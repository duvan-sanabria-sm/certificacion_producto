#!/usr/bin/env python3
"""Extract and validate an ISO 9001 certificate against a service request."""

from __future__ import annotations

import argparse
import base64
import datetime as dt
import difflib
import json
import mimetypes
import os
import re
import sys
import unicodedata
import urllib.error
import urllib.request
from pathlib import Path
from typing import Any


GENERIC_WORDS = {
    "para", "con", "por", "los", "las", "del", "una", "uno", "producto", "productos",
    "fabricacion", "manufactura", "produccion", "ensamble", "construccion", "servicio", "sistema",
    "certificacion", "calidad", "quality", "management", "manufacturing", "production", "assembly",
}
MANUFACTURING_WORDS = {
    "fabricacion", "fabricar", "manufactura", "manufacturing", "produccion", "production",
    "ensamble", "ensamblaje", "assembly", "construccion", "elaboracion", "manufacture",
}


def normalize(value: Any) -> str:
    text = unicodedata.normalize("NFKD", str(value or ""))
    text = "".join(char for char in text if not unicodedata.combining(char))
    return re.sub(r"\s+", " ", re.sub(r"[^a-zA-Z0-9]+", " ", text).lower()).strip()


def useful_tokens(value: Any) -> set[str]:
    return {
        token for token in normalize(value).split()
        if len(token) > 2 and token not in GENERIC_WORDS
    }


def extract_pdf(path: Path) -> str:
    try:
        from pypdf import PdfReader
        return "\n".join((page.extract_text() or "") for page in PdfReader(str(path)).pages)
    except Exception:
        return ""


def extract_text(path: Path) -> str:
    if path.suffix.lower() == ".pdf":
        return extract_pdf(path)
    return ""


def first_match(text: str, patterns: list[str], maximum: int = 800) -> str:
    for pattern in patterns:
        match = re.search(pattern, text, flags=re.IGNORECASE | re.MULTILINE)
        if match:
            return re.sub(r"\s+", " ", match.group(1)).strip(" :-|\t")[:maximum]
    return ""


def local_extract(text: str) -> dict[str, str]:
    compact = text[:240000]
    standard = first_match(compact, [r"\b((?:ISO\s*)?9001\s*:?\s*20\d{2})\b", r"\b(ISO\s*9001)\b"], 80)
    holder = first_match(compact, [
        r"(?:certif(?:y|ies|ica|icamos)\s+(?:that|que)?|titular|cliente|organization|organizacion)\s*[:\-]?\s*([^\n\r]{3,180})",
        r"(?:issued to|otorgado a|concedido a)\s*[:\-]?\s*([^\n\r]{3,180})",
    ], 180)
    scope = first_match(compact, [
        r"(?:scope|alcance|certification scope|alcance de certificacion)\s*[:\-]?\s*([^\n\r]{10,800}(?:\n[^\n\r]{5,300}){0,4})",
    ], 900)
    certifier = first_match(compact, [
        r"(?:certification body|organismo certificador|certificado por|certified by)\s*[:\-]?\s*([^\n\r]{2,160})",
    ], 160)
    certificate_number = first_match(compact, [
        r"(?:certificate (?:number|no\.?|n[°º])|numero de certificado|certificado n[°º])\s*[:\-]?\s*([A-Z0-9./_-]{3,100})",
    ], 100)
    expiry = first_match(compact, [
        r"(?:expiry date|expiration date|valid until|fecha de vencimiento|valido hasta|vigencia hasta)\s*[:\-]?\s*([0-9]{1,4}[./-][0-9]{1,2}[./-][0-9]{1,4})",
    ], 40)
    accreditors = [name for name in ["ONAC", "UKAS", "ANAB", "ENAC", "SCC", "CNAS", "IAF", "IQNET"] if re.search(rf"\b{name}\b", compact, re.I)]
    return {
        "idioma": "No determinado",
        "titular": holder,
        "norma": standard,
        "numero_certificado": certificate_number,
        "certificador": certifier,
        "acreditador": ", ".join(accreditors),
        "alcance": scope,
        "fecha_vencimiento": normalize_date(expiry),
        "urls_verificacion": ", ".join(re.findall(r'https?://[^\s<>"\']+', compact, flags=re.I)[:5]),
    }


