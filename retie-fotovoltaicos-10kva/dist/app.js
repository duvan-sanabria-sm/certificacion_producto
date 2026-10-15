"use strict";

const ui = {
  questionView: document.querySelector("#questionView"), resultView: document.querySelector("#resultView"),
  questionKicker: document.querySelector("#questionKicker"), questionText: document.querySelector("#questionText"),
  questionHelp: document.querySelector("#questionHelp"), answerArea: document.querySelector("#answerArea"),
  stepLabel: document.querySelector("#stepLabel"), progressBar: document.querySelector("#progressBar"),
  normCallout: document.querySelector("#normCallout"), normReference: document.querySelector("#normReference"),
  normExplanation: document.querySelector("#normExplanation"), backButton: document.querySelector("#backButton"),
  routeSummary: document.querySelector("#routeSummary"), resultBadge: document.querySelector("#resultBadge"),
  resultTitle: document.querySelector("#resultTitle"), resultText: document.querySelector("#resultText"),
  resultParameter: document.querySelector("#resultParameter"), resultValue: document.querySelector("#resultValue"),
  resultNorm: document.querySelector("#resultNorm"), resultAction: document.querySelector("#resultAction"),
  diagramCanvas: document.querySelector("#diagramCanvas"), diagramConnection: document.querySelector("#diagramConnection"),
  diagramParameter: document.querySelector("#diagramParameter"), diagramValue: document.querySelector("#diagramValue"),
  diagramVerdict: document.querySelector("#diagramVerdict"), diagramVerdictIcon: document.querySelector("#diagramVerdictIcon"),
  diagramVerdictText: document.querySelector("#diagramVerdictText"), diagramLiteral: document.querySelector("#diagramLiteral"),
};

let state = initialState();
let screen = "connection";
let history = [];

function initialState() { return { connected: null, exports: null, parameter: null, value: null, verified: null, literal: null }; }
function formatKva(value) { return Number.isFinite(value) ? `${new Intl.NumberFormat("es-CO", { maximumFractionDigits: 2 }).format(value)} kVA` : "No definido"; }
function setNorm(reference, explanation) {
  ui.normCallout.hidden = !reference; ui.normReference.textContent = reference || ""; ui.normExplanation.textContent = explanation || "";
}

function buttonChoice(label, detail, action) {
  const button = document.createElement("button"); button.type = "button"; button.className = "choice-button";
  const strong = document.createElement("strong"); strong.textContent = label;
  const small = document.createElement("small"); small.textContent = detail;
  button.append(strong, small); button.addEventListener("click", action); return button;
}

function goTo(nextScreen, update = {}) { history.push({ screen, state: { ...state } }); Object.assign(state, update); screen = nextScreen; render(); }
function finish(resultType, update = {}) { history.push({ screen, state: { ...state } }); Object.assign(state, update); screen = resultType; render(); }

function renderConnection() {
  setNorm("", "");
  ui.questionKicker.textContent = "Primera pregunta";
  ui.questionText.textContent = "¿El sistema se conecta a la red para entregar excedentes?";
  ui.questionHelp.textContent = "Seleccione la opción A o B según el diagrama unifilar y el modo real de operación.";
  ui.answerArea.append(
    buttonChoice("A. Sí, con excedentes", "Evaluar capacidad instalable · literal h.", () => goTo("capacity", { connected: true, exports: true, parameter: "installable", literal: "Literal h" })),
    buttonChoice("B. No, sistema aislado", "Evaluar potencia instalada · literal i.", () => goTo("capacity", { connected: false, exports: false, parameter: "installed", literal: "Literal i" })),
    buttonChoice("Caso diferente o no sé", "Conectado sin excedentes o información insuficiente.", () => finish("outside-route"))
  );
}

