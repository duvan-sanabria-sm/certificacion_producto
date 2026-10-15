const assert = require('node:assert/strict');
const path = require('node:path');
const os = require('node:os');
const { chromium } = require('playwright');

const baseUrl = (process.env.SERVIMETERS_TEST_BASE_URL || 'http://localhost/Software%20producto').replace(/\/$/, '');

async function showFinalStage(page) {
  await page.evaluate(() => {
    document.querySelectorAll('[data-stage]').forEach((stage) => {
      const active = stage.dataset.stage === '3';
      stage.hidden = !active;
      stage.classList.toggle('active', active);
    });
    document.querySelectorAll('[data-step]').forEach((step) => {
      step.classList.toggle('active', step.dataset.step === '3');
      step.classList.toggle('complete', Number(step.dataset.step) < 3);
    });
    document.getElementById('previousStep').hidden = false;
    document.getElementById('nextStep').hidden = true;
    document.getElementById('submitRequest').hidden = false;
  });
}

async function main() {
  const options = { headless: true };
  if (process.env.SERVIMETERS_CHROME_PATH) options.executablePath = process.env.SERVIMETERS_CHROME_PATH;
  const browser = await chromium.launch(options);
  try {
    const desktop = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
    await desktop.goto(`${baseUrl}/solicitud_cliente.php`, { waitUntil: 'networkidle' });
    assert.equal(await desktop.getByRole('heading', { name: 'Solicitud de certificación' }).count(), 1);
    assert.equal(await desktop.locator('[name="medios_tecnologicos_remotos"][value="1"]').count(), 1);
    assert.equal(await desktop.locator('[name="medios_tecnologicos_remotos"][value="0"]').count(), 1);
    await showFinalStage(desktop);
    assert.equal(await desktop.getByRole('heading', { name: 'Documentos, asignación y envío' }).count(), 1);
    assert.equal(await desktop.locator('.optional-badge').count(), 2);
    assert.equal(await desktop.locator('#clientIsoCertificate').getAttribute('name'), 'certificado_iso');
    assert.equal(await desktop.locator('#clientTestReports').getAttribute('multiple'), '');
    await desktop.screenshot({ path: path.join(os.tmpdir(), 'servimeters-solicitud-documentos-desktop.png'), fullPage: true });

    const mobile = await browser.newPage({ viewport: { width: 390, height: 844 } });
    await mobile.goto(`${baseUrl}/solicitud_cliente.php`, { waitUntil: 'networkidle' });
    await showFinalStage(mobile);
    const bodyWidth = await mobile.locator('body').evaluate((body) => body.scrollWidth);
    assert.ok(bodyWidth <= 390, `La vista móvil tiene desbordamiento horizontal: ${bodyWidth}px.`);
    await mobile.screenshot({ path: path.join(os.tmpdir(), 'servimeters-solicitud-documentos-mobile.png'), fullPage: true });
    process.stdout.write(`Client request UI smoke check OK\n${path.join(os.tmpdir(), 'servimeters-solicitud-documentos-desktop.png')}\n${path.join(os.tmpdir(), 'servimeters-solicitud-documentos-mobile.png')}\n`);
  } finally {
    await browser.close();
  }
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
