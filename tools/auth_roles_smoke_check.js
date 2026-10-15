const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const path = require('node:path');
const os = require('node:os');
const { chromium } = require('playwright');

const php = process.env.SERVIMETERS_PHP_BINARY || 'php';
const fixture = path.join(__dirname, 'auth_test_fixture.php');
const baseUrl = (process.env.SERVIMETERS_TEST_BASE_URL || 'http://localhost/Software%20producto').replace(/\/$/, '');

async function login(browser, username, password) {
  const context = await browser.newContext({ viewport: { width: 1366, height: 900 } });
  const page = await context.newPage();
  await page.goto(`${baseUrl}/login.php`, { waitUntil: 'networkidle' });
  await page.locator('[name="usuario"]').fill(username);
  await page.locator('[name="password"]').fill(password);
  await page.getByRole('button', { name: 'Ingresar' }).click();
  await page.waitForLoadState('networkidle');
  assert.equal(await page.locator('body').getByText('Usuario o contraseña incorrectos.', { exact: true }).count(), 0);
  return { context, page };
}

async function expectAllowed(page, path, heading) {
  const response = await page.goto(`${baseUrl}/${path}`, { waitUntil: 'networkidle' });
  assert.equal(response.status(), 200, `${path} debía estar permitido.`);
  assert.equal(await page.getByRole('heading', { name: heading, exact: true }).count(), 1, `${path} no mostró ${heading}.`);
}

async function expectDenied(page, path) {
  const response = await page.goto(`${baseUrl}/${path}`, { waitUntil: 'networkidle' });
  assert.equal(response.status(), 403, `${path} debía estar restringido.`);
  assert.equal(await page.getByRole('heading', { name: 'Tu perfil no tiene este permiso', exact: true }).count(), 1);
}

async function main() {
  const fixtureData = JSON.parse(execFileSync(php, [fixture, 'setup'], { encoding: 'utf8' }).trim());
  const launchOptions = { headless: true };
  if (process.env.SERVIMETERS_CHROME_PATH) {
    launchOptions.executablePath = process.env.SERVIMETERS_CHROME_PATH;
  }
  const browser = await chromium.launch(launchOptions);

  try {
    {
      const { context, page } = await login(browser, fixtureData.users.administrador, fixtureData.password);
      const frame = page.locator('.app-frame');
      const sidebarToggle = page.locator('[data-sidebar-toggle].sidebar-toggle');
      assert.equal(await sidebarToggle.count(), 1, 'No se encontró el control del panel lateral.');
      await sidebarToggle.click();
      assert.equal(await frame.evaluate((element) => element.classList.contains('sidebar-collapsed')), true, 'El panel no se ocultó.');
      await page.reload({ waitUntil: 'networkidle' });
      assert.equal(await frame.evaluate((element) => element.classList.contains('sidebar-collapsed')), true, 'El estado oculto del panel no se conservó.');
      await sidebarToggle.click();
      assert.equal(await frame.evaluate((element) => element.classList.contains('sidebar-collapsed')), false, 'El panel no volvió a mostrarse.');
      await expectAllowed(page, 'usuarios.php', 'Usuarios y roles');
      await page.screenshot({ path: path.join(os.tmpdir(), 'servimeters-usuarios-roles.png'), fullPage: true });
      await expectAllowed(page, 'criterios_precotizacion.php', 'Criterios de pre-cotizacion');
      await expectAllowed(page, 'laboratorios.php', 'Laboratorios y matrices de precios');
      assert.equal(await page.getByRole('heading', { name: /Nuevo laboratorio|Editar laboratorio/ }).count(), 1);
      await context.close();
    }

    {
      const { context, page } = await login(browser, fixtureData.users.comercial_1, fixtureData.password);
      await expectAllowed(page, 'index.php', 'Solicitud de servicio');
      assert.equal(await page.getByText(fixtureData.own_request, { exact: true }).count(), 1, 'Comercial 1 no ve su solicitud asignada.');
      assert.equal(await page.getByText(fixtureData.other_request, { exact: true }).count(), 0, 'Comercial 1 puede ver una solicitud ajena.');
      await expectAllowed(page, 'pre_cotizacion.php', 'Pre-cotización');
      assert.equal(await page.locator('#quoteRequest option').filter({ hasText: fixtureData.other_request }).count(), 0, 'La solicitud ajena llegó al selector de pre-cotización.');
      await expectDenied(page, 'revision_tecnica.php');
      await expectDenied(page, 'usuarios.php');
      await context.close();
    }

    {
      const { context, page } = await login(browser, fixtureData.users.comercial_2, fixtureData.password);
      await expectAllowed(page, 'index.php', 'Solicitud de servicio');
      assert.equal(await page.getByText(fixtureData.own_request, { exact: true }).count(), 1, 'Comercial 2 debe consultar el flujo comercial completo.');
      assert.equal(await page.getByText(fixtureData.other_request, { exact: true }).count(), 1, 'Comercial 2 debe consultar el flujo comercial completo.');
      await expectAllowed(page, 'cotizacion_final.php', 'Cotizacion final');
      await expectDenied(page, 'revision_tecnica.php');
      await expectDenied(page, 'laboratorios.php');
      await context.close();
    }

    {
      const { context, page } = await login(browser, fixtureData.users.coordinador_tecnico, fixtureData.password);
      await expectAllowed(page, 'revision_tecnica.php', 'Aplicación de revisión y tiempos de evaluación');
      await expectAllowed(page, 'pre_cotizacion.php', 'Pre-cotización');
      await expectAllowed(page, 'matrices.php', 'Plantillas y matrices por alcance');
      await expectAllowed(page, 'laboratorios.php', 'Laboratorios y matrices de precios');
      assert.equal(await page.getByRole('heading', { name: /Nuevo laboratorio|Editar laboratorio/ }).count(), 0);
      await context.close();
    }

    {
      const { context, page } = await login(browser, fixtureData.users.jefe_tecnico, fixtureData.password);
      await expectAllowed(page, 'revision_tecnica.php', 'Aplicación de revisión y tiempos de evaluación');
      await expectAllowed(page, 'pre_cotizacion.php', 'Pre-cotización');
      await expectAllowed(page, 'laboratorios.php', 'Laboratorios y matrices de precios');
      assert.equal(await page.getByRole('heading', { name: /Nuevo laboratorio|Editar laboratorio/ }).count(), 1);
      await expectAllowed(page, 'cotizacion_final.php', 'Cotizacion final');
      await expectDenied(page, 'criterios_precotizacion.php');
      await context.close();
    }

    console.log('Auth role smoke check OK');
    console.log(`Captura: ${path.join(os.tmpdir(), 'servimeters-usuarios-roles.png')}`);
  } finally {
    await browser.close();
    execFileSync(php, [fixture, 'cleanup'], { encoding: 'utf8' });
  }
}

main().catch((error) => {
  try { execFileSync(php, [fixture, 'cleanup'], { encoding: 'utf8' }); } catch {}
  console.error(error.stack || error.message || error);
  process.exitCode = 1;
});