function renderCapacity() {
  const installable = state.parameter === "installable";
  setNorm(`Artículo 4.3.2.1, ${installable ? "literal h" : "literal i"}`, installable
    ? "Para sistemas conectados a la red con excedentes se evalúa la capacidad instalable de la acometida."
    : "Para sistemas asociados al uso final sin conexión a la red se evalúa la potencia instalada.");
  ui.questionKicker.textContent = installable ? "Parámetro: capacidad instalable" : "Parámetro: potencia instalada";
  ui.questionText.textContent = installable ? "¿Cuál es la capacidad instalable de la acometida?" : "¿Cuál es la potencia instalada definida por el diseñador?";
  ui.questionHelp.textContent = installable
    ? "Ingrese el valor en kVA de la acometida. No use la potencia del inversor como sustituto."
    : "Ingrese los kVA definidos por el diseñador. El inversor es determinante cuando corresponde al componente limitante de la instalación.";
  const wrapper = document.createElement("div"); wrapper.className = "number-answer";
  const label = document.createElement("label"); const span = document.createElement("span");
  span.textContent = installable ? "Capacidad instalable (kVA)" : "Potencia instalada (kVA)";
  const input = document.createElement("input"); input.type = "number"; input.min = "0.01"; input.step = "0.01"; input.inputMode = "decimal"; input.placeholder = "Ej. 8 o 15";
  input.value = Number.isFinite(state.value) ? String(state.value) : ""; label.append(span, input);
  const continueButton = document.createElement("button"); continueButton.type = "button"; continueButton.className = "primary-button"; continueButton.textContent = "Continuar";
  const error = document.createElement("p"); error.className = "inline-error"; error.hidden = true;
  const submitValue = () => {
    const value = Number(input.value);
    if (!Number.isFinite(value) || value <= 0) { error.textContent = "Ingrese un valor mayor que cero."; error.hidden = false; input.focus(); return; }
    goTo("evidence", { value });
  };
  input.addEventListener("input", () => {
    const value = Number(input.value);
    state.value = input.value !== "" && Number.isFinite(value) && value > 0 ? value : null;
    updateDiagram();
    updateSummary();
  });
  continueButton.addEventListener("click", submitValue); input.addEventListener("keydown", event => { if (event.key === "Enter") submitValue(); });
  wrapper.append(label, continueButton); ui.answerArea.append(wrapper, error); requestAnimationFrame(() => input.focus());
}

function renderEvidence() {
  const installable = state.parameter === "installable";
  setNorm(`Artículo 4.3.2.1, ${installable ? "literal h" : "literal i"}`, `${installable ? "Capacidad instalable" : "Potencia instalada"}: ${formatKva(state.value)}. El umbral se activa desde 10 kVA.`);
  ui.questionKicker.textContent = "Última verificación";
  ui.questionText.textContent = "¿El valor está respaldado por un documento técnico verificado?";
  ui.questionHelp.textContent = installable ? "Puede ser memoria de cálculo, plan de diseño o documento del ODR que sustente la acometida." : "Use la memoria o el plan de diseño y verifique la capacidad nominal del inversor cuando sea el componente limitante.";
  ui.answerArea.append(
    buttonChoice("Sí, está verificado", "La fuente y el valor son trazables.", () => finish("classified", { verified: true })),
    buttonChoice("No, solo informado", "El cliente lo declaró, pero falta soporte.", () => finish("unverified", { verified: false })),
    buttonChoice("No tengo el dato", "Debe solicitarse antes de cerrar.", () => finish("unverified", { verified: false }))
  );
}

