#!/usr/bin/env python3
"""Classify a product against the official request catalog."""

from __future__ import annotations

import argparse
import base64
import difflib
import json
import mimetypes
import os
import re
import shutil
import subprocess
import sys
import unicodedata
import urllib.error
import urllib.request
import zipfile
from pathlib import Path
from typing import Any
from xml.etree import ElementTree


STOPWORDS = {
    "para", "por", "con", "del", "las", "los", "una", "uno", "producto", "productos",
    "technical", "data", "sheet", "ficha", "tecnica", "modelo", "marca", "fabricante",
}

SYNONYMS = {
    "bombillo": "fuente luminosa lampara led",
    "breaker": "interruptor disyuntor automatico",
    "enchufe": "clavija tomacorriente",
    "lampara": "luminaria fuente luminosa",
    "motobomba": "electrobomba",
    "panel led": "luminaria espacios interiores",
    "taco": "interruptor disyuntor automatico",
    "trafo": "transformador electrico",
    "vajilla": "articulos vidrio ceramica contacto alimentos",
}


def normalize(value: Any) -> str:
    text = unicodedata.normalize("NFKD", str(value or ""))
    text = "".join(char for char in text if not unicodedata.combining(char))
    text = re.sub(r"[^a-zA-Z0-9]+", " ", text).lower()
    return re.sub(r"\s+", " ", text).strip()


def tokens(value: str) -> set[str]:
    return {token for token in normalize(value).split() if len(token) > 2 and token not in STOPWORDS}


def extract_pdf(path: Path) -> str:
    try:
        from pypdf import PdfReader
        return "\n".join((page.extract_text() or "") for page in PdfReader(str(path)).pages)
    except Exception:
        return ""


def extract_docx(path: Path) -> str:
    try:
        with zipfile.ZipFile(path) as archive:
            xml = archive.read("word/document.xml")
        root = ElementTree.fromstring(xml)
        return " ".join(node.text or "" for node in root.iter() if node.tag.endswith("}t"))
    except Exception:
        return ""


def extract_xlsx(path: Path) -> str:
    try:
        import openpyxl
        workbook = openpyxl.load_workbook(path, read_only=True, data_only=True)
        values: list[str] = []
        for sheet in workbook.worksheets:
            for row in sheet.iter_rows(values_only=True):
                values.extend(str(value) for value in row if value not in (None, ""))
                if len(values) >= 8000:
                    break
            if len(values) >= 8000:
                break
        workbook.close()
        return "\n".join(values)
    except Exception:
        return ""


def extract_image_ocr(path: Path) -> str:
    executable = shutil.which("tesseract")
    if not executable:
        return ""
    try:
        result = subprocess.run(
            [executable, str(path), "stdout", "-l", "spa+eng"],
            capture_output=True,
            text=True,
            timeout=25,
            check=False,
        )
        return result.stdout if result.returncode == 0 else ""
    except Exception:
        return ""


def extract_text(path: Path) -> str:
    suffix = path.suffix.lower()
    if suffix == ".pdf":
        return extract_pdf(path)
    if suffix == ".docx":
        return extract_docx(path)
    if suffix in {".xlsx", ".xlsm"}:
        return extract_xlsx(path)
    if suffix in {".jpg", ".jpeg", ".png", ".webp", ".tif", ".tiff"}:
        return extract_image_ocr(path)
    if suffix in {".txt", ".csv", ".tsv"}:
        return path.read_text(encoding="utf-8", errors="ignore")
    return ""


def first_match(text: str, patterns: list[str]) -> str:
    for pattern in patterns:
        match = re.search(pattern, text, flags=re.IGNORECASE | re.MULTILINE)
        if match:
            return re.sub(r"\s+", " ", match.group(1)).strip(" :-|\t")[:240]
    return ""


