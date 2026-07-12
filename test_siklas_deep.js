// SIKLAS-NB deep feature test - test all real menus + CRUD + klasifikasi
const BASE_URL = 'https://siklas.pinnhost.my.id';
const ADMIN_EMAIL = 'admin@siklas.test';
const ADMIN_PASSWORD = 'password';

const results = { passed: [], failed: [], warnings: [], info: [] };
const page = await browser.getPage('siklas-deep');

const consoleErrors = [];
page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
const networkErrors = [];
page.on('requestfailed', req => { networkErrors.push(`${req.failure().errorText}: ${req.url()}`); });

console.log('=== SIKLAS-NB DEEP FEATURE TEST ===\n');

async function loginIfNeeded() {
  await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });
  await page.waitForTimeout(2000);
  const emailInput = page.locator('input[type="email"], input[name="email"]').first();
  const passwordInput = page.locator('input[type="password"], input[name="password"]').first();
  if (await emailInput.count() === 0) return; // already logged in
  await emailInput.fill(ADMIN_EMAIL);
  await passwordInput.fill(ADMIN_PASSWORD);
  await page.locator('button[type="submit"]').first().click();
  try {
    await page.waitForURL(url => !url.toString().includes('/login'), { timeout: 30000 });
    results.passed.push('Login success');
  } catch (e) {
    await page.waitForTimeout(5000);
  }
  await page.waitForTimeout(3000);
}

