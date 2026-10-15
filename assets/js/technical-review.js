(() => {
  const form = document.getElementById('technicalReviewForm');
  if (!form) return;

  const data = JSON.parse(document.getElementById('technicalReviewData')?.textContent || '{}');
  const tabs = [...form.querySelectorAll('[data-review-tab]')];
  const panels = [...form.querySelectorAll('[data-review-panel]')];
  const isoFile = document.getElementById('reviewIsoFile');
  const existingIso = document.getElementById('existingIsoAttachment');
  const analyzeIsoButton = document.getElementById('analyzeIsoCertificate');
  const isoStatus = document.getElementById('isoAnalysisStatus');
  const isoResult = document.getElementById('isoAnalysisResult');
  const isoJsonInput = document.getElementById('isoAnalysisJson');
  const timesJsonInput = document.getElementById('evaluationTimesJson');
  const daysBaseInput = document.getElementById('daysBaseInput');
  const daysAdjustedInput = document.getElementById('daysAdjustedInput');
  const safetyFactor = document.getElementById('safetyFactor');
  const calculateButton = document.getElementById('calculateEvaluationTimes');
  const evaluationResults = document.getElementById('evaluationResults');
  const evaluationBreakdown = document.getElementById('evaluationBreakdown');
  const baseDaysOutput = document.getElementById('baseDaysOutput');
  const adjustedDaysOutput = document.getElementById('adjustedDaysOutput');
  const timeControls = {
    evaluacion_inicial: document.getElementById('timeInitial'),
    sistema_gestion: document.getElementById('timeManagement'),
    evaluacion_laboratorio: document.getElementById('timeLaboratory'),
    atestiguamiento: document.getElementById('timeWitness'),
    analisis_resultados: document.getElementById('timeAnalysis'),
  };

  const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

  const openTab = (name) => {
    tabs.forEach((tab) => tab.classList.toggle('active', tab.dataset.reviewTab === name));
    panels.forEach((panel) => {
      const active = panel.dataset.reviewPanel === name;
      panel.hidden = !active;
      panel.classList.toggle('active', active);
    });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  tabs.forEach((tab) => tab.addEventListener('click', () => openTab(tab.dataset.reviewTab)));
  form.querySelectorAll('[data-open-review-tab]').forEach((button) => button.addEventListener('click', () => openTab(button.dataset.openReviewTab)));

  const setIsoStatus = (type, message) => {
    isoStatus.hidden = false;
    isoStatus.className = `analysis-status ${type}`;
    isoStatus.textContent = message;
  };

  const renderIsoAnalysis = (payload) => {
    if (!payload?.certificate || !payload?.validation) return;
    const certificate = payload.certificate;
    const validation = payload.validation;
    const resultLabels = {
      conforme: 'Conforme',
      alerta: 'Requiere revisión',
      no_conforme: 'No conforme',
    };
    const resultClass = validation.resultado === 'conforme' ? 'conforme' : validation.resultado === 'no_conforme' ? 'no-conforme' : 'alerta';
    const alerts = validation.alertas || [];
    isoResult.className = `iso-analysis-result ${resultClass}`;
    isoResult.innerHTML = `
      <div class="iso-result-heading"><span>${escapeHtml(payload.mode === 'gemini_document' ? 'Análisis documental + visión' : 'Análisis documental local')}</span><strong>${escapeHtml(resultLabels[validation.resultado] || 'Revisar')}</strong></div>
      <dl>
        <div><dt>Titular</dt><dd>${escapeHtml(certificate.titular || 'No identificado')}</dd></div>
        <div><dt>Norma</dt><dd>${escapeHtml(certificate.norma || 'No identificada')}</dd></div>
        <div><dt>Vencimiento</dt><dd>${escapeHtml(certificate.fecha_vencimiento || 'No identificado')}</dd></div>
        <div><dt>Certificador / acreditador</dt><dd>${escapeHtml([certificate.certificador, certificate.acreditador].filter(Boolean).join(' / ') || 'No identificado')}</dd></div>
        <div class="wide"><dt>Alcance</dt><dd>${escapeHtml(certificate.alcance || 'No identificado')}</dd></div>
      </dl>
      ${alerts.length ? `<ul>${alerts.map((alert) => `<li>${escapeHtml(alert)}</li>`).join('')}</ul>` : '<p>El titular, la vigencia y el alcance son compatibles con la solicitud.</p>'}
      ${payload.warning ? `<small>${escapeHtml(payload.warning)}</small>` : ''}`;
    isoResult.hidden = false;
  };

  analyzeIsoButton?.addEventListener('click', async () => {
    const file = isoFile?.files?.[0];
    const attachmentId = existingIso?.value || '';
    if (!file && !attachmentId) {
      setIsoStatus('error', 'Adjunta un certificado ISO o selecciona uno ya aportado.');
      return;
    }
    const payload = new FormData();
    payload.append('csrf_token', form.querySelector('[name="csrf_token"]').value);
    payload.append('solicitud_id', form.querySelector('[name="solicitud_id"]').value);
    if (attachmentId) payload.append('adjunto_id', attachmentId);
    if (file) payload.append('certificado_iso', file);
    analyzeIsoButton.disabled = true;
    setIsoStatus('loading', 'Python está validando titular, vigencia y alcance del certificado...');
    try {
      const response = await fetch('api/analyze-iso-certificate.php', { method: 'POST', body: payload });
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.message || 'No fue posible analizar el certificado.');
      isoJsonInput.value = JSON.stringify(result);
      renderIsoAnalysis(result);
      setIsoStatus('success', 'Análisis terminado. Confirma el dictamen antes de aprobar la revisión.');
      const suggestedValue = result.validation?.resultado === 'conforme' ? 'SI' : result.validation?.resultado === 'no_conforme' ? 'NO' : '';
      if (suggestedValue) {
        const radio = form.querySelector(`[name="iso_conforme"][value="${suggestedValue}"]`);
        if (radio) radio.checked = true;
      }
      if (file && result.attachment_id && existingIso) {
        const option = document.createElement('option');
        option.value = result.attachment_id;
        option.textContent = file.name;
        option.selected = true;
        existingIso.append(option);
        isoFile.value = '';
      }
    } catch (error) {
      setIsoStatus('error', error.message || 'No fue posible analizar el certificado.');
    } finally {
      analyzeIsoButton.disabled = false;
    }
  });

  const schemeKey = String(data.scheme || '').toUpperCase().replace(/^ESQUEMA\s+/, '').replaceAll('.', '').replace(/[\s-]+/g, '_');
  const labels = {
    evaluacion_inicial: 'Evaluación inicial',
    sistema_gestion: 'Evaluación del sistema de gestión',
    evaluacion_laboratorio: 'Evaluación de laboratorio',
    atestiguamiento: 'Atestiguamiento de ensayos',
    analisis_resultados: 'Análisis de resultados',
    comite_certificacion: 'Comité de certificación',
  };

  const selectedLevel = (select) => select.options[select.selectedIndex]?.text || '';
  const round = (value) => Math.round(value * 100) / 100;

  const calculateTimes = () => {
    const safety = Math.max(0, Math.min(50, Number(safetyFactor.value) || 0));
    const factors = {
      evaluacion_inicial: schemeKey.startsWith('1B') ? 1.2 : 1,
      sistema_gestion: schemeKey.startsWith('4') ? 1.3 : 1,
      evaluacion_laboratorio: 1,
      atestiguamiento: schemeKey.startsWith('1B') ? 1.2 : 1,
      analisis_resultados: schemeKey.startsWith('5') ? 1.2 : 1,
    };
    const times = {};
    let baseWithoutCommittee = 0;
    let adjustedWithoutCommittee = 0;
    Object.entries(timeControls).forEach(([key, control]) => {
      let base = Number(control.value) || 0;
      if (key === 'sistema_gestion' && schemeKey.startsWith('1B')) base = 0;
      const schemeAdjusted = base * factors[key];
      const adjusted = schemeAdjusted * (1 + safety / 100);
      times[key] = {
        level: key === 'sistema_gestion' && schemeKey.startsWith('1B') ? 'No aplica al esquema 1B' : selectedLevel(control),
        base: round(base),
        scheme_factor: factors[key],
        adjusted: round(adjusted),
      };
      baseWithoutCommittee += schemeAdjusted;
      adjustedWithoutCommittee += adjusted;
    });
    times.comite_certificacion = { level: 'Fijo', base: 1, scheme_factor: 1, adjusted: 1 };
    const totalBase = round(baseWithoutCommittee + 1);
    const totalAdjusted = round(adjustedWithoutCommittee + 1);
    timesJsonInput.value = JSON.stringify(times);
    daysBaseInput.value = String(totalBase);
    daysAdjustedInput.value = String(totalAdjusted);
    baseDaysOutput.textContent = totalBase.toFixed(1);
    adjustedDaysOutput.textContent = `${totalAdjusted.toFixed(1)} días`;
    evaluationBreakdown.innerHTML = Object.entries(times).map(([key, value]) => `
      <tr><td><strong>${escapeHtml(labels[key])}</strong></td><td>${escapeHtml(value.level)}</td><td>${Number(value.base).toFixed(1)}</td><td>${Number(value.scheme_factor) > 1 ? `+${Math.round((Number(value.scheme_factor) - 1) * 100)}%` : '-'}</td><td>${Number(value.adjusted).toFixed(1)}</td></tr>`).join('');
    evaluationResults.hidden = false;
  };

  calculateButton.addEventListener('click', calculateTimes);
  Object.values(timeControls).forEach((control) => control.addEventListener('change', calculateTimes));
  safetyFactor.addEventListener('change', calculateTimes);

  const storedTimes = data.times || {};
  Object.entries(timeControls).forEach(([key, control]) => {
    const storedBase = storedTimes[key]?.base;
    if (storedBase !== undefined) control.value = String(storedBase);
  });
  if (Object.keys(storedTimes).length) calculateTimes();
  if (data.isoAnalysis?.validation) renderIsoAnalysis(data.isoAnalysis);

  form.addEventListener('submit', (event) => {
    if (!timesJsonInput.value) calculateTimes();
    const invalid = form.querySelector(':invalid');
    if (!invalid) return;
    event.preventDefault();
    const panel = invalid.closest('[data-review-panel]');
    if (panel) openTab(panel.dataset.reviewPanel);
    window.setTimeout(() => invalid.reportValidity(), 150);
  });
})();