def labeled_value(text: str, labels: list[str], maximum: int = 180) -> str:
    """Read values from flattened PDF/Excel table rows such as '5.2 MATERIAL SILICONA'."""
    normalized_labels = [(normalize(label), len(normalize(label).split())) for label in labels]
    for raw_line in text.splitlines():
        line = re.sub(r"^\s*\d+(?:\.\d+)*\s*", "", raw_line).strip(" :-|\t")
        if not line:
            continue
        normalized_line = normalize(line)
        words = re.findall(r"\S+", line)
        for normalized_label, word_count in normalized_labels:
            if normalized_line == normalized_label or not normalized_line.startswith(normalized_label + " "):
                continue
            value = " ".join(words[word_count:]).strip(" :-|\t")
            if value:
                return re.sub(r"\s+", " ", value)[:maximum]
    return ""


def extract_fields(text: str, supplied_name: str) -> dict[str, str]:
    compact = text[:200000]
    fields = {
        "generic_name": supplied_name.strip(),
        "brand": first_match(compact, [r"(?:marca|brand)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{2,80})"]),
        "model": first_match(compact, [r"(?:modelo|referencia|model|ref\.?|sku)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{1,100})"]),
        "manufacturer": first_match(compact, [r"(?:fabricante|manufacturer|manufactured by)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{2,140})"]),
        "manufacturer_address": first_match(compact, [r"(?:direccion (?:del )?fabricante|manufacturer address)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{3,180})"]),
        "country": first_match(compact, [r"(?:pais de origen|country of origin|origen)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{2,80})"]),
        "marketer": first_match(compact, [r"(?:comercializado por|distribuido por|importador|marketer|distributed by)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{2,140})"]),
        "invoice_number": first_match(compact, [r"(?:factura(?: del lote)?|invoice)\s*(?:n[o.]*)?\s*(?:[:\-]\s*|\n\s*)([^\n\r]{1,80})"]),
        "tariff_code": first_match(compact, [r"(?:partida|subpartida)(?: arancelaria)?\s*(?:[:\-]\s*|\n\s*)([0-9.\-]{4,30})"]),
        "voltage": first_match(compact, [r"(?:tension|voltaje|voltage|rated voltage)(?:\s+nominal)?\s*[:\-]?\s*([0-9][0-9., /\-]*(?:mv|kv|vac|vdc|v|voltios?))\b"]),
        "current": first_match(compact, [r"(?:corriente|current|rated current)(?:\s+(?:nominal|nominal y maxima|maxima))?\s*[:\-]?\s*([0-9][0-9., /\-]*(?:ma|a|amperios?))\b"]),
        "power": first_match(compact, [r"(?:potencia|power|rated power)\s*[:\-]?\s*([0-9][0-9., /\-]*(?:w|kw|va|kva))\b"]),
        "frequency": first_match(compact, [r"(?:frecuencia|frequency)\s*[:\-]?\s*([0-9][0-9., /\-]*(?:hz))\b"]),
        "ip_rating": first_match(compact, [r"(?:grado de proteccion|proteccion|ip rating)\s*[:\-]?\s*(IP[0-9]{2}[A-Z]?)\b", r"\b(IP[0-9]{2}[A-Z]?)\b"]),
        "material": first_match(compact, [r"(?:material|materials?)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{2,120})"]),
        "technical_standard": first_match(compact, [r"(?:norma(?: tecnica)?|standard|compliance)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{2,180})"]),
        "description": first_match(compact, [r"(?:descripcion|description)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{3,240})"]),
        "business_use": first_match(compact, [r"(?:uso|aplicacion|application|intended use)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{3,200})"]),
        "bulb_type": first_match(compact, [r"(?:tipo de bombilla|bombilla|lamp type)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{2,120})"]),
        "age_range": first_match(compact, [r"(?:edad de uso|rango de edad|edad recomendada|age range)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{1,80})"]),
        "battery_powered": first_match(compact, [r"(?:usa baterias|funciona con baterias|battery powered|medio electrico)\s*(?:[:\-]\s*|\n\s*)((?:si|no|no aplica)[^\n\r]{0,40})"]),
        "presentation": first_match(compact, [r"(?:presentacion|presentation)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{2,160})"]),
        "color_finish": first_match(compact, [r"(?:color|acabado|finish)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{2,100})"]),
        "capacity": first_match(compact, [r"(?:capacidad|volumen|capacity|volume)\s*[:\-]?\s*([0-9][0-9., ]*(?:ml|l|litros?))\b"]),
        "material_standard": first_match(compact, [r"(?:norma (?:aplicable )?al material|material standard)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{2,180})"]),
        "dimensions": first_match(compact, [r"(?:dimensiones|dimensions)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{2,140})"]),
        "chin_protection": first_match(compact, [r"(?:proteccion de la barbilla|chin protection)\s*(?:[:\-]\s*|\n\s*)((?:si|no|no aplica)[^\n\r]{0,40})"]),
        "lot_serial": first_match(compact, [r"^(?:lote|serial|batch)(?: no| number| nro)?\s*(?:[:\-]\s*|\n\s*)([^\n\r]{1,100})"]),
        "manufacture_date": first_match(compact, [r"(?:fecha de fabricacion|manufacturing date|manufactured on)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{4,40})"]),
        "manufacturer_importer_id": first_match(compact, [r"(?:id del fabricante|id del importador|manufacturer id|importer id)\s*(?:[:\-]\s*|\n\s*)([^\n\r]{2,100})"]),
        "subfamily_detail": "",
    }

    fallback_labels = {
        "brand": ["marca comercial", "brand"],
        "model": ["modelo", "referencia", "reference", "model", "sku"],
        "manufacturer": ["fabricante", "manufacturer", "manufactured by"],
        "manufacturer_address": ["direccion del fabricante", "endereco do fabricante", "manufacturer address"],
        "country": ["pais de origen", "pais de origem", "country of origin"],
        "technical_standard": ["norma tecnica aplicable", "norma aplicavel", "standard"],
        "voltage": ["tension nominal", "tensao nominal", "rated voltage", "voltaje nominal"],
        "current": ["corriente nominal", "corrente nominal", "rated current"],
        "power": ["potencia nominal", "rated power", "potencia"],
        "frequency": ["frecuencia", "frequencia", "frequency"],
        "ip_rating": ["grado de proteccion ip", "grau de protecao ip", "ip rating"],
        "material": ["material de fabricacion", "material do produto", "material do isolador", "material"],
        "color_finish": ["color acabado", "cor acabamento", "color", "cor"],
        "presentation": ["presentacion", "apresentacao", "tipo de embalaje", "tipo de embalagem"],
        "lot_serial": ["numero de lote", "lote", "serial", "batch"],
        "manufacture_date": ["fecha de fabricacion", "data de fabricacao", "manufacturing date"],
    }
    for key, labels in fallback_labels.items():
        if not fields[key]:
            fields[key] = labeled_value(compact, labels)

    if normalize(fields["manufacturer"]) in {"ano", "marca", "ano valor de carga de ruptura"}:
        fields["manufacturer"] = ""

    if not fields["dimensions"]:
        dimension_parts = []
        creepage = labeled_value(compact, ["distancia de fuga", "creepage distance"])
        arc = labeled_value(compact, ["distancia de arco", "arcing distance"])
        if creepage:
            dimension_parts.append(f"Distancia de fuga: {creepage}")
        if arc:
            dimension_parts.append(f"Distancia de arco: {arc}")
        fields["dimensions"] = "; ".join(dimension_parts)

    product_type = labeled_value(compact, ["tipo de producto", "tipo do produto", "tipo"])
    if not fields["description"] and product_type:
        fields["description"] = f"{supplied_name.strip()}. Tipo: {product_type}"[:240]
    return fields


