import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({
  viewport: { width: 360, height: 800 },
  deviceScaleFactor: 1,
  isMobile: true,
  hasTouch: true,
});

const errors = [];
page.on('console', (message) => {
  if (message.type() === 'error') errors.push(message.text());
});
page.on('pageerror', (error) => errors.push(error.message));

await page.goto('http://127.0.0.1:4175', { waitUntil: 'networkidle' });
await page.waitForTimeout(350);
await page.screenshot({ path: 'qa/screenshots/01-orders.png', fullPage: true });

await page.getByRole('button', { name: 'Nuevo pedido' }).click();
await page.waitForTimeout(350);
await page.screenshot({ path: 'qa/screenshots/02-catalog.png', fullPage: true });

await page.getByRole('button', { name: /Agregar Varilla/ }).click();
await page.getByRole('button', { name: /Agregar Cemento/ }).click();
await page.getByRole('button', { name: /Ver pedido/ }).click();
await page.waitForTimeout(350);
await page.screenshot({ path: 'qa/screenshots/03-review.png', fullPage: true });

await page.getByRole('button', { name: 'Generar' }).click();
await page.waitForTimeout(350);
await page.screenshot({ path: 'qa/screenshots/04-confirm.png', fullPage: true });
await page.getByRole('button', { name: 'Sí, generar pedido' }).click();
await page.waitForTimeout(350);
await page.screenshot({ path: 'qa/screenshots/05-ticket.png', fullPage: true });
await page.getByRole('button', { name: 'Imprimir ticket' }).click();
await page.waitForTimeout(350);
await page.screenshot({ path: 'qa/screenshots/06-printer.png', fullPage: true });

await page.reload({ waitUntil: 'networkidle' });
await page.waitForTimeout(350);
await page.getByRole('button', { name: 'Abrir cuenta' }).click();
await page.waitForTimeout(350);
await page.screenshot({ path: 'qa/screenshots/07-settings.png', fullPage: true });
await page.getByRole('button', { name: 'Cerrar sesión' }).click();
await page.waitForTimeout(350);
await page.screenshot({ path: 'qa/screenshots/08-login.png', fullPage: true });

const layout = await page.evaluate(() => ({
  viewportWidth: window.innerWidth,
  documentWidth: document.documentElement.scrollWidth,
  title: document.title,
}));

await browser.close();

if (errors.length) {
  console.error(JSON.stringify({ errors, layout }, null, 2));
  process.exit(1);
}

if (layout.documentWidth > layout.viewportWidth) {
  console.error(JSON.stringify({ error: 'Horizontal overflow', layout }, null, 2));
  process.exit(1);
}

console.log(JSON.stringify({ ok: true, screenshots: 8, layout }, null, 2));
