(function () {
  const form = document.querySelector('[data-prequote]');

  if (!form) {
    return;
  }

  const money = new Intl.NumberFormat('es-CO', {
    style: 'currency',
    currency: 'COP',
    maximumFractionDigits: 0
  });

  function numeric(input) {
    let value = String(input && input.value ? input.value : '0')
      .replaceAll('$', '')
      .replaceAll(' ', '');

    if (value.includes(',') && value.includes('.')) {
      value = value.replaceAll('.', '').replace(',', '.');
    } else {
      value = value.replace(',', '.');
    }

    const parsed = Number.parseFloat(value);
    return Number.isFinite(parsed) ? parsed : 0;
  }

  function setText(id, value) {
    const element = document.getElementById(id);
    if (element) {
      element.textContent = money.format(value);
    }
  }

  function recalc() {
    let subtotalEnsayos = 0;
    let subtotalServicios = 0;
    let subtotalOtros = 0;

    form.querySelectorAll('[data-quote-row]').forEach((row) => {
      const quantity = numeric(row.querySelector('.quote-qty'));
      const unitCost = numeric(row.querySelector('.unit-cost'));
      const unitValue = numeric(row.querySelector('.unit-value'));
      const cost = quantity * unitCost;
      const value = quantity * unitValue;

      subtotalEnsayos += value;

      const costCell = row.querySelector('.subtotal-cost');
      const valueCell = row.querySelector('.subtotal-value');
      if (costCell) costCell.textContent = money.format(cost);
      if (valueCell) valueCell.textContent = money.format(value);
    });

    form.querySelectorAll('[data-service-row]').forEach((row) => {
      const quantity = numeric(row.querySelector('.service-qty'));
      const unitValue = numeric(row.querySelector('.service-unit'));
      const subtotal = quantity * unitValue;
      const type = row.dataset.serviceType || 'certificacion';

      if (type === 'otro') {
        subtotalOtros += subtotal;
      } else {
        subtotalServicios += subtotal;
      }

      const cell = row.querySelector('.service-subtotal');
      if (cell) cell.textContent = money.format(subtotal);
    });

    const base = subtotalEnsayos + subtotalServicios + subtotalOtros;
    const iva = base * 0.19;
    setText('subtotalEnsayos', subtotalEnsayos);
    setText('subtotalServicios', subtotalServicios);
    setText('subtotalOtros', subtotalOtros);
    setText('ivaTotal', iva);
    setText('valorFacturar', base + iva);
  }

  form.addEventListener('input', (event) => {
    if (event.target.matches('input, textarea, select')) {
      recalc();
    }
  });

  recalc();
})();

(function () {
  document.querySelectorAll('[data-print-page]').forEach((button) => {
    button.addEventListener('click', () => window.print());
  });

  if (document.body && document.body.dataset.autoPrint === '1') {
    window.addEventListener('load', () => window.setTimeout(() => window.print(), 150));
  }
})();