def expand_query(name: str, text: str) -> str:
    query = f"{name} {text[:30000]}"
    normalized_name = normalize(name)
    additions = [replacement for synonym, replacement in SYNONYMS.items() if synonym in normalized_name]
    return f"{query} {' '.join(additions)}"


def rank_catalog(catalog: list[dict[str, Any]], name: str, text: str, preferred_id: int = 0) -> list[dict[str, Any]]:
    query = expand_query(name, text)
    query_tokens = tokens(query)
    name_normalized = normalize(name)
    ranked: list[dict[str, Any]] = []

    for row in catalog:
        core_fields = [row.get("producto_nombre"), row.get("familia_nombre"), row.get("subfamilia_nombre")]
        core = " ".join(str(value or "") for value in core_fields)
        searchable = " ".join([
            core,
            str(row.get("reglamento_nombre") or ""),
            str(row.get("documento_normativo") or ""),
        ])
        target_tokens = tokens(searchable)
        overlap = len(query_tokens & target_tokens) / max(1, min(len(query_tokens), len(target_tokens), 20))
        similarities = [difflib.SequenceMatcher(None, name_normalized, normalize(value)).ratio() for value in core_fields if value]
        similarity = max(similarities or [0.0])
        containment = 1.0 if name_normalized and name_normalized in normalize(core) else 0.0
        score = round(min(100.0, (similarity * 55.0) + (overlap * 35.0) + (containment * 10.0)), 1)
        ranked.append({
            "id": int(row["id"]),
            "regulation_code": row.get("reglamento_codigo", ""),
            "regulation": row.get("reglamento_nombre", ""),
            "product": row.get("producto_nombre", ""),
            "family": row.get("familia_nombre", ""),
            "subfamily": row.get("subfamilia_nombre", ""),
            "subfamily_references": row.get("subfamilia_referencias", []),
            "nace_code": row.get("codigo_nace", ""),
            "article": row.get("articulo", ""),
            "numeral": row.get("numeral", ""),
            "schemes": row.get("esquemas", []),
            "documents": row.get("documentos", []),
            "score": score,
        })

    ranked = sorted(ranked, key=lambda item: (-item["score"], item["product"]))
    if preferred_id:
        preferred = next((item for item in ranked if item["id"] == preferred_id), None)
        if preferred:
            return [preferred] + [item for item in ranked if item["id"] != preferred_id][:7]
    return ranked[:8]


