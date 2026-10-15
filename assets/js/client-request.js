(() => {
  const form = document.getElementById('assistedRequestForm');
  if (!form) return;

  const dataElement = document.getElementById('requestCatalogData');
  const requestData = JSON.parse(dataElement?.textContent || '{"catalog":[],"regulations":[]}');
  const catalog = requestData.catalog || [];
  const regulations = requestData.regulations || [];
  const serviceRules = requestData.serviceRules || {};
  const purposeRules = serviceRules.purposes || {};
  const recommendationRules = serviceRules.recommendations || {};
  const isoStatusLabels = serviceRules.iso_statuses || {};
  const schemeHelpRules = serviceRules.scheme_help || {};
  const technicalConfig = requestData.technicalFields || {};
  const technicalGroups = technicalConfig.groups || {};
  const technicalProfiles = technicalConfig.profiles || {};
  const regulationMap = new Map(regulations.map((item) => [item.codigo, item]));
  const accreditedRegulationCodes = new Set(['RETIE_40117', 'RETILAP_40150']);

  const stages = [...form.querySelectorAll('[data-stage]')];
  const progressSteps = [...form.querySelectorAll('[data-step]')];
  const previousButton = document.getElementById('previousStep');
  const nextButton = document.getElementById('nextStep');
  const submitButton = document.getElementById('submitRequest');
  const stepMessage = document.getElementById('stepMessage');
  const hasRepresentative = document.getElementById('hasRepresentative');
  const representativeFields = document.getElementById('representativeFields');
  const serviceRegulation = document.getElementById('serviceRegulation');
  const serviceScheme = document.getElementById('serviceScheme');
  const servicePurpose = document.getElementById('servicePurpose');
  const schemeHelpToggle = document.getElementById('schemeHelpToggle');
  const schemeHelpPanel = document.getElementById('schemeHelpPanel');
  const schemeHelpTitle = document.getElementById('schemeHelpTitle');
  const schemeHelpSummary = document.getElementById('schemeHelpSummary');
  const schemeHelpApplies = document.getElementById('schemeHelpApplies');
  const schemeHelpValidity = document.getElementById('schemeHelpValidity');
  const schemeHelpFollowUp = document.getElementById('schemeHelpFollowUp');
  const schemeHelpConditions = document.getElementById('schemeHelpConditions');
  const serviceScopePreview = document.getElementById('serviceScopePreview');
  const isoValidityField = document.getElementById('isoValidityField');
  const isoCertificateField = document.getElementById('isoCertificateField');
  const isoExpiryAlert = document.getElementById('isoExpiryAlert');
  const schemeRecommendation = document.getElementById('schemeRecommendation');
  const recommendationIso = document.getElementById('recommendationIso');
  const recommendationScheme = document.getElementById('recommendationScheme');
  const recommendationResult = document.getElementById('recommendationResult');
  const recommendationOptional = document.getElementById('recommendationOptional');
  const schemeWarningAccepted = document.getElementById('schemeWarningAccepted');
  const schemeWarningDialog = document.getElementById('schemeWarningDialog');
  const schemeWarningText = document.getElementById('schemeWarningText');
  const reviewSchemeButton = document.getElementById('reviewSchemeButton');
  const continueSchemeButton = document.getElementById('continueSchemeButton');
  const selectedServiceSummary = document.getElementById('selectedServiceSummary');
  const retieFallback = document.getElementById('retieClassificationFallback');
  const scopeClassificationKicker = document.getElementById('scopeClassificationKicker');
  const scopeFamilyLabel = document.getElementById('scopeFamilyLabel');
  const scopeProductLabel = document.getElementById('scopeProductLabel');
  const scopeSubfamilyLabel = document.getElementById('scopeSubfamilyLabel');
  const retieFamilySelect = document.getElementById('retieFamilySelect');
  const retieSubfamilySelect = document.getElementById('retieSubfamilySelect');
  const scopeTechnicalSubfamilySelect = document.getElementById('scopeTechnicalSubfamilySelect');
  const retieClassificationDetail = document.getElementById('retieClassificationDetail');
  const retieClassificationCount = document.getElementById('retieClassificationCount');
  const useRetieClassification = document.getElementById('useRetieClassification');
  const manualCatalogBlock = document.getElementById('manualCatalogBlock');
  const manualSearch = document.getElementById('manualCatalogSearch');
  const manualFamily = document.getElementById('manualCatalogFamily');
  const manualProduct = document.getElementById('manualCatalogProduct');
  const manualDetail = document.getElementById('manualCatalogDetail');
  const manualCount = document.getElementById('manualCatalogCount');
  const useManualButton = document.getElementById('useManualProduct');
  const productName = document.getElementById('productName');
  const productImage = document.getElementById('productImage');
  const productDatasheet = document.getElementById('productDatasheet');
  const preview = document.getElementById('uploadPreview');
  const previewImage = document.getElementById('productImagePreview');
  const previewName = document.getElementById('previewProductName');
  const previewFiles = document.getElementById('previewFileNames');
  const analyzeButton = document.getElementById('analyzeProduct');
  const analysisStatus = document.getElementById('analysisStatus');
  const analysisMode = document.getElementById('analysisMode');
  const analysisWarning = document.getElementById('analysisWarning');
  const candidateList = document.getElementById('analysisCandidates');
  const identifiedClassification = document.getElementById('identifiedClassification');
  const technicalFields = document.getElementById('technicalFields');
  const resultScheme = document.getElementById('resultScheme');
  const quantityInput = document.getElementById('analysisQuantity');
  const quantityField = document.getElementById('analysisQuantityField');
  const addProductButton = document.getElementById('addAnalyzedProduct');
  const basketItems = document.getElementById('productBasketItems');
  const basketEmpty = document.getElementById('productBasketEmpty');
  const productCount = document.getElementById('productCount');
  const hiddenInputs = document.getElementById('productHiddenInputs');
  const finalSummary = document.getElementById('finalSummary');
  const commercialWrap = document.getElementById('commercialSelectWrap');
  const managerNote = document.getElementById('managerNote');
  const clientIsoCertificate = document.getElementById('clientIsoCertificate');
  const clientTestReports = document.getElementById('clientTestReports');
  const clientIsoFileName = document.getElementById('clientIsoFileName');
  const clientTestReportNames = document.getElementById('clientTestReportNames');
  const datasheetChecklistStatus = document.getElementById('datasheetChecklistStatus');
  const photoChecklistStatus = document.getElementById('photoChecklistStatus');
  const isoChecklistStatus = document.getElementById('isoChecklistStatus');
  const testReportChecklistStatus = document.getElementById('testReportChecklistStatus');

  let currentStage = 0;
  let currentAnalysis = null;
  let selectedCandidate = null;
  let manualCandidate = null;
  let preferredCatalogId = 0;
  let manualRetieClassification = null;
  let previewUrl = '';
  const products = [];

  const escapeHtml = (value) => String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');

  const normalize = (value) => String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, ' ')
    .trim();

  const showStepMessage = (message) => {
    stepMessage.textContent = message;
    if (message) window.setTimeout(() => {
      if (stepMessage.textContent === message) stepMessage.textContent = '';
    }, 4000);
  };

  const selectedRegulation = () => regulationMap.get(serviceRegulation.value) || null;
  const selectedScheme = () => serviceScheme.value;
  const selectedIsoStatus = () => form.querySelector('input[name="iso_9001_vigente"]:checked')?.value || '';
  const schemeRuleKey = (scheme) => String(scheme || '')
    .replace(/^Esquema\s+/i, '')
    .replaceAll('.', '')
    .trim()
    .replace(/[\s-]+/g, '_')
    .toUpperCase();

  const renderSchemeHelp = () => {
    const scheme = selectedScheme();
    const help = schemeHelpRules[schemeRuleKey(scheme)];
    schemeHelpToggle.disabled = !help;
    if (!help) {
      schemeHelpPanel.hidden = true;
      schemeHelpToggle.setAttribute('aria-expanded', 'false');
      return;
    }
    schemeHelpTitle.textContent = scheme;
    schemeHelpSummary.textContent = help.summary || '';
    schemeHelpApplies.textContent = help.applies || '';
    schemeHelpValidity.textContent = help.validity || '';
    schemeHelpFollowUp.textContent = help.follow_up || '';
    schemeHelpConditions.textContent = help.conditions || '';
  };

  schemeHelpToggle.addEventListener('click', () => {
    const help = schemeHelpRules[schemeRuleKey(selectedScheme())];
    if (!help) return;
    schemeHelpPanel.hidden = !schemeHelpPanel.hidden;
    schemeHelpToggle.setAttribute('aria-expanded', String(!schemeHelpPanel.hidden));
  });

  const renderTechnicalFields = (values = {}) => {
    technicalFields.innerHTML = '';
    const groupKeys = [...(technicalProfiles[serviceRegulation.value] || technicalConfig.default_profile || ['common', 'traceability'])];
    if (schemeRuleKey(selectedScheme()).startsWith('1B') && technicalGroups.batch) {
      groupKeys.splice(Math.min(1, groupKeys.length), 0, 'batch');
    }
    groupKeys.forEach((groupKey) => {
      const group = technicalGroups[groupKey];
      if (!group) return;
      const section = document.createElement('section');
      section.className = 'technical-field-group';
      const heading = document.createElement('div');
      heading.className = 'technical-group-heading';
      const title = document.createElement('h4');
      title.textContent = group.label || groupKey;
      const hasRequiredFields = (group.fields || []).some((definition) => Boolean(definition.required));
      const status = document.createElement('span');
      status.textContent = hasRequiredFields ? 'Obligatorio' : 'Opcional';
      status.className = hasRequiredFields ? 'required' : 'optional';
      heading.append(title, status);
      const note = document.createElement('p');
      note.className = 'technical-group-note';
      note.textContent = group.note || '';
      const grid = document.createElement('div');
      grid.className = 'field-grid compact-fields';
      (group.fields || []).forEach((definition) => {
        const label = document.createElement('label');
        if (definition.wide) label.classList.add('span-two');
        label.append(document.createTextNode(`${definition.label || definition.key}${definition.required ? ' *' : ''}`));
        let control;
        if (definition.type === 'select') {
          control = document.createElement('select');
          const placeholder = document.createElement('option');
          placeholder.value = '';
          placeholder.textContent = 'Selecciona';
          control.append(placeholder);
          (definition.options || []).forEach((value) => {
            const option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            control.append(option);
          });
        } else if (definition.type === 'textarea') {
          control = document.createElement('textarea');
          control.rows = 2;
        } else {
          control = document.createElement('input');
          control.type = definition.type === 'date' ? 'date' : 'text';
        }
        control.dataset.analysisField = definition.key;
        control.required = Boolean(definition.required);
        const value = String(values[definition.key] || '');
        control.value = definition.type === 'date' && value && !/^\d{4}-\d{2}-\d{2}$/.test(value) ? '' : value;
        label.append(control);
        grid.append(label);
      });
      section.append(heading);
      if (note.textContent) section.append(note);
      section.append(grid);
      technicalFields.append(section);
    });
  };

  const renderPurposeOptions = () => {
    const previousPurpose = servicePurpose.value;
    const purposes = purposeRules[schemeRuleKey(selectedScheme())] || [];
    servicePurpose.innerHTML = `<option value="">${purposes.length ? 'Selecciona' : 'Selecciona primero el esquema'}</option>`;
    purposes.forEach((purpose) => {
      const option = document.createElement('option');
      option.value = purpose;
      option.textContent = purpose;
      servicePurpose.append(option);
    });
    servicePurpose.disabled = purposes.length === 0;
    if (purposes.includes(previousPurpose)) servicePurpose.value = previousPurpose;
    else if (purposes.length === 1) servicePurpose.selectedIndex = 1;
  };

  const localDateFromInput = (value) => value ? new Date(`${value}T00:00:00`) : null;

  const renderIsoExpiryAlert = () => {
    const validityInput = isoValidityField.querySelector('input');
    const expiry = localDateFromInput(validityInput.value);
    isoExpiryAlert.hidden = true;
    isoExpiryAlert.textContent = '';
    isoExpiryAlert.classList.remove('is-expired');
    validityInput.setCustomValidity('');
    if (selectedIsoStatus() !== 'certificacion_vigente' || !expiry || Number.isNaN(expiry.getTime())) return;

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const threeMonthsFromToday = new Date(today);
    threeMonthsFromToday.setMonth(threeMonthsFromToday.getMonth() + 3);
    const daysRemaining = Math.ceil((expiry.getTime() - today.getTime()) / 86400000);
    const formattedExpiry = new Intl.DateTimeFormat('es-CO', { dateStyle: 'long' }).format(expiry);

    if (daysRemaining < 0) {
      isoExpiryAlert.textContent = `El certificado ISO 9001 venció el ${formattedExpiry}. Para declarar una certificación vigente, registra una fecha de vigencia válida.`;
      isoExpiryAlert.classList.add('is-expired');
      isoExpiryAlert.hidden = false;
      validityInput.setCustomValidity('La vigencia del certificado ISO 9001 no puede estar vencida.');
      return;
    }
    if (expiry <= threeMonthsFromToday) {
      const remainingText = daysRemaining === 0 ? 'vence hoy' : `vence en ${daysRemaining} día${daysRemaining === 1 ? '' : 's'}`;
      isoExpiryAlert.textContent = `Atención: el certificado ISO 9001 ${remainingText}, el ${formattedExpiry}. Estos procesos suelen tardar entre dos y tres meses; considera renovarlo para evitar demoras o inconvenientes durante la certificación.`;
      isoExpiryAlert.hidden = false;
    }
  };

  const renderIsoFields = () => {
    const showCertificate = selectedIsoStatus() === 'certificacion_vigente';
    const validityInput = isoValidityField.querySelector('input');
    const certificateInput = isoCertificateField.querySelector('input');
    const today = new Date();
    validityInput.min = [
      today.getFullYear(),
      String(today.getMonth() + 1).padStart(2, '0'),
      String(today.getDate()).padStart(2, '0'),
    ].join('-');
    isoValidityField.hidden = !showCertificate;
    isoCertificateField.hidden = !showCertificate;
    validityInput.required = showCertificate;
    certificateInput.required = showCertificate;
    if (!showCertificate) {
      validityInput.value = '';
      certificateInput.value = '';
    }
    renderIsoExpiryAlert();
  };

  const renderRecommendation = () => {
    const isoStatus = selectedIsoStatus();
    const scheme = selectedScheme();
    const recommendation = recommendationRules[isoStatus]?.[schemeRuleKey(scheme)];
    if (!isoStatus || !scheme || !recommendation) {
      schemeRecommendation.hidden = true;
      return;
    }
    recommendationIso.textContent = isoStatusLabels[isoStatus] || isoStatus;
    recommendationScheme.textContent = scheme;
    recommendationResult.textContent = recommendation.recommended || '';
    recommendationOptional.textContent = recommendation.optional || '';
    recommendationOptional.hidden = !recommendation.optional;
    schemeWarningAccepted.value = '0';
    schemeRecommendation.hidden = false;
  };

  const renderSelectedService = () => {
    const regulation = selectedRegulation();
    const scheme = selectedScheme();
    selectedServiceSummary.innerHTML = regulation ? `
      <div><span>Alcance seleccionado</span><strong>${escapeHtml(regulation.nombre)}</strong></div>
      <div><span>Esquema</span><strong>${escapeHtml(scheme)}</strong></div>` : '';
  };

  const activateStage = (index) => {
    currentStage = Math.max(0, Math.min(index, stages.length - 1));
    stages.forEach((stage, stageIndex) => {
      const active = stageIndex === currentStage;
      stage.hidden = !active;
      stage.classList.toggle('active', active);
    });
    progressSteps.forEach((step, stepIndex) => {
      step.classList.toggle('active', stepIndex === currentStage);
      step.classList.toggle('complete', stepIndex < currentStage);
    });
    previousButton.hidden = currentStage === 0;
    nextButton.hidden = currentStage === stages.length - 1;
    submitButton.hidden = currentStage !== stages.length - 1;
    if (currentStage === 2) {
      renderSelectedService();
      refreshManualCatalog(true);
    }
    if (currentStage === 3) renderFinalSummary();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const validateRequiredFields = (container) => {
    const fields = [...container.querySelectorAll('input[required], select[required], textarea[required]')];
    for (const field of fields) {
      if (!field.checkValidity()) {
        field.reportValidity();
        return false;
      }
    }
    return true;
  };

  const validateStage = (index) => {
    if (index === 2 && products.length === 0) {
      showStepMessage('Valida y agrega al menos un producto.');
      return false;
    }
    if (!validateRequiredFields(stages[index])) return false;
    if (index === 3) {
      const attended = form.querySelector('input[name="atendido_comercial"]:checked');
      const commercial = form.querySelector('select[name="comercial_id"]');
      if (attended?.value === '1' && !commercial.value) {
        commercial.reportValidity();
        showStepMessage('Selecciona el comercial que te atendio.');
        return false;
      }
    }
    return true;
  };

  const setDocumentStatus = (element, text, state) => {
    if (!element) return;
    element.textContent = text;
    element.className = `document-status ${state}`;
  };

  const renderClientDocumentChecklist = () => {
    const photoCount = products.filter((product) => product.hasImage).length;
    setDocumentStatus(
      datasheetChecklistStatus,
      products.length ? `${products.length} aportada${products.length === 1 ? '' : 's'}` : 'Pendiente',
      products.length ? 'provided' : 'pending',
    );
    setDocumentStatus(
      photoChecklistStatus,
      photoCount ? `${photoCount} aportada${photoCount === 1 ? '' : 's'}` : 'Opcional',
      photoCount ? 'provided' : 'optional',
    );
    setDocumentStatus(
      isoChecklistStatus,
      clientIsoCertificate?.files?.length ? 'Aportado' : 'Opcional',
      clientIsoCertificate?.files?.length ? 'provided' : 'optional',
    );
    const reportCount = clientTestReports?.files?.length || 0;
    setDocumentStatus(
      testReportChecklistStatus,
      reportCount ? `${reportCount} aportado${reportCount === 1 ? '' : 's'}` : 'Opcional',
      reportCount ? 'provided' : 'optional',
    );
    if (clientIsoFileName) {
      clientIsoFileName.textContent = clientIsoCertificate?.files?.[0]?.name || 'Sin archivo seleccionado';
    }
    if (clientTestReportNames) {
      clientTestReportNames.textContent = reportCount
        ? [...clientTestReports.files].map((file) => file.name).join(', ')
        : 'Sin archivos seleccionados';
    }
  };

  const recommendationNeedsConfirmation = () => {
    const recommendation = recommendationRules[selectedIsoStatus()]?.[schemeRuleKey(selectedScheme())];
    return Boolean(recommendation?.optional);
  };

  const advanceFromCurrentStage = (target = currentStage + 1) => {
    if (!validateStage(currentStage)) return;
    if (currentStage === 1 && recommendationNeedsConfirmation() && schemeWarningAccepted.value !== '1') {
      schemeWarningText.textContent = recommendationOptional.textContent;
      if (typeof schemeWarningDialog.showModal === 'function') schemeWarningDialog.showModal();
      else if (window.confirm(`${recommendationOptional.textContent}\n\n¿Deseas continuar con la solicitud así?`)) {
        schemeWarningAccepted.value = '1';
        activateStage(target);
      }
      return;
    }
    activateStage(target);
  };

  nextButton.addEventListener('click', () => advanceFromCurrentStage());
  previousButton.addEventListener('click', () => activateStage(currentStage - 1));
  progressSteps.forEach((step) => step.addEventListener('click', () => {
    const target = Number(step.dataset.step);
    if (target <= currentStage) activateStage(target);
    else if (target === currentStage + 1) advanceFromCurrentStage(target);
  }));
  reviewSchemeButton.addEventListener('click', () => {
    schemeWarningDialog.close();
    (form.querySelector('input[name="iso_9001_vigente"]:checked') || serviceScheme).focus();
  });
  continueSchemeButton.addEventListener('click', () => {
    schemeWarningAccepted.value = '1';
    schemeWarningDialog.close();
    activateStage(2);
  });

  hasRepresentative.addEventListener('change', () => {
    representativeFields.hidden = !hasRepresentative.checked;
    representativeFields.querySelectorAll('[data-representative-required]').forEach((field) => {
      field.required = hasRepresentative.checked;
    });
  });

  const clearProductsForServiceChange = () => {
    if (!products.length) return;
    products.splice(0, products.length);
    renderProducts();
    showStepMessage('Se limpiaron los productos porque cambio el alcance o el esquema.');
  };

  const renderScopePreview = () => {
    const regulation = selectedRegulation();
    const scheme = selectedScheme();
    if (!regulation) {
      serviceScopePreview.hidden = true;
      return;
    }
    const compatible = catalog.filter((item) => item.reglamento_codigo === regulation.codigo
      && (!scheme || (item.esquemas || []).includes(scheme)));
    serviceScopePreview.hidden = false;
    serviceScopePreview.innerHTML = `
      <div><span>Alcance</span><strong>${escapeHtml(regulation.nombre)}</strong></div>
      <div><span>País</span><strong>${escapeHtml(regulation.pais || 'Internacional')}</strong></div>
      <div><span>Categorías compatibles</span><strong>${compatible.length}</strong></div>`;
  };

  serviceRegulation.addEventListener('change', () => {
    clearProductsForServiceChange();
    resetAnalyzer();
    const regulation = selectedRegulation();
    serviceScheme.innerHTML = '<option value="">Selecciona el esquema</option>';
    (regulation?.esquemas || []).forEach((scheme) => {
      const option = document.createElement('option');
      option.value = scheme;
      option.textContent = scheme;
      serviceScheme.append(option);
    });
    serviceScheme.disabled = !regulation;
    if ((regulation?.esquemas || []).length === 1) serviceScheme.selectedIndex = 1;
    renderPurposeOptions();
    renderSchemeHelp();
    renderRecommendation();
    renderTechnicalFields();
    renderScopePreview();
    refreshManualCatalog(true);
    renderRetieFallback(true);
  });
  serviceScheme.addEventListener('change', () => {
    clearProductsForServiceChange();
    resetAnalyzer();
    renderPurposeOptions();
    renderSchemeHelp();
    renderRecommendation();
    renderTechnicalFields();
    renderScopePreview();
    refreshManualCatalog(true);
    renderRetieFallback(false);
  });
  form.querySelectorAll('input[name="iso_9001_vigente"]').forEach((radio) => radio.addEventListener('change', () => {
    renderIsoFields();
    renderRecommendation();
  }));
  isoValidityField.querySelector('input').addEventListener('change', renderIsoExpiryAlert);

  const activateToolTab = (name) => {
    form.querySelectorAll('[data-tool-panel]').forEach((panel) => {
      const active = panel.dataset.toolPanel === name;
      panel.hidden = !active;
      panel.classList.toggle('active', active);
    });
    form.querySelectorAll('.tool-tab[data-tool-tab]').forEach((tab) => {
      tab.classList.toggle('active', tab.dataset.toolTab === name);
    });
  };
  form.querySelectorAll('[data-tool-tab]').forEach((tab) => tab.addEventListener('click', () => {
    if (!tab.disabled) activateToolTab(tab.dataset.toolTab);
  }));

  const compatibleCatalog = () => catalog.filter((item) => item.reglamento_codigo === serviceRegulation.value
    && (item.esquemas || []).includes(serviceScheme.value));

  const accreditedRows = () => accreditedRegulationCodes.has(serviceRegulation.value) ? compatibleCatalog() : [];

  const currentRetieOption = () => {
    const catalogId = Number(retieSubfamilySelect.value || 0);
    if (!catalogId) return null;
    return accreditedRows().find((item) => Number(item.id) === catalogId) || null;
  };

  const scopeClassificationFor = (item) => item?.clasificacion_alcance || null;

  const scopeFamilyValue = (item) => {
    const classification = scopeClassificationFor(item);
    return classification?.categoria || item?.familia_nombre || '';
  };

  const currentTechnicalSubfamily = () => {
    const classification = scopeClassificationFor(currentRetieOption());
    if (!classification) return null;
    return (classification.opciones || []).find((option) => option.id === scopeTechnicalSubfamilySelect.value) || null;
  };

  const renderRetieOptionDetail = () => {
    const selection = currentRetieOption();
    const classification = scopeClassificationFor(selection);
    const technicalSubfamily = currentTechnicalSubfamily();
    manualRetieClassification = null;
    useRetieClassification.disabled = !selection;
    retieClassificationDetail.hidden = !selection;
    if (!selection) {
      retieClassificationDetail.innerHTML = '';
      return;
    }
    const familyTitle = serviceRegulation.value === 'RETILAP_40150' ? 'Categoría RETILAP' : 'Familia oficial RETIE';
    retieClassificationDetail.innerHTML = `
      <strong>${escapeHtml(selection.producto_nombre)}</strong>
      <div class="classification-grid">
        <div><span>${familyTitle}</span><b>${escapeHtml(classification?.familia_oficial || selection.familia_nombre || '-')}</b></div>
        <div><span>Ítem oficial</span><b>${escapeHtml(classification?.item || '-')}</b></div>
        <div><span>Producto acreditado</span><b>${escapeHtml(selection.producto_nombre || '-')}</b></div>
        <div><span>Subfamilia técnica</span><b>${escapeHtml(technicalSubfamily?.subfamilia || technicalSubfamily?.etiqueta || 'Por validar')}</b></div>
        <div><span>Criterio 1</span><b>${escapeHtml(technicalSubfamily?.criterio_1 || '-')}</b></div>
        <div><span>Criterio 2</span><b>${escapeHtml(technicalSubfamily?.criterio_2 || '-')}</b></div>
        <div><span>División NACE</span><b>${escapeHtml(selection.codigo_nace || '-')}</b></div>
        <div><span>Artículo general</span><b>${escapeHtml(selection.articulo || '-')}</b></div>
        <div><span>Subnumeral / ensayos mínimos</span><b>${escapeHtml(selection.numeral || '-')}</b></div>
        <div><span>Fuente de clasificación</span><b>${escapeHtml(`${classification?.fuente || '-'}${technicalSubfamily?.pagina ? ` · pág. ${technicalSubfamily.pagina}` : ''}`)}</b></div>
      </div>`;
  };

  const populateScopeTechnicalSubfamilies = () => {
    scopeTechnicalSubfamilySelect.innerHTML = '<option value="">No la conozco / dejar por validar</option>';
    const selection = currentRetieOption();
    const classification = scopeClassificationFor(selection);
    const options = classification?.opciones || [];
    options.forEach((item) => {
      const option = document.createElement('option');
      option.value = item.id;
      option.textContent = item.etiqueta;
      scopeTechnicalSubfamilySelect.append(option);
    });
    scopeTechnicalSubfamilySelect.disabled = options.length === 0;
    if (selection) {
      retieClassificationCount.textContent = options.length
        ? `${options.length} opción${options.length === 1 ? '' : 'es'} técnica${options.length === 1 ? '' : 's'} compatible${options.length === 1 ? '' : 's'}. Puedes dejarla por validar si no conoces la especificación.`
        : 'Este producto no tiene una subfamilia técnica vinculada; revisa la matriz de alcance.';
    }
    renderRetieOptionDetail();
  };

  const populateRetieSubfamilies = () => {
    retieSubfamilySelect.innerHTML = '<option value="">Selecciona un producto</option>';
    const family = retieFamilySelect.value;
    const rows = accreditedRows()
      .filter((item) => scopeFamilyValue(item) === family)
      .sort((left, right) => {
        const leftItem = Number(scopeClassificationFor(left)?.item || 0);
        const rightItem = Number(scopeClassificationFor(right)?.item || 0);
        if (leftItem && rightItem && leftItem !== rightItem) return leftItem - rightItem;
        return `${left.producto_nombre} ${left.subfamilia_nombre}`.localeCompare(`${right.producto_nombre} ${right.subfamilia_nombre}`, 'es');
      });
    rows.forEach((item) => {
      const option = document.createElement('option');
      option.value = String(item.id);
      const itemNumber = scopeClassificationFor(item)?.item;
      option.textContent = `${itemNumber ? `Ítem ${itemNumber} - ` : ''}${item.producto_nombre} | NACE ${item.codigo_nace || '-'}`;
      retieSubfamilySelect.append(option);
    });
    retieSubfamilySelect.disabled = rows.length === 0;
    const familyCount = new Set(accreditedRows().map(scopeFamilyValue).filter(Boolean)).size;
    const familyTerm = serviceRegulation.value === 'RETILAP_40150' ? 'categoría' : 'familia';
    retieClassificationCount.textContent = family
      ? `${rows.length} producto${rows.length === 1 ? '' : 's'} acreditado${rows.length === 1 ? '' : 's'} en esta ${familyTerm}.`
      : `${familyCount} ${familyTerm}${familyCount === 1 ? '' : 's'} acreditada${familyCount === 1 ? '' : 's'} disponible${familyCount === 1 ? '' : 's'} para este esquema.`;
    populateScopeTechnicalSubfamilies();
  };

  const renderRetieFallback = (reset = false) => {
    const rows = accreditedRows();
    const visible = accreditedRegulationCodes.has(serviceRegulation.value) && rows.length > 0;
    retieFallback.hidden = !visible;
    manualCatalogBlock.hidden = visible;
    if (!visible) {
      manualRetieClassification = null;
      if (reset) {
        retieFamilySelect.innerHTML = '<option value="">Selecciona una familia</option>';
        retieSubfamilySelect.innerHTML = '<option value="">Selecciona un producto</option>';
        scopeTechnicalSubfamilySelect.innerHTML = '<option value="">Selecciona una subfamilia</option>';
      }
      return;
    }
    const isRetilap = serviceRegulation.value === 'RETILAP_40150';
    scopeFamilyLabel.textContent = isRetilap ? 'Categoría RETILAP' : 'Familia acreditada';
    scopeProductLabel.textContent = 'Producto acreditado';
    scopeSubfamilyLabel.textContent = isRetilap ? 'Subfamilia RETILAP' : 'Subfamilia técnica RETIE';
    retieFamilySelect.innerHTML = `<option value="">Selecciona una ${isRetilap ? 'categoría' : 'familia'}</option>`;
    const families = [...new Set(rows.map(scopeFamilyValue).filter(Boolean))]
      .sort((left, right) => left.localeCompare(right, 'es'));
    families.forEach((family) => {
      const option = document.createElement('option');
      option.value = family;
      option.textContent = family;
      retieFamilySelect.append(option);
    });
    scopeClassificationKicker.textContent = isRetilap
      ? 'Clasificación acreditada RETILAP'
      : 'Clasificación acreditada RETIE';
    retieFamilySelect.value = '';
    populateRetieSubfamilies();
  };

  retieFamilySelect.addEventListener('change', populateRetieSubfamilies);
  retieSubfamilySelect.addEventListener('change', populateScopeTechnicalSubfamilies);
  scopeTechnicalSubfamilySelect.addEventListener('change', renderRetieOptionDetail);
  useRetieClassification.addEventListener('click', () => {
    const selection = currentRetieOption();
    const classification = scopeClassificationFor(selection);
    const technicalSubfamily = currentTechnicalSubfamily();
    if (!selection) return;
    manualRetieClassification = {
      catalogId: Number(selection.id),
      categoria: classification?.categoria || '',
      item: classification?.item || '',
      familia: classification?.familia_oficial || selection.familia_nombre || '',
      producto: selection.producto_nombre || '',
      subfamilia: technicalSubfamily?.subfamilia || technicalSubfamily?.etiqueta || '',
      criterio1: technicalSubfamily?.criterio_1 || '',
      criterio2: technicalSubfamily?.criterio_2 || '',
      observacion: technicalSubfamily?.observacion || '',
      articulo: selection.articulo || '',
      numeral: selection.numeral || '',
      nace: selection.codigo_nace || '',
      etiqueta: technicalSubfamily?.etiqueta || technicalSubfamily?.subfamilia || '',
    };
    manualCandidate = selection;
    preferredCatalogId = Number(selection.id);
    manualSearch.value = '';
    manualProduct.value = '';
    selectManualCandidate();
    productName.value = selection.producto_nombre;
    refreshPreview();
    activateToolTab('evidence');
    setStatus('success', technicalSubfamily
      ? 'Producto y subfamilia seleccionados. Adjunta la ficha técnica obligatoria y, si la tienes, una fotografía.'
      : 'Producto seleccionado con la subfamilia pendiente de validar. Adjunta la ficha técnica obligatoria y, si la tienes, una fotografía.');
  });

  const refreshManualCatalog = (refreshFamilies = false) => {
    const compatible = compatibleCatalog();
    if (refreshFamilies) {
      const previousFamily = manualFamily.value;
      const families = [...new Set(compatible.map((item) => item.familia_nombre).filter(Boolean))]
        .sort((a, b) => a.localeCompare(b, 'es'));
      manualFamily.innerHTML = '<option value="">Todas las familias</option>';
      families.forEach((family) => {
        const option = document.createElement('option');
        option.value = family;
        option.textContent = family;
        manualFamily.append(option);
      });
      if (families.includes(previousFamily)) manualFamily.value = previousFamily;
    }

    const search = normalize(manualSearch.value);
    const family = manualFamily.value;
    const filtered = compatible.filter((item) => {
      if (family && item.familia_nombre !== family) return false;
      if (!search) return true;
      return normalize([
        item.producto_nombre, item.familia_nombre, item.subfamilia_nombre,
        item.articulo, item.numeral, item.codigo_nace, item.documento_normativo,
      ].join(' ')).includes(search);
    });
    const previousProduct = Number(manualProduct.value || 0);
    manualProduct.innerHTML = '<option value="">Selecciona un producto</option>';
    filtered.forEach((item) => {
      const option = document.createElement('option');
      option.value = item.id;
      option.textContent = item.producto_nombre;
      manualProduct.append(option);
    });
    if (filtered.some((item) => Number(item.id) === previousProduct)) manualProduct.value = String(previousProduct);
    manualCount.textContent = serviceRegulation.value && serviceScheme.value
      ? `${filtered.length} coincidencia${filtered.length === 1 ? '' : 's'} dentro del alcance.`
      : 'Selecciona primero el alcance y el esquema del servicio.';
    selectManualCandidate();
  };

  const selectManualCandidate = () => {
    const id = Number(manualProduct.value || 0);
    manualCandidate = catalog.find((item) => Number(item.id) === id) || null;
    useManualButton.disabled = !manualCandidate;
    manualDetail.hidden = !manualCandidate;
    if (!manualCandidate) {
      manualDetail.innerHTML = '';
      return;
    }
    const referenceOptions = manualCandidate.subfamilia_referencias || [];
    manualDetail.innerHTML = `
      <strong>${escapeHtml(manualCandidate.producto_nombre)}</strong>
      <div class="classification-grid">
        <div><span>Familia</span><b>${escapeHtml(manualCandidate.familia_nombre || '-')}</b></div>
        <div><span>Subfamilia</span><b>${escapeHtml(manualCandidate.subfamilia_nombre || '-')}</b></div>
        <div><span>División NACE</span><b>${escapeHtml(manualCandidate.codigo_nace || '-')}</b></div>
        <div><span>Artículo general</span><b>${escapeHtml(manualCandidate.articulo || '-')}</b></div>
        <div><span>Subnumeral / ensayos mínimos</span><b>${escapeHtml(manualCandidate.numeral || '-')}</b></div>
      </div>
      ${referenceOptions.length ? `<div class="subfamily-reference-list"><span>Opciones técnicas de subfamilia</span><div>${referenceOptions.map((option) => `<small>${escapeHtml(option)}</small>`).join('')}</div></div>` : ''}
      <p>${escapeHtml((manualCandidate.documentos || [])[0] || manualCandidate.documento_normativo || '')}</p>`;
  };

  manualSearch.addEventListener('input', () => refreshManualCatalog(false));
  manualFamily.addEventListener('change', () => refreshManualCatalog(false));
  manualProduct.addEventListener('change', selectManualCandidate);
  useManualButton.addEventListener('click', () => {
    if (!manualCandidate) return;
    manualRetieClassification = null;
    preferredCatalogId = Number(manualCandidate.id);
    productName.value = manualCandidate.producto_nombre;
    refreshPreview();
    activateToolTab('evidence');
    setStatus('', '');
  });

  const refreshPreview = () => {
    const imageFile = productImage.files[0];
    const datasheetFile = productDatasheet.files[0];
    if (!imageFile && !datasheetFile && !productName.value.trim()) {
      preview.hidden = true;
      return;
    }
    preview.hidden = false;
    previewName.textContent = productName.value.trim() || 'Producto sin nombre';
    previewFiles.textContent = [imageFile?.name, datasheetFile?.name].filter(Boolean).join(' + ') || 'Sin evidencia seleccionada';
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    if (imageFile) {
      previewUrl = URL.createObjectURL(imageFile);
      previewImage.src = previewUrl;
      previewImage.hidden = false;
    } else {
      previewImage.removeAttribute('src');
      previewImage.hidden = true;
    }
  };
  [productImage, productDatasheet].forEach((input) => input.addEventListener('change', refreshPreview));
  productName.addEventListener('input', refreshPreview);

  const setStatus = (type, message) => {
    analysisStatus.hidden = !message;
    analysisStatus.className = `analysis-status ${type}`;
    analysisStatus.textContent = message;
  };

  const selectedFields = () => {
    const result = {};
    form.querySelectorAll('[data-analysis-field]').forEach((field) => {
      result[field.dataset.analysisField] = field.value.trim();
    });
    return result;
  };

  const renderIdentifiedClassification = () => {
    identifiedClassification.hidden = !selectedCandidate;
    if (!selectedCandidate) return;
    const referenceOptions = [...new Set((manualRetieClassification
      ? [manualRetieClassification.etiqueta, ...(selectedCandidate.subfamily_references || [])]
      : [selectedCandidate.subfamily || selectedCandidate.product, ...(selectedCandidate.subfamily_references || [])]
    ).filter(Boolean))];
    const extractedDetail = currentAnalysis?.fields?.subfamily_detail || '';
    const selectedDetail = referenceOptions.includes(extractedDetail)
      ? extractedDetail
      : (manualRetieClassification ? manualRetieClassification.etiqueta : (referenceOptions[0] || ''));
    const classificationTitle = manualRetieClassification ? 'Clasificación oficial confirmada' : 'Subfamilia identificada';
    const classificationName = manualRetieClassification
      ? (manualRetieClassification.etiqueta || 'Subfamilia por validar')
      : (selectedCandidate.subfamily || selectedCandidate.product);
    const classificationGrid = manualRetieClassification ? `
        <div><span>Categoría</span><b>${escapeHtml(manualRetieClassification.categoria || '-')}</b></div>
        <div><span>Ítem oficial</span><b>${escapeHtml(manualRetieClassification.item || '-')}</b></div>
        <div><span>Familia oficial</span><b>${escapeHtml(manualRetieClassification.familia || '-')}</b></div>
        <div><span>Producto acreditado</span><b>${escapeHtml(manualRetieClassification.producto || '-')}</b></div>
        <div><span>Subfamilia técnica</span><b>${escapeHtml(manualRetieClassification.etiqueta || manualRetieClassification.subfamilia || 'Por validar')}</b></div>
        <div><span>Criterio 1</span><b>${escapeHtml(manualRetieClassification.criterio1 || '-')}</b></div>
        <div><span>Criterio 2</span><b>${escapeHtml(manualRetieClassification.criterio2 || '-')}</b></div>
        <div><span>División NACE</span><b>${escapeHtml(manualRetieClassification.nace || selectedCandidate.nace_code || '-')}</b></div>
        <div><span>Artículo general</span><b>${escapeHtml(manualRetieClassification.articulo || selectedCandidate.article || '-')}</b></div>
        <div><span>Subnumeral</span><b>${escapeHtml(manualRetieClassification.numeral || selectedCandidate.numeral || '-')}</b></div>` : `
        <div><span>Familia</span><b>${escapeHtml(selectedCandidate.family || '-')}</b></div>
        <div><span>Subfamilia</span><b>${escapeHtml(selectedCandidate.subfamily || '-')}</b></div>
        <div><span>División NACE</span><b>${escapeHtml(selectedCandidate.nace_code || '-')}</b></div>
        <div><span>Artículo general</span><b>${escapeHtml(selectedCandidate.article || '-')}</b></div>
        <div><span>Subnumeral</span><b>${escapeHtml(selectedCandidate.numeral || '-')}</b></div>`;
    identifiedClassification.innerHTML = `
      <div class="classification-title"><span>${classificationTitle}</span><strong>${escapeHtml(classificationName)}</strong></div>
      <div class="classification-grid">
        ${classificationGrid}
      </div>
      ${referenceOptions.length ? `<label class="subfamily-reference-select">Detalle técnico de la subfamilia <small>(opcional)</small>
        <select data-analysis-field="subfamily_detail">
          <option value="" ${selectedDetail === '' ? 'selected' : ''}>No la conozco / dejar por validar</option>
          ${referenceOptions.map((option) => `<option value="${escapeHtml(option)}" ${option === selectedDetail ? 'selected' : ''}>${escapeHtml(option)}</option>`).join('')}
        </select>
      </label>` : `<input data-analysis-field="subfamily_detail" type="hidden" value="${escapeHtml(selectedDetail)}">`}
      ${manualRetieClassification ? `
        <input data-analysis-field="classification_category" type="hidden" value="${escapeHtml(manualRetieClassification.categoria)}">
        <input data-analysis-field="classification_item" type="hidden" value="${escapeHtml(manualRetieClassification.item)}">
        <input data-analysis-field="classification_family" type="hidden" value="${escapeHtml(manualRetieClassification.familia)}">
        <input data-analysis-field="classification_subfamily" type="hidden" value="${escapeHtml(manualRetieClassification.etiqueta)}">
        <input data-analysis-field="classification_criterion_1" type="hidden" value="${escapeHtml(manualRetieClassification.criterio1)}">
        <input data-analysis-field="classification_criterion_2" type="hidden" value="${escapeHtml(manualRetieClassification.criterio2)}">
        <input data-analysis-field="classification_observation" type="hidden" value="${escapeHtml(manualRetieClassification.observacion)}">
        <input data-analysis-field="retie_family" type="hidden" value="${escapeHtml(manualRetieClassification.familia)}">
        <input data-analysis-field="retie_subfamily" type="hidden" value="${escapeHtml(manualRetieClassification.subfamilia)}">` : ''}`;
  };

  const selectCandidate = (candidateId) => {
    selectedCandidate = currentAnalysis?.candidates.find((candidate) => Number(candidate.id) === Number(candidateId)) || null;
    renderIdentifiedClassification();
  };

  const renderAnalysis = (payload) => {
    currentAnalysis = { token: payload.token, ...payload.analysis };
    if (manualRetieClassification) {
      currentAnalysis.fields = {
        ...(currentAnalysis.fields || {}),
        subfamily_detail: manualRetieClassification.etiqueta || currentAnalysis.fields?.subfamily_detail || '',
        classification_category: manualRetieClassification.categoria,
        classification_item: manualRetieClassification.item,
        classification_family: manualRetieClassification.familia,
        classification_subfamily: manualRetieClassification.etiqueta,
        classification_criterion_1: manualRetieClassification.criterio1,
        classification_criterion_2: manualRetieClassification.criterio2,
        classification_observation: manualRetieClassification.observacion,
        retie_family: manualRetieClassification.familia,
        retie_subfamily: manualRetieClassification.subfamilia,
      };
    }
    const modeLabels = {
      gemini_vision: 'Visión Gemini + evidencia',
      gemini_document: 'Gemini + ficha del producto',
      manual_assisted: 'Selección manual + evidencia',
      local_documental: 'Análisis documental local',
      local_limited: 'Coincidencia por nombre',
    };
    analysisMode.textContent = modeLabels[currentAnalysis.mode] || 'Análisis completado';
    analysisWarning.hidden = !currentAnalysis.warning;
    analysisWarning.textContent = currentAnalysis.warning || '';
    resultScheme.textContent = selectedScheme();
    const batchScheme = schemeRuleKey(selectedScheme()).startsWith('1B');
    quantityField.hidden = !batchScheme;
    quantityInput.required = batchScheme;
    renderTechnicalFields(currentAnalysis.fields || {});
    candidateList.innerHTML = '';
    currentAnalysis.candidates.forEach((candidate, index) => {
      const label = document.createElement('label');
      label.className = 'candidate-option';
      label.innerHTML = `
        <input type="radio" name="analysis_candidate" value="${candidate.id}" ${index === 0 ? 'checked' : ''}>
        <span class="candidate-copy">
          <strong>${escapeHtml(candidate.product)}</strong>
          <small>${escapeHtml(candidate.family)} | Subfamilia: ${escapeHtml(candidate.subfamily || '-')} | NACE ${escapeHtml(candidate.nace_code || '-')} | ${escapeHtml(candidate.numeral || candidate.article || '-')}${(candidate.subfamily_references || []).length ? ` | ${(candidate.subfamily_references || []).length} opciones técnicas` : ''}</small>
        </span>
        <b>${escapeHtml(candidate.score)}%</b>`;
      candidateList.append(label);
    });
    candidateList.querySelectorAll('input').forEach((radio) => radio.addEventListener('change', () => selectCandidate(radio.value)));
    selectCandidate(currentAnalysis.candidates[0]?.id);
    const resultTab = form.querySelector('.tool-tab[data-tool-tab="result"]');
    resultTab.disabled = false;
    activateToolTab('result');
  };

  analyzeButton.addEventListener('click', async () => {
    const name = productName.value.trim();
    const imageFile = productImage.files[0];
    const datasheetFile = productDatasheet.files[0];
    if (!name) {
      setStatus('error', 'Escribe el nombre genérico del producto.');
      return;
    }
    if (!datasheetFile) {
      setStatus('error', 'Adjunta la ficha técnica del producto.');
      return;
    }
    if (!serviceRegulation.value || !serviceScheme.value) {
      setStatus('error', 'Selecciona primero el alcance y el esquema del servicio.');
      return;
    }
    const data = new FormData();
    data.append('nombre_producto', name);
    data.append('reglamento_codigo', serviceRegulation.value);
    data.append('esquema_solicitado', serviceScheme.value);
    if (preferredCatalogId) data.append('catalogo_id_preseleccionado', String(preferredCatalogId));
    if (imageFile) data.append('imagen_producto', imageFile);
    if (datasheetFile) data.append('ficha_tecnica', datasheetFile);
    analyzeButton.disabled = true;
    setStatus('loading', 'Python está leyendo la ficha y comparando la subfamilia...');
    try {
      const response = await fetch('api/analyze-product.php', { method: 'POST', body: data });
      const payload = await response.json();
      if (!response.ok || !payload.ok) throw new Error(payload.message || 'No fue posible analizar el producto.');
      if (!(payload.analysis?.candidates || []).length) throw new Error('No se encontraron categorías compatibles con el servicio.');
      setStatus('success', 'Análisis completado. Valida la subfamilia y los datos extraídos.');
      renderAnalysis(payload);
    } catch (error) {
      setStatus('error', error.message || 'No fue posible analizar el producto.');
    } finally {
      analyzeButton.disabled = false;
    }
  });

  const appendHidden = (name, value) => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = `${name}[]`;
    input.value = value ?? '';
    hiddenInputs.append(input);
  };

  const renderProducts = () => {
    basketItems.innerHTML = '';
    hiddenInputs.innerHTML = '';
    basketEmpty.hidden = products.length > 0;
    productCount.textContent = products.length;
    products.forEach((product, index) => {
      const technicalSubfamily = product.fields.subfamily_detail
        || product.fields.classification_subfamily
        || product.candidate.subfamily
        || '-';
      const item = document.createElement('article');
      item.className = 'basket-item';
      item.innerHTML = `
        <div><span>${index + 1}</span><strong>${escapeHtml(product.candidate.product)}</strong></div>
        <p>${escapeHtml(product.candidate.family)} | ${escapeHtml(technicalSubfamily)}</p>
        <small>NACE ${escapeHtml(product.candidate.nace_code || '-')} | ${escapeHtml(product.scheme)} | Cantidad: ${escapeHtml(product.quantity)}</small>
        <button type="button" data-remove-product="${index}" aria-label="Eliminar ${escapeHtml(product.name)}">Eliminar</button>`;
      basketItems.append(item);
      appendHidden('producto_catalogo_id', product.candidate.id);
      appendHidden('producto_analisis_token', product.token);
      appendHidden('producto_esquema', product.scheme);
      appendHidden('producto_cantidad', product.quantity);
      appendHidden('producto_nombre_cliente', product.name);
      appendHidden('producto_marca', product.fields.brand);
      appendHidden('producto_referencia', product.fields.model);
      appendHidden('producto_fabricante', product.fields.manufacturer);
      appendHidden('producto_pais_fabricante', product.fields.country);
      appendHidden('producto_tension', product.fields.voltage);
      appendHidden('producto_corriente', product.fields.current);
      appendHidden('producto_potencia', product.fields.power);
      appendHidden('producto_frecuencia', product.fields.frequency);
      appendHidden('producto_ip', product.fields.ip_rating);
      appendHidden('producto_material', product.fields.material);
      appendHidden('producto_norma_tecnica', product.fields.technical_standard);
      appendHidden('producto_descripcion', product.fields.description);
      appendHidden('producto_uso', product.fields.business_use);
      appendHidden('producto_especificaciones_json', JSON.stringify(product.fields));
    });
    renderClientDocumentChecklist();
    basketItems.querySelectorAll('[data-remove-product]').forEach((button) => button.addEventListener('click', () => {
      products.splice(Number(button.dataset.removeProduct), 1);
      renderProducts();
    }));
  };

  const resetAnalyzer = () => {
    currentAnalysis = null;
    selectedCandidate = null;
    manualCandidate = null;
    preferredCatalogId = 0;
    manualRetieClassification = null;
    productName.value = '';
    productImage.value = '';
    productDatasheet.value = '';
    quantityInput.value = '1';
    preview.hidden = true;
    setStatus('', '');
    identifiedClassification.hidden = true;
    form.querySelector('.tool-tab[data-tool-tab="result"]').disabled = true;
    activateToolTab('manual');
    manualProduct.value = '';
    selectManualCandidate();
    if (!retieFallback.hidden) {
      retieFamilySelect.value = '';
      populateRetieSubfamilies();
    }
  };

  addProductButton.addEventListener('click', () => {
    const batchScheme = schemeRuleKey(selectedScheme()).startsWith('1B');
    const quantity = batchScheme ? quantityInput.value : '1';
    const missingTechnicalField = technicalFields.querySelector('[required]:invalid');
    if (missingTechnicalField) {
      missingTechnicalField.reportValidity();
      analysisWarning.hidden = false;
      analysisWarning.textContent = 'Completa los campos técnicos obligatorios. Si una característica no corresponde al producto, registra "No aplica".';
      return;
    }
    if (!currentAnalysis || !selectedCandidate || !quantity || Number(quantity) < 1) {
      analysisWarning.hidden = false;
      analysisWarning.textContent = 'Selecciona la subfamilia y una cantidad valida.';
      return;
    }
    products.push({
      token: currentAnalysis.token,
      candidate: selectedCandidate,
      name: productName.value.trim(),
      scheme: selectedScheme(),
      quantity,
      hasImage: Boolean(productImage.files?.length),
      fields: selectedFields(),
    });
    renderProducts();
    resetAnalyzer();
    showStepMessage('Producto agregado a la solicitud.');
  });

  const renderFinalSummary = () => {
    const client = form.querySelector('[name="cliente_nombre"]').value;
    const purpose = form.querySelector('[name="proposito_servicio"]').value;
    const regulation = selectedRegulation();
    finalSummary.innerHTML = `
      <p>Resumen</p>
      <h3>${escapeHtml(client)}</h3>
      <span>${escapeHtml(purpose)} | ${escapeHtml(regulation?.nombre || '')}</span>
      <ul>${products.map((product) => `<li><strong>${escapeHtml(product.candidate.product)}</strong><small>${escapeHtml(product.fields.subfamily_detail || product.fields.classification_subfamily || product.candidate.subfamily || product.scheme)}</small></li>`).join('')}</ul>
      <div><b>${products.length}</b><span>producto${products.length === 1 ? '' : 's'} con evidencia adjunta</span></div>`;
    renderClientDocumentChecklist();
  };

  clientIsoCertificate?.addEventListener('change', renderClientDocumentChecklist);
  clientTestReports?.addEventListener('change', renderClientDocumentChecklist);

  form.querySelectorAll('input[name="atendido_comercial"]').forEach((radio) => radio.addEventListener('change', () => {
    const attended = radio.value === '1' && radio.checked;
    commercialWrap.hidden = !attended;
    commercialWrap.querySelector('select').required = attended;
    if (managerNote) managerNote.hidden = attended || !radio.checked;
  }));

  form.addEventListener('submit', (event) => {
    if (!validateStage(3) || products.length === 0) {
      event.preventDefault();
      return;
    }
    submitButton.disabled = true;
    submitButton.textContent = 'Enviando...';
  });

  renderProducts();
  renderClientDocumentChecklist();
  renderPurposeOptions();
  renderSchemeHelp();
  renderIsoFields();
  renderRecommendation();
  renderTechnicalFields();
  refreshManualCatalog(true);
  renderRetieFallback(true);
  activateStage(0);
})();