function resultData() {
  const installable = state.parameter === "installable";
  const parameter = installable ? "Capacidad instalable de la acometida" : state.parameter === "installed" ? "Potencia instalada del sistema" : "Por determinar";
  const norm = state.literal ? `Artículo 4.3.2.1, ${state.literal.toLowerCase()}` : "Artículo 4.3.2";
  if (screen === "outside-route") return { type: "review", title: "Confirmación técnica necesaria", text: "El caso no coincide con las dos ramas del diagrama. Puede tratarse de una conexión sin excedentes o de información insuficiente.", parameter: "Por determinar", value: "No definido", norm: "Artículo 4.3.2", action: "Solicite el diagrama unifilar y escale el caso a Dirección Técnica antes de definir el alcance." };
  if (screen === "unverified") {
    const reaches = state.value >= 10;
    return { type: "pending", title: reaches ? "Alcanzaría el umbral, pero falta soporte" : "Estaría bajo el umbral, pero falta soporte", text: `El valor informado es ${formatKva(state.value)}. La decisión no debe cerrarse hasta verificar el documento técnico.`, parameter, value: formatKva(state.value), norm, action: installable ? "Solicite el soporte de la capacidad de la acometida; no use solo la potencia del inversor." : "Solicite la memoria o el plan de diseño que determine la potencia instalada." };
  }
  const applies = state.value >= 10;
  return applies
    ? { type: "applies", title: "Aplica certificación plena", text: `${parameter} verificada: ${formatKva(state.value)}. El valor es igual o superior a 10 kVA y activa el ${state.literal.toLowerCase()}.`, parameter, value: formatKva(state.value), norm, action: "Definir o mantener el alcance de certificación plena y conservar el soporte técnico." }
    : { type: "no-literal", title: `No activa el ${state.literal.toLowerCase()} por umbral`, text: `${parameter} verificada: ${formatKva(state.value)}. El valor es inferior a 10 kVA.`, parameter, value: formatKva(state.value), norm, action: "Verificar que ningún otro supuesto del artículo 4.3.2 exija certificación plena." };
}

function renderResult() {
  const result = resultData(); setNorm(result.norm, "La referencia se muestra con el resultado de la ruta seleccionada.");
  ui.resultView.className = `result-view ${result.type}`;
  ui.resultBadge.textContent = result.type === "applies" ? "Certificación plena" : result.type === "no-literal" ? "Umbral no activado" : result.type === "review" ? "Escalar" : "Pendiente";
  ui.resultTitle.textContent = result.title; ui.resultText.textContent = result.text; ui.resultParameter.textContent = result.parameter;
  ui.resultValue.textContent = result.value; ui.resultNorm.textContent = result.norm; ui.resultAction.textContent = result.action;
}

function updateDiagram() {
  const mode = state.parameter === "installable" ? "grid" : state.parameter === "installed" ? "island" : "idle";
  let verdict = "pending";
  let verdictText = "Seleccione una opción para comenzar";
  let literalText = "El numeral aparecerá durante la ruta.";
  let icon = "?";

  ui.diagramConnection.textContent = mode === "grid" ? "Red con excedentes" : mode === "island" ? "Sistema aislado" : "Por definir";
  ui.diagramParameter.textContent = mode === "grid" ? "Capacidad instalable" : mode === "island" ? "Potencia instalada / inversor limitante" : "Por definir";
  ui.diagramValue.textContent = Number.isFinite(state.value) ? formatKva(state.value) : "Sin dato";

  if (screen === "outside-route") {
    verdict = "review";
    verdictText = "Requiere confirmación técnica";
    literalText = "Revisión integral del artículo 4.3.2.";
    icon = "!";
  } else if (screen === "classified") {
    if (state.value >= 10) {
      verdict = "applies";
      verdictText = "Requiere certificación plena";
      icon = "✓";
    } else {
      verdict = "no-literal";
      verdictText = `No activa el ${state.literal.toLowerCase()} por umbral`;
      icon = "<";
    }
    literalText = `Artículo 4.3.2.1, ${state.literal.toLowerCase()}.`;
  } else if (screen === "unverified") {
    verdictText = state.value >= 10 ? "Alcanza 10 kVA; falta soporte" : "Bajo 10 kVA; falta soporte";
    literalText = `Artículo 4.3.2.1, ${state.literal.toLowerCase()}.`;
    icon = "!";
  } else if (Number.isFinite(state.value)) {
    verdictText = state.value >= 10 ? "Umbral alcanzado; verifique la evidencia" : "Bajo el umbral; verifique la evidencia";
    literalText = `Artículo 4.3.2.1, ${state.literal.toLowerCase()}.`;
    icon = state.value >= 10 ? "≥" : "<";
  } else if (state.parameter) {
    verdictText = "Ingrese la capacidad en kVA";
    literalText = `Artículo 4.3.2.1, ${state.literal.toLowerCase()}.`;
    icon = "…";
  }

  ui.diagramCanvas.className = `diagram-canvas mode-${mode} verdict-${verdict}`;
  ui.diagramVerdict.classList.toggle("threshold-reached", Number.isFinite(state.value) && state.value >= 10 && verdict === "pending");
  ui.diagramVerdictIcon.textContent = icon;
  ui.diagramVerdictText.textContent = verdictText;
  ui.diagramLiteral.textContent = literalText;
}