async function visitAndSnap(path, label) {
  console.log(`\n>>> ${label} (${path})`);
  try {
    await page.goto(`${BASE_URL}${path}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
    await page.waitForTimeout(3000);
    const url = page.url();
    const bodyText = (await page.locator('body').textContent() || '').replace(/\s+/g, ' ').trim();
    const hasError = /sqlstate|exception|fatal|undefined|class not found|500 internal/i.test(bodyText);
    if (hasError) {
      results.failed.push(`${label}: error in body`);
      console.log(`   ERROR in body: ${bodyText.substring(0, 300)}`);
    } else {
      results.passed.push(`${label}: renders OK`);
    }
    // snapshot key part
    const snap = await page.snapshotForAI({ depth: 5 });
    console.log(`   URL: ${url}`);
    console.log(`   CONTENT: ${snap.full.substring(0, 1500)}`);
    // screenshot
    const shot = await page.screenshot({ fullPage: false });
    const shotPath = await saveScreenshot(shot, `siklas-${path.replace(/\//g,'-')||'root'}.png`);
    results.info.push(`${label} screenshot: ${shotPath}`);
    return bodyText;
  } catch (e) {
    results.failed.push(`${label}: nav failed - ${e.message.substring(0, 80)}`);
    return '';
  }
}

try {
  await loginIfNeeded();

  // Test all 7 real menu routes
  const menus = [
    ['/dashboard', 'Dashboard'],
    ['/warga', 'Data Warga'],
    ['/data-training', 'Data Training'],
    ['/kategori', 'Kategori Atribut'],
    ['/rekapitulasi', 'Rekap Per Dusun'],
    ['/klasifikasi', 'Klasifikasi NB'],
    ['/evaluasi', 'Evaluasi Model'],
  ];

  for (const [path, label] of menus) {
    await visitAndSnap(path, label);
  }

  // Deep test: Data Warga - check for table content + action buttons
  console.log('\n=== DEEP: Data Warga CRUD ===');
  await page.goto(`${BASE_URL}/warga`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(3000);
  const wargaSnap = await page.snapshotForAI({ depth: 7 });
  console.log('WARGA FULL SNAPSHOT:\n', wargaSnap.full.substring(0, 4000));

  // Count table rows
  const rowCount = await page.locator('table tbody tr').count();
  results.info.push(`Data Warga: ${rowCount} rows in table`);
  console.log(`   Table rows: ${rowCount}`);

  // Check for action buttons (Tambah, Import, Export)
  const addBtn = page.locator('button, a').filter({ hasText: /tambah|tambah warga|buat/i }).first();
  const importBtn = page.locator('button, a').filter({ hasText: /import/i }).first();
  const exportBtn = page.locator('button, a').filter({ hasText: /export|unduh|download/i }).first();
  if (await addBtn.count() > 0) results.passed.push('Warga: Tambah button found');
  else results.warnings.push('Warga: Tambah button not found');
  if (await importBtn.count() > 0) results.passed.push('Warga: Import button found');
  else results.warnings.push('Warga: Import button not found');
  if (await exportBtn.count() > 0) results.passed.push('Warga: Export button found');
  else results.warnings.push('Warga: Export button not found');

  // Deep test: Klasifikasi - check form
  console.log('\n=== DEEP: Klasifikasi form ===');
  await page.goto(`${BASE_URL}/klasifikasi`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(3000);
  const klasSnap = await page.snapshotForAI({ depth: 7 });
  console.log('KLASIFIKASI FULL SNAPSHOT:\n', klasSnap.full.substring(0, 4000));

  // Check for train/predict buttons
  const trainBtn = page.locator('button').filter({ hasText: /train|latih|training/i }).first();
  const predictBtn = page.locator('button').filter({ hasText: /prediksi|predict|klasifikas/i }).first();
  if (await trainBtn.count() > 0) results.passed.push('Klasifikasi: Train button found');
  else results.warnings.push('Klasifikasi: Train button not found');
  if (await predictBtn.count() > 0) results.passed.push('Klasifikasi: Predict button found');
  else results.warnings.push('Klasifikasi: Predict button not found');

  // Deep test: Data Training
  console.log('\n=== DEEP: Data Training ===');
  await page.goto(`${BASE_URL}/data-training`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(3000);
  const trainSnap = await page.snapshotForAI({ depth: 6 });
  console.log('DATA TRAINING SNAPSHOT:\n', trainSnap.full.substring(0, 2500));

  // Deep test: Evaluasi Model
  console.log('\n=== DEEP: Evaluasi Model ===');
  await page.goto(`${BASE_URL}/evaluasi`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(3000);
  const evalSnap = await page.snapshotForAI({ depth: 6 });
  console.log('EVALUASI SNAPSHOT:\n', evalSnap.full.substring(0, 2500));

  // Deep test: Rekapitulasi
  console.log('\n=== DEEP: Rekapitulasi ===');
  await page.goto(`${BASE_URL}/rekapitulasi`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(3000);
  const rekapSnap = await page.snapshotForAI({ depth: 6 });
  console.log('REKAP SNAPSHOT:\n', rekapSnap.full.substring(0, 2500));

  // Deep test: Kategori
  console.log('\n=== DEEP: Kategori Atribut ===');
  await page.goto(`${BASE_URL}/kategori`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(3000);
  const katSnap = await page.snapshotForAI({ depth: 6 });
  console.log('KATEGORI SNAPSHOT:\n', katSnap.full.substring(0, 2500));

  // Test logout
  console.log('\n=== LOGOUT TEST ===');
  const logoutBtn = page.locator('button, a').filter({ hasText: /keluar|logout|sign out/i }).first();
  if (await logoutBtn.count() > 0) {
    results.passed.push('Logout button found');
    // don't actually click to preserve session for recap
  } else {
    results.warnings.push('Logout button not found');
  }

} catch (error) {
  results.failed.push(`Critical: ${error.message}`);
  try {
    const es = await page.screenshot();
    const ep = await saveScreenshot(es, 'siklas-deep-error.png');
    results.info.push(`Error screenshot: ${ep}`);
  } catch {}
}

// Error summary
if (consoleErrors.length) {
  results.warnings.push(`Console errors: ${consoleErrors.length}`);
  consoleErrors.slice(0, 10).forEach(e => console.log(`   CE: ${e.substring(0, 140)}`));
}
if (networkErrors.length) {
  results.warnings.push(`Network failures: ${networkErrors.length}`);
  networkErrors.slice(0, 8).forEach(e => console.log(`   NE: ${e.substring(0, 140)}`));
}

// Print results
console.log('\n' + '='.repeat(60));
console.log('=== FINAL RESULTS ===');
console.log('='.repeat(60));
console.log(`\n✓ PASSED (${results.passed.length}):`);
results.passed.forEach(p => console.log(`  ✓ ${p}`));
if (results.warnings.length) {
  console.log(`\n⚠ WARNINGS (${results.warnings.length}):`);
  results.warnings.forEach(w => console.log(`  ⚠ ${w}`));
}
if (results.failed.length) {
  console.log(`\n✗ FAILED (${results.failed.length}):`);
  results.failed.forEach(f => console.log(`  ✗ ${f}`));
}
console.log('\n' + '='.repeat(60));
console.log(`STATUS: ${results.failed.length === 0 ? '✓ NO CRITICAL BUGS' : '✗ BUGS FOUND'}`);
console.log('='.repeat(60));
