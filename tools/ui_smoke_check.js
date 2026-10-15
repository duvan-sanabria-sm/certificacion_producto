const assert = require('node:assert/strict');
const path = require('node:path');
const os = require('node:os');
const { chromium } = require('playwright');

const baseUrl = (process.env.SERVIMETERS_TEST_BASE_URL || 'http://localhost/Software%20producto').replace(/\/$/, '');

async function selectFirstRealOption(locator) {
  const values = await locator.locator('option').evaluateAll((options) =>
    options.map((option) => option.value).filter(Boolean)
  );
  assert.ok(values.length > 0, `No hay opciones disponibles en ${await locator.getAttribute('id')}`);
  await locator.selectOption(values[0]);
}

async function main() {
  const launchOptions = { headless: true };
  if (process.env.SERVIMETERS_CHROME_PATH) {
    launchOptions.executablePath = process.env.SERVIMETERS_CHROME_PATH;
  }
  const browser = await chromium.launch(launchOptions);
  const page = await browser.newPage({ viewport: { width: 1440, height: 1050 } });

  try {
    await page.goto(`${baseUrl}/solicitud_cliente.php`, { waitUntil: 'networkidle' });
    await page.locator('[name="cliente_nombre"]').fill('Prueba de interfaz');
    await page.locator('[name="numero_documento"]').fill('900123456');
    await page.locator('[name="contacto_nombre"]').fill('Validación interna');
    await page.locator('[name="contacto_cargo"]').fill('Analista');
    await page.locator('[name="contacto_correo"]').fill('analista.innovacion@servimeters.com');
    await page.locator('[name="contacto_telefono"]').fill('3001234567');
    await page.locator('#nextStep').click();
    await page.locator('[data-stage="1"]:not([hidden])').waitFor();

    const regulation = page.locator('#serviceRegulation');
    const genericOption = regulation.locator('option').filter({ hasText: /Cascos/i }).first();
    assert.ok(await genericOption.count(), 'No se encontró el alcance de Cascos para probar el esquema 5 general.');
    await regulation.selectOption(await genericOption.getAttribute('value'));
    await page.locator('#serviceScheme').selectOption({ label: 'Esquema 5' });
    await selectFirstRealOption(page.locator('#servicePurpose'));
    await page.locator('[name="motivo_servicio"]').selectOption({ index: 1 });
    await page.locator('[name="iso_9001_vigente"][value="implementacion"]').check();
    await page.locator('[name="medios_tecnologicos_remotos"]').check();

    await expectText(page.locator('#recommendationResult'), /5.*evaluación del sistema de control de producción/i);
    assert.equal(await page.locator('#recommendationOptional').isHidden(), true, 'El esquema 5 general no debe mostrar alerta de esquema inferior.');

    const retieOption = regulation.locator('option').filter({ hasText: /^RETIE\b/i }).first();
    assert.ok(await retieOption.count(), 'No se encontró el alcance RETIE.');
    await regulation.selectOption(await retieOption.getAttribute('value'));
    await page.locator('#serviceScheme').selectOption({ label: 'Esquema 4 RETIE' });
    await selectFirstRealOption(page.locator('#servicePurpose'));
    await page.locator('#nextStep').click();
    await page.locator('[data-stage="2"]:not([hidden])').waitFor();

    const family = page.locator('#retieFamilySelect');
    await family.selectOption({ label: 'Aisladores eléctricos' });
    const product = page.locator('#retieSubfamilySelect');
    const pinOption = product.locator('option').filter({ hasText: /tipo Pin.*material polimérico/i }).first();
    assert.ok(await pinOption.count(), 'No se encontró el aislador tipo Pin polimérico acreditado.');
    await product.selectOption(await pinOption.getAttribute('value'));

    const technical = page.locator('#scopeTechnicalSubfamilySelect');
    const technicalOptions = await technical.locator('option').allTextContents();
    assert.equal(technicalOptions.length, 2, `Se esperaban una opción en blanco y una subfamilia compatible; se obtuvieron ${technicalOptions.length}.`);
    assert.match(technicalOptions[1], /Media tensión.*poliméric|poliméric.*Media tensión/i);
    assert.match(await page.locator('.scope-subfamily-note').innerText(), /no conoce.*déjela por validar/i);
    assert.equal(await page.locator('#useRetieClassification').isEnabled(), true, 'Debe poder continuar dejando la subfamilia técnica por validar.');

    await page.locator('#useRetieClassification').click();
    await page.locator('[data-tool-panel="evidence"]:not([hidden])').waitFor();
    await expectText(page.getByText('Ficha técnica del producto', { exact: true }), /Ficha técnica del producto/);
    await expectText(page.locator('.evidence-requirements'), /referencia.*fabricante.*especificaciones técnicas/i);
    await page.screenshot({ path: path.join(os.tmpdir(), 'servimeters-productos.png'), fullPage: true });

    if (process.env.SERVIMETERS_TEST_USER && process.env.SERVIMETERS_TEST_PASSWORD) {
      const quote = await browser.newPage({ viewport: { width: 1440, height: 900 } });
      await quote.goto(`${baseUrl}/login.php`, { waitUntil: 'networkidle' });
      await quote.locator('[name="usuario"]').fill(process.env.SERVIMETERS_TEST_USER);
      await quote.locator('[name="password"]').fill(process.env.SERVIMETERS_TEST_PASSWORD);
      await quote.getByRole('button', { name: 'Ingresar' }).click();
      await quote.goto(`${baseUrl}/pre_cotizacion.php`, { waitUntil: 'networkidle' });
      assert.equal(await quote.locator('.mode-selector input[name="tipo_servicio"][type="radio"]').count(), 2, 'La pre-cotización debe tener exactamente dos modalidades visibles.');
      assert.equal(await quote.locator('.mode-selector').getByText('Solo ensayos', { exact: true }).count(), 0, 'La modalidad Solo ensayos sigue visible.');
      await expectText(quote.locator('.mode-selector'), /Solo certificación.*Certificación \+ ensayos/s);
      assert.deepEqual(
        await quote.locator('.quote-component thead th').allTextContents(),
        ['Código', 'Ensayo', 'Producto', 'Cantidad', 'Metodo / norma', 'Costo unit.', 'Valor unit.', 'Subtotal costo', 'Subtotal valor'],
        'Los encabezados de ensayos no corresponden con las columnas editables.'
      );
      await quote.screenshot({ path: path.join(os.tmpdir(), 'servimeters-precotizacion.png'), fullPage: true });
    } else {
      console.log('Pre-cotización omitida: define SERVIMETERS_TEST_USER y SERVIMETERS_TEST_PASSWORD.');
    }

    console.log('UI smoke check OK');
    console.log(`Capturas: ${path.join(os.tmpdir(), 'servimeters-productos.png')} | ${path.join(os.tmpdir(), 'servimeters-precotizacion.png')}`);
  } finally {
    await browser.close();
  }
}

async function expectText(locator, pattern) {
  const text = await locator.innerText();
  assert.match(text, pattern);
}

main().catch((error) => {
  console.error(error.stack || error.message || error);
  process.exitCode = 1;
});