function updateProgress() {
  const positions = { connection: 1, capacity: 2, evidence: 3 };
  const total = 3; const current = positions[screen] || total;
  ui.stepLabel.textContent = ["outside-route", "classified", "unverified"].includes(screen) ? "Resultado" : `Paso ${current} de ${total}`;
  ui.progressBar.style.width = `${Math.min(100, (current / total) * 100)}%`;
}

function updateSummary() {
  const parts = [];
  if (state.connected === true) parts.push("Conectado a red"); if (state.connected === false) parts.push("Sin conexión a red");
  if (state.exports === true) parts.push("con excedentes"); if (state.exports === false && state.connected === true) parts.push("sin excedentes");
  if (state.parameter === "installable") parts.push("capacidad instalable"); if (state.parameter === "installed") parts.push("potencia instalada");
  if (Number.isFinite(state.value)) parts.push(formatKva(state.value)); ui.routeSummary.textContent = parts.length ? parts.join(" · ") : "Aún no se ha seleccionado una ruta.";
}

function render() {
  ui.answerArea.replaceChildren(); const isResult = ["outside-route", "classified", "unverified"].includes(screen);
  ui.questionView.hidden = isResult; ui.resultView.hidden = !isResult; ui.backButton.hidden = history.length === 0;
  if (screen === "connection") renderConnection(); if (screen === "capacity") renderCapacity(); if (screen === "evidence") renderEvidence(); if (isResult) renderResult();
  updateProgress(); updateDiagram(); updateSummary();
}

function goBack() { const previous = history.pop(); if (!previous) return; screen = previous.screen; state = previous.state; render(); }
function reset() { state = initialState(); screen = "connection"; history = []; render(); document.querySelector("#ruta").scrollIntoView({ behavior: "smooth", block: "start" }); }
function currentSummary() {
  if (!["outside-route", "classified", "unverified"].includes(screen)) return null;
  const result = resultData(); return { project: document.querySelector("#projectName").value.trim(), client: document.querySelector("#clientName").value.trim(), result: result.title, parameter: result.parameter, value: result.value, numeral: result.norm, nextAction: result.action };
}
function registerWebMcp() {
  const context = document.modelContext; if (!context?.registerTool) return; const signal = new AbortController().signal;
  Promise.resolve(context.registerTool({ name: "get_current_retie_route_result", title: "Consultar resultado de la ruta RETIE", description: "Devuelve el resultado que se muestra actualmente en la ruta guiada.", inputSchema: { type: "object", properties: {}, additionalProperties: false }, annotations: { readOnlyHint: true, untrustedContentHint: true }, execute() { return currentSummary() || { status: "incomplete", nextQuestion: ui.questionText.textContent }; } }, { signal })).catch(() => {});
}

ui.backButton.addEventListener("click", goBack);
document.querySelector("#resetButton").addEventListener("click", reset);
document.querySelector("#newCaseButton").addEventListener("click", reset);
document.querySelector("#printButton").addEventListener("click", () => window.print());
render(); registerWebMcp();