def normalize_date(value: Any) -> str:
    text = str(value or "").strip()
    if not text:
        return ""
    for fmt in ("%Y-%m-%d", "%d/%m/%Y", "%d-%m-%Y", "%Y/%m/%d", "%m/%d/%Y"):
        try:
            return dt.datetime.strptime(text[:10], fmt).date().isoformat()
        except ValueError:
            continue
    match = re.search(r"(20\d{2})[-/.](\d{1,2})[-/.](\d{1,2})", text)
    if match:
        try:
            return dt.date(int(match.group(1)), int(match.group(2)), int(match.group(3))).isoformat()
        except ValueError:
            return ""
    return ""


def call_gemini(path: Path, products: list[str], manufacturers: list[str], local: dict[str, str], text: str) -> tuple[dict[str, Any], str]:
    api_key = os.getenv("SERVIMETERS_GEMINI_API_KEY") or os.getenv("GEMINI_API_KEY")
    if not api_key:
        return {}, ""
    model = os.getenv("SERVIMETERS_GEMINI_MODEL", "gemini-2.5-flash")
    mime_type = mimetypes.guess_type(path.name)[0] or "application/pdf"
    prompt = (
        "Actua como revisor documental de certificacion ISO 9001. Extrae unicamente datos visibles del archivo y devuelve JSON valido, sin markdown, "
        "con las claves idioma, titular, norma, numero_certificado, certificador, acreditador, alcance, fecha_vencimiento, urls_verificacion. "
        "fecha_vencimiento debe usar YYYY-MM-DD o quedar vacia. No inventes datos. El alcance debe conservar suficiente detalle para compararlo con los productos.\n"
        f"Productos solicitados: {json.dumps(products, ensure_ascii=False)}\n"
        f"Fabricantes declarados: {json.dumps(manufacturers, ensure_ascii=False)}\n"
        f"Extraccion local: {json.dumps(local, ensure_ascii=False)}\n"
        f"Texto local disponible: {text[:12000]}"
    )
    payload = {
        "contents": [{"parts": [
            {"text": prompt},
            {"inlineData": {"mimeType": mime_type, "data": base64.b64encode(path.read_bytes()).decode("ascii")}},
        ]}],
        "generationConfig": {"temperature": 0.05, "responseMimeType": "application/json"},
    }
    request = urllib.request.Request(
        f"https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent",
        data=json.dumps(payload).encode("utf-8"),
        headers={"Content-Type": "application/json", "x-goog-api-key": api_key},
        method="POST",
    )
    try:
        with urllib.request.urlopen(request, timeout=60) as response:
            result = json.loads(response.read().decode("utf-8"))
        raw = result["candidates"][0]["content"]["parts"][0]["text"].strip()
        raw = re.sub(r"^```(?:json)?|```$", "", raw, flags=re.I).strip()
        parsed = json.loads(raw)
        return parsed if isinstance(parsed, dict) else {}, ""
    except (urllib.error.URLError, urllib.error.HTTPError, KeyError, IndexError, ValueError, json.JSONDecodeError) as exc:
        return {}, f"Gemini no estuvo disponible: {exc}"


