const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const os = require('node:os');
const { chromium } = require('playwright');

const php = process.env.SERVIMETERS_PHP_BINARY || 'php';
const fixture = path.join(__dirname, 'auth_test_fixture.php');
const baseUrl = (process.env.SERVIMETERS_TEST_BASE_URL || 'http://localhost/Software%20producto').replace(/\/$/, '');

async function login(browser, username, password) {
  const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
  const page = await context.newPage();
  await page.goto(`${baseUrl}/login.php`, { waitUntil: 'networkidle' });
  await page.locator('[name="usuario"]').fill(username);
  await page.locator('[name="password"]').fill(password);
  await page.getByRole('button', { name: 'Ingresar' }).click();
  await page.waitForLoadState('networkidle');
  return { context, page };
}

async function main() {
  const fixtureData = JSON.parse(execFileSync(php, [fixture, 'setup'], { encoding: 'utf8' }).trim());
  const launchOptions = { headless: true };
  if (process.env.SERVIMETERS_CHROME_PATH) launchOptions.executablePath = process.env.SERVIMETERS_CHROME_PATH;
  const browser = await chromium.launch(launchOptions);
  try {
    {
      const { context, page } = await login(browser, fixtureData.users.coordinador_tecnico, fixtureData.password);
      await page.goto(`${baseUrl}/revision_tecnica.php?solicitud_id=${fixtureData.own_request_id}`, { waitUntil: 'networkidle' });
      assert.equal(await page.getByRole('heading', { name: 'Aplicación de revisión y tiempos de evaluación' }).count(), 1);
      await page.screenshot({ path: path.join(os.tmpdir(), 'servimeters-revision-desktop.png'), fullPage: true });
      await page.setViewportSize({ width: 390, height: 844 });
      const mobileBodyWidth = await page.locator('body').evaluate((body) => body.scrollWidth);
      await page.screenshot({ path: path.join(os.tmpdir(), 'servimeters-revision-mobile.png'), fullPage: true });
      const wideElements = await page.evaluate(() => [...document.querySelectorAll('body *')]
        .map((element) => ({ tag: element.tagName, className: element.className, width: element.getBoundingClientRect().width, right: element.getBoundingClientRect().right }))
        .filter((item) => item.right > window.innerWidth + 1)
        .sort((left, right) => right.right - left.right)
        .slice(0, 8));
      assert.ok(mobileBodyWidth <= 390, `La revisión móvil tiene desbordamiento horizontal: ${mobileBodyWidth}px. ${JSON.stringify(wideElements)}`);
      await page.setViewportSize({ width: 1440, height: 1000 });
      await page.locator('[name="doc_ficha_tecnica"][value="SI"]').check();
      await page.locator('[name="producto_en_alcance"][value="SI"]').check();
      await page.locator('[name="iso_conforme"][value="NA"]').check();
      await page.locator('[name="test_reports_suficientes"][value="NA"]').check();
      await page.locator('[name="decision_viabilidad"]').selectOption('SI');
      await page.getByRole('button', { name: '2. Tiempos de evaluación' }).click();
      await page.getByRole('button', { name: 'Calcular días técnicos' }).click();
      assert.match(await page.locator('#adjustedDaysOutput').textContent(), /días/);
      await page.getByRole('button', { name: 'Guardar revisión' }).click();
      await page.waitForLoadState('networkidle');
      assert.equal(await page.getByText('Revisión guardada y enviada para aprobación técnica.', { exact: true }).count(), 1);
      await context.close();
    }
    {
      const { context, page } = await login(browser, fixtureData.users.jefe_tecnico, fixtureData.password);
      await page.goto(`${baseUrl}/revision_tecnica.php?solicitud_id=${fixtureData.own_request_id}`, { waitUntil: 'networkidle' });
      await page.getByRole('button', { name: 'Aprobar y habilitar precotización' }).click();
      await page.waitForLoadState('networkidle');
      assert.equal(await page.getByText('Revisión aprobada. La solicitud ya está habilitada para precotización.', { exact: true }).count(), 1);
      await context.close();
    }
    {
      const { context, page } = await login(browser, fixtureData.users.comercial_1, fixtureData.password);
      await page.goto(`${baseUrl}/pre_cotizacion.php`, { waitUntil: 'networkidle' });
      assert.equal(await page.locator('#quoteRequest option').filter({ hasText: fixtureData.own_request }).count(), 1);
      assert.equal(await page.locator('#quoteRequest option').filter({ hasText: fixtureData.other_request }).count(), 0);
      await page.goto(`${baseUrl}/pre_cotizacion.php?solicitud_id=${fixtureData.own_request_id}`, { waitUntil: 'networkidle' });
      assert.equal(await page.getByRole('button', { name: 'Aprobar y pasar a cotización final' }).count(), 0);
      await context.close();
    }
    {
      const { context, page } = await login(browser, fixtureData.users.comercial_2, fixtureData.password);
      await page.goto(`${baseUrl}/pre_cotizacion.php?solicitud_id=${fixtureData.own_request_id}`, { waitUntil: 'networkidle' });
      assert.equal(await page.getByRole('button', { name: 'Aprobar y pasar a cotización final' }).count(), 1);
      await page.getByRole('button', { name: 'Aprobar y pasar a cotización final' }).click();
      await page.waitForLoadState('networkidle');
      assert.equal(await page.locator('.notice.ok').filter({ hasText: 'Pre-cotización guardada como' }).count(), 1);
      await page.goto(`${baseUrl}/cotizacion_final.php`, { waitUntil: 'networkidle' });
      assert.equal(await page.locator('tbody').getByText(fixtureData.own_request, { exact: true }).count(), 1);
      await context.close();
    }
    process.stdout.write(`Technical review smoke check OK\n${path.join(os.tmpdir(), 'servimeters-revision-desktop.png')}\n${path.join(os.tmpdir(), 'servimeters-revision-mobile.png')}\n`);
  } finally {
    await browser.close();
    execFileSync(php, [fixture, 'cleanup'], { stdio: 'ignore' });
  }
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