def call_gemini(evidence_path: Path, fields: dict[str, str], candidates: list[dict[str, Any]], document_text: str) -> tuple[dict[str, Any], str]:
    api_key = os.getenv("SERVIMETERS_GEMINI_API_KEY") or os.getenv("GEMINI_API_KEY")
    if not api_key:
        return {}, ""

    model = os.getenv("SERVIMETERS_GEMINI_MODEL", "gemini-2.5-flash")
    mime_type = mimetypes.guess_type(evidence_path.name)[0] or "application/octet-stream"
    candidate_payload = [
        {
            "id": item["id"],
            "regulation": item["regulation"],
            "product": item["product"],
            "family": item["family"],
            "subfamily": item["subfamily"],
            "subfamily_references": item["subfamily_references"],
            "nace_code": item["nace_code"],
            "article": item["article"],
            "numeral": item["numeral"],
            "schemes": item["schemes"],
        }
        for item in candidates
    ]
    prompt = (
        "Analiza el archivo aportado, el texto extraido de la ficha tecnica y, cuando exista, la fotografia o placa del producto. "
        "Devuelve exclusivamente JSON valido. Usa solo informacion legible y sustentada por la evidencia; no completes valores por conocimiento general ni por inferencia. "
        "Selecciona catalogo_id solo entre los candidatos. Campos: generic_name, brand, model, "
        "manufacturer, manufacturer_address, country, marketer, invoice_number, tariff_code, "
        "voltage, current, power, frequency, ip_rating, material, technical_standard, description, "
        "business_use, bulb_type, age_range, battery_powered, presentation, color_finish, capacity, "
        "material_standard, dimensions, chin_protection, lot_serial, manufacture_date, "
        "manufacturer_importer_id, subfamily_detail, catalogo_id, confidence, visual_evidence. "
        "subfamily_detail debe coincidir exactamente con una referencia de subfamilia del candidato solamente cuando la ficha o la imagen sustenten el tipo, material y nivel de tension; de lo contrario debe quedar vacio. "
        "Conserva unidades, rangos y designaciones tal como aparecen en la evidencia. Si un dato no es visible, devuelve una cadena vacia.\n"
        f"Extraccion local: {json.dumps(fields, ensure_ascii=False)}\n"
        f"Texto de ficha: {document_text[:16000]}\n"
        f"Candidatos oficiales: {json.dumps(candidate_payload, ensure_ascii=False)}"
    )
    payload = {
        "contents": [{
            "parts": [
                {"text": prompt},
                {"inlineData": {"mimeType": mime_type, "data": base64.b64encode(evidence_path.read_bytes()).decode("ascii")}},
            ]
        }],
        "generationConfig": {"temperature": 0.1, "responseMimeType": "application/json"},
    }
    request = urllib.request.Request(
        f"https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent",
        data=json.dumps(payload).encode("utf-8"),
        headers={"Content-Type": "application/json", "x-goog-api-key": api_key},
        method="POST",
    )
    try:
        with urllib.request.urlopen(request, timeout=55) as response:
            result = json.loads(response.read().decode("utf-8"))
        raw = result["candidates"][0]["content"]["parts"][0]["text"]
        raw = re.sub(r"^```(?:json)?|```$", "", raw.strip(), flags=re.IGNORECASE).strip()
        parsed = json.loads(raw)
        valid_ids = {item["id"] for item in candidates}
        if int(parsed.get("catalogo_id") or 0) not in valid_ids:
            parsed["catalogo_id"] = candidates[0]["id"] if candidates else 0
        return parsed, ""
    except (urllib.error.URLError, urllib.error.HTTPError, KeyError, IndexError, ValueError, json.JSONDecodeError) as exc:
        return {}, f"La vision de Gemini no estuvo disponible: {exc}"


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--catalog", required=True)
    parser.add_argument("--image", default="")
    parser.add_argument("--datasheet", default="")
    parser.add_argument("--name", required=True)
    parser.add_argument("--preferred-id", type=int, default=0)
    args = parser.parse_args()

    catalog = json.loads(Path(args.catalog).read_text(encoding="utf-8"))
    image_path = Path(args.image) if args.image else None
    datasheet_path = Path(args.datasheet) if args.datasheet else None
    document_text = extract_text(datasheet_path) if datasheet_path else ""
    image_text = extract_image_ocr(image_path) if image_path else ""
    combined_text = f"{document_text}\n{image_text}".strip()
    fields = extract_fields(combined_text, args.name)
    candidates = rank_catalog(catalog, args.name, combined_text, args.preferred_id)

    gemini_source = image_path
    if gemini_source is None and datasheet_path and datasheet_path.suffix.lower() in {".pdf", ".jpg", ".jpeg", ".png", ".webp"}:
        gemini_source = datasheet_path
    vision, vision_error = call_gemini(gemini_source, fields, candidates, document_text) if gemini_source else ({}, "")
    mode = ("gemini_vision" if image_path else "gemini_document") if vision else ("local_documental" if document_text or image_text else "local_limited")
    if vision:
        for key in fields:
            if str(vision.get(key) or "").strip():
                fields[key] = str(vision[key]).strip()
        selected_id = int(vision.get("catalogo_id") or 0)
        candidates.sort(key=lambda item: (item["id"] != selected_id, -item["score"]))
    if args.preferred_id:
        candidates.sort(key=lambda item: (item["id"] != args.preferred_id, -item["score"]))
    warning = vision_error
    if not vision and not warning:
        warning = "Clasificación local completada. Valida los campos que no pudieron extraerse de la evidencia."
        if image_path:
            warning = "La visión de Gemini no está activa. La fotografía queda como evidencia y la coincidencia usa el nombre y el texto legible."

    result = {
        "ok": True,
        "mode": mode,
        "warning": warning,
        "fields": fields,
        "candidates": candidates,
        "evidence": {
            "datasheet_characters": len(document_text),
            "image_ocr_characters": len(image_text),
            "top_score": candidates[0]["score"] if candidates else 0,
            "visual_evidence": vision.get("visual_evidence", "") if vision else "",
            "extracted_fields": sum(1 for value in fields.values() if str(value or "").strip()),
        },
    }
    print(json.dumps(result, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    sys.exit(main())