def compare_certificate(data: dict[str, Any], products: list[str], manufacturers: list[str]) -> dict[str, Any]:
    alerts: list[str] = []
    standard_ok = "9001" in normalize(data.get("norma"))
    if not standard_ok:
        alerts.append("No se identificó claramente la norma ISO 9001 en el certificado.")

    expiry_text = normalize_date(data.get("fecha_vencimiento"))
    data["fecha_vencimiento"] = expiry_text
    expiry_status = "indeterminada"
    days_remaining: int | None = None
    if expiry_text:
        expiry = dt.date.fromisoformat(expiry_text)
        days_remaining = (expiry - dt.date.today()).days
        expiry_status = "vigente" if days_remaining >= 0 else "vencido"
        if days_remaining < 0:
            alerts.append("El certificado ISO 9001 está vencido.")
        elif days_remaining <= 90:
            alerts.append(f"El certificado vence en {days_remaining} día(s); el proceso puede superar este plazo.")
    else:
        alerts.append("No fue posible determinar la fecha de vencimiento.")

    holder = normalize(data.get("titular"))
    manufacturer_match = not manufacturers
    manufacturer_score = 0.0
    for manufacturer in manufacturers:
        candidate = normalize(manufacturer)
        if not candidate:
            continue
        score = difflib.SequenceMatcher(None, holder, candidate).ratio()
        overlap = bool(useful_tokens(holder) & useful_tokens(candidate))
        manufacturer_score = max(manufacturer_score, score)
        if score >= 0.62 or overlap:
            manufacturer_match = True
    if manufacturers and not manufacturer_match:
        alerts.append("El titular del certificado no coincide claramente con el fabricante declarado.")

    scope = normalize(data.get("alcance"))
    has_manufacturing = bool(useful_tokens(scope) & MANUFACTURING_WORDS) or any(word in scope for word in MANUFACTURING_WORDS)
    product_tokens = set().union(*(useful_tokens(product) for product in products)) if products else set()
    scope_product_overlap = sorted(product_tokens & useful_tokens(scope))
    scope_match = bool(scope_product_overlap) and has_manufacturing
    if not has_manufacturing:
        alerts.append("El alcance no evidencia actividades de producción, fabricación, manufactura o ensamblaje.")
    if products and not scope_product_overlap:
        alerts.append("El alcance del certificado no coincide con los productos incluidos en la solicitud.")

    accredited = bool(str(data.get("acreditador") or "").strip())
    if not accredited:
        alerts.append("No se identificó claramente el organismo acreditador; requiere verificación manual.")

    if expiry_status == "vencido" or not standard_ok:
        result = "no_conforme"
    elif not scope_match or not manufacturer_match or expiry_status == "indeterminada" or not accredited:
        result = "alerta"
    else:
        result = "conforme"
    return {
        "resultado": result,
        "vigencia": expiry_status,
        "dias_vigencia_restantes": days_remaining,
        "norma_iso_9001": standard_ok,
        "titular_coincide": manufacturer_match,
        "puntaje_titular": round(manufacturer_score * 100, 1),
        "alcance_incluye_fabricacion": has_manufacturing,
        "alcance_coincide_producto": scope_match,
        "coincidencias_producto": scope_product_overlap,
        "acreditador_identificado": accredited,
        "alertas": alerts,
    }


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--certificate", required=True)
    parser.add_argument("--products-json", required=True)
    parser.add_argument("--manufacturers-json", required=True)
    args = parser.parse_args()

    path = Path(args.certificate)
    products = [str(value) for value in json.loads(args.products_json) if str(value).strip()]
    manufacturers = [str(value) for value in json.loads(args.manufacturers_json) if str(value).strip()]
    text = extract_text(path)
    local = local_extract(text)
    gemini, gemini_error = call_gemini(path, products, manufacturers, local, text)
    data = dict(local)
    for key, value in gemini.items():
        if str(value or "").strip():
            data[key] = str(value).strip()
    validation = compare_certificate(data, products, manufacturers)
    warning = gemini_error
    mode = "gemini_document" if gemini else "local_documental"
    if not gemini and not warning:
        warning = "Análisis documental local completado. Confirma manualmente los datos no identificados."
    print(json.dumps({
        "ok": True,
        "mode": mode,
        "warning": warning,
        "certificate": data,
        "validation": validation,
        "evidence": {"text_characters": len(text), "file_name": path.name},
    }, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    sys.exit(main())