(function () {
  const form = document.getElementById('clientRequestWizard');
  const catalogElement = document.getElementById('requestCatalogData');

  if (!form || !catalogElement) {
    return;
  }

  let catalog = [];
  try {
    catalog = JSON.parse(catalogElement.textContent || '[]');
  } catch (error) {
    catalog = [];
  }

  const panels = Array.from(form.querySelectorAll('[data-wizard-panel]'));
  const stepButtons = Array.from(form.querySelectorAll('[data-wizard-step]'));
  const track = form.querySelector('.wizard-track');
  const backButton = form.querySelector('[data-step-back]');
  const nextButton = form.querySelector('[data-step-next]');
  const submitButton = form.querySelector('[data-step-submit]');
  const progress = document.getElementById('wizardProgress');
  const familySelect = document.getElementById('catalogFamily');
  const productSelect = document.getElementById('catalogProduct');
  const searchInput = document.getElementById('catalogSearch');
  const detail = document.getElementById('catalogSelectionDetail');
  const addProductButton = document.getElementById('addRequestProduct');
  const productList = document.getElementById('requestProducts');
  const productMessage = document.getElementById('productValidationMessage');
  const catalogCount = document.getElementById('catalogCount');
  const representativeToggle = document.getElementById('hasRepresentative');
  const representativeFields = document.getElementById('representativeFields');
  const commercialSelect = document.getElementById('commercialId');
  const commercialSelection = document.getElementById('commercialSelection');
  const managerAssignment = document.getElementById('managerAssignment');
  const summary = document.getElementById('requestSummary');
  let currentStep = 0;
  let maxVisitedStep = 0;
  let productSequence = 0;

  function escapeHtml(value) {
    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  function normalizeText(value) {
    return String(value ?? '')
      .replaceAll('_', ' ')
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .replace(/\s+/g, ' ')
      .trim()
      .toLowerCase();
  }

  function selectedRegulation() {
    return form.querySelector('input[name="reglamento_codigo"]:checked')?.value || '';
  }

  function filteredCatalog() {
    const regulation = selectedRegulation();
    const family = familySelect.value;
    const search = normalizeText(searchInput.value);

    return catalog.filter((item) => {
      if (!regulation || item.reglamento_codigo !== regulation) return false;
      if (family && item.familia_nombre !== family) return false;
      if (!search) return true;

      return normalizeText([
        item.producto_nombre,
        item.familia_nombre,
        item.subfamilia_nombre,
        item.articulo,
        item.numeral
      ].join(' ')).includes(search);
    });
  }

  function populateFamilies() {
    const regulation = selectedRegulation();
    const families = Array.from(new Set(
      catalog
        .filter((item) => item.reglamento_codigo === regulation)
        .map((item) => item.familia_nombre)
        .filter(Boolean)
    )).sort((a, b) => a.localeCompare(b, 'es'));

    const previous = familySelect.value;
    familySelect.innerHTML = '<option value="">Todas las familias</option>' + families
      .map((family) => `<option value="${escapeHtml(family)}">${escapeHtml(family)}</option>`)
      .join('');
    if (families.includes(previous)) {
      familySelect.value = previous;
    }
    populateProducts();
  }

  function populateProducts() {
    const rows = filteredCatalog();
    const regulation = selectedRegulation();
    productSelect.innerHTML = regulation
      ? '<option value="">Selecciona un producto</option>'
      : '<option value="">Selecciona primero el reglamento</option>';

    rows.forEach((item) => {
      const option = document.createElement('option');
      option.value = item.id;
      option.textContent = item.producto_nombre;
      productSelect.appendChild(option);
    });

    catalogCount.textContent = regulation ? `${rows.length} coincidencias` : `${catalog.length} productos`;
    detail.hidden = true;
  }

  function selectedCatalogItem() {
    const id = Number.parseInt(productSelect.value, 10);
    return catalog.find((item) => Number(item.id) === id) || null;
  }

  function showCatalogDetail() {
    const item = selectedCatalogItem();
    if (!item) {
      detail.hidden = true;
      return;
    }

    detail.innerHTML = `
      <strong>${escapeHtml(item.producto_nombre)}</strong>
      <span>${escapeHtml(item.familia_nombre || 'Sin familia')}</span>
      <span>${escapeHtml(item.subfamilia_nombre || '')}</span>
      ${item.articulo || item.numeral ? `<small>Articulo ${escapeHtml(item.articulo || '')} ${escapeHtml(item.numeral || '')}</small>` : ''}
    `;
    detail.hidden = false;
  }

  function addProduct(item) {
    productSequence += 1;
    const card = document.createElement('article');
    card.className = 'request-product-card';
    card.dataset.requestProduct = String(productSequence);
    card.dataset.productName = item.producto_nombre;
    card.innerHTML = `
      <div class="request-product-header">
        <div>
          <span class="tag">${escapeHtml(item.reglamento_codigo)}</span>
          <h3>${escapeHtml(item.producto_nombre)}</h3>
          <p>${escapeHtml(item.familia_nombre || '')}${item.subfamilia_nombre ? ` / ${escapeHtml(item.subfamilia_nombre)}` : ''}</p>
        </div>
        <button class="button danger compact-button" type="button" data-remove-product>Eliminar</button>
      </div>
      <input type="hidden" name="producto_catalogo_id[]" value="${escapeHtml(item.id)}">
      <div class="form-grid request-form-grid">
        <div><label>Referencia o modelo</label><input name="producto_referencia[]" type="text" required></div>
        <div><label>Marca comercial</label><input name="producto_marca[]" type="text" required></div>
        <div><label>Cantidad total</label><input name="producto_cantidad[]" type="text"></div>
        <div><label>Fabricado por</label><input name="producto_fabricante[]" type="text"></div>
        <div class="full"><label>Norma tecnica aplicable</label><input name="producto_norma_tecnica[]" type="text"></div>
        <div><label>Material de fabricacion</label><input name="producto_material[]" type="text"></div>
        <div><label>Tension nominal</label><input name="producto_tension[]" type="text"></div>
        <div><label>Corriente nominal o maxima</label><input name="producto_corriente[]" type="text"></div>
        <div><label>Frecuencia</label><input name="producto_frecuencia[]" type="text"></div>
        <div><label>Grado de proteccion IP</label><input name="producto_ip[]" type="text"></div>
        <div><label>Lote o serial</label><input name="producto_lote_serial[]" type="text"></div>
        <div class="full"><label>Otras caracteristicas</label><textarea name="producto_caracteristicas[]" rows="2"></textarea></div>
      </div>
    `;
    productList.appendChild(card);
    productMessage.hidden = true;
    renderSummary();
  }

  function fieldValue(name) {
    const field = form.elements.namedItem(name);
    if (!field) return '';
    if (field instanceof RadioNodeList) return field.value;
    return field.value || '';
  }

  function renderSummary() {
    const regulation = selectedRegulation() || 'Por seleccionar';
    const products = Array.from(productList.querySelectorAll('[data-request-product]'));
    const attended = fieldValue('atendido_comercial');
    let assignee = 'Por seleccionar';

    if (attended === '1') {
      assignee = commercialSelect.options[commercialSelect.selectedIndex]?.text || 'Comercial por seleccionar';
    } else if (attended === '0') {
      assignee = managerAssignment.querySelector('strong')?.textContent || 'Jefe comercial';
    }

    summary.innerHTML = `
      <dt>Solicitante</dt><dd>${escapeHtml(fieldValue('cliente_nombre') || 'Por completar')}</dd>
      <dt>Contacto</dt><dd>${escapeHtml(fieldValue('contacto_nombre') || 'Por completar')}</dd>
      <dt>Reglamento</dt><dd>${escapeHtml(regulation)}</dd>
      <dt>Servicio</dt><dd>${escapeHtml(fieldValue('proposito_servicio') || 'Por seleccionar')} / Esquema ${escapeHtml(fieldValue('esquema_solicitado') || '-')}</dd>
      <dt>Productos</dt><dd>${products.length ? products.map((row) => escapeHtml(row.dataset.productName)).join('<br>') : 'Ninguno agregado'}</dd>
      <dt>Responsable</dt><dd>${escapeHtml(assignee)}</dd>
    `;
  }

  function updateCommercialAssignment() {
    const attended = fieldValue('atendido_comercial');
    commercialSelection.hidden = attended !== '1';
    managerAssignment.hidden = attended !== '0';
    commercialSelect.required = attended === '1';
    if (attended !== '1') {
      commercialSelect.value = '';
    }
    renderSummary();
  }

  function showStep(index, shouldScroll = true) {
    currentStep = Math.max(0, Math.min(index, panels.length - 1));
    maxVisitedStep = Math.max(maxVisitedStep, currentStep);
    track.style.transform = `translateX(-${currentStep * 100}%)`;

    panels.forEach((panel, panelIndex) => {
      panel.setAttribute('aria-hidden', panelIndex === currentStep ? 'false' : 'true');
    });
    stepButtons.forEach((button, buttonIndex) => {
      button.classList.toggle('active', buttonIndex === currentStep);
      button.classList.toggle('complete', buttonIndex < currentStep);
      button.disabled = buttonIndex > maxVisitedStep;
    });

    backButton.hidden = currentStep === 0;
    nextButton.hidden = currentStep === panels.length - 1;
    submitButton.hidden = currentStep !== panels.length - 1;
    progress.textContent = `Paso ${currentStep + 1} de ${panels.length}`;
    renderSummary();
    if (shouldScroll) {
      form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }

  function validatePanel(index) {
    if (index === 2 && !productList.querySelector('[data-request-product]')) {
      productMessage.hidden = false;
      productMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
      return false;
    }

    const requiredFields = Array.from(panels[index].querySelectorAll('[required]'));
    const invalid = requiredFields.find((field) => !field.checkValidity());
    if (invalid) {
      invalid.reportValidity();
      invalid.focus({ preventScroll: false });
      return false;
    }

    return true;
  }

  form.querySelectorAll('input[name="reglamento_codigo"]').forEach((radio) => {
    radio.addEventListener('change', () => {
      familySelect.value = '';
      searchInput.value = '';
      populateFamilies();
      renderSummary();
    });
  });
  familySelect.addEventListener('change', populateProducts);
  searchInput.addEventListener('input', populateProducts);
  productSelect.addEventListener('change', showCatalogDetail);
  addProductButton.addEventListener('click', () => {
    const item = selectedCatalogItem();
    if (!item) {
      productSelect.focus();
      return;
    }
    addProduct(item);
    productSelect.value = '';
    detail.hidden = true;
  });

  productList.addEventListener('click', (event) => {
    const removeButton = event.target.closest('[data-remove-product]');
    if (!removeButton) return;
    removeButton.closest('[data-request-product]')?.remove();
    renderSummary();
  });
  productList.addEventListener('input', renderSummary);

  representativeToggle.addEventListener('change', () => {
    representativeFields.hidden = !representativeToggle.checked;
  });

  form.querySelectorAll('input[name="atendido_comercial"]').forEach((radio) => {
    radio.addEventListener('change', updateCommercialAssignment);
  });
  commercialSelect.addEventListener('change', renderSummary);
  form.addEventListener('input', (event) => {
    if (event.target.matches('input, select, textarea')) renderSummary();
  });

  nextButton.addEventListener('click', () => {
    if (validatePanel(currentStep)) {
      showStep(currentStep + 1);
    }
  });
  backButton.addEventListener('click', () => showStep(currentStep - 1));
  stepButtons.forEach((button) => {
    button.addEventListener('click', () => {
      const target = Number.parseInt(button.dataset.wizardStep, 10);
      if (target <= maxVisitedStep && (target < currentStep || validatePanel(currentStep))) {
        showStep(target);
      }
    });
  });

  form.addEventListener('submit', (event) => {
    for (let index = 0; index < panels.length; index += 1) {
      if (!validatePanel(index)) {
        event.preventDefault();
        showStep(index);
        return;
      }
    }
    submitButton.disabled = true;
    submitButton.textContent = 'Enviando...';
  });

  populateFamilies();
  updateCommercialAssignment();
  showStep(0, false);
})();
