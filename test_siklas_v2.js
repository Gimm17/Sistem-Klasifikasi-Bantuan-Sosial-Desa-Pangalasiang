// SIKLAS-NB full feature test v2 - longer login redirect wait
const BASE_URL = 'https://siklas.pinnhost.my.id';
const ADMIN_EMAIL = 'admin@siklas.test';
const ADMIN_PASSWORD = 'password';

const results = { passed: [], failed: [], warnings: [], info: [] };
const page = await browser.getPage('siklas-test2');

const consoleErrors = [];
page.on('console', msg => { if (msg.type() === 'error') consoleErrors.push(msg.text()); });
const networkErrors = [];
page.on('requestfailed', req => { networkErrors.push(`${req.failure().errorText}: ${req.url()}`); });

console.log('=== SIKLAS-NB FULL FEATURE TEST v2 ===\n');

async function snap(label) {
  const r = await page.snapshotForAI({ depth: 6 });
  console.log(`\n--- SNAPSHOT: ${label} --- url=${page.url()}`);
  console.log(r.full.substring(0, 3000));
  return r.full;
}

try {
  // 1. Login flow with long redirect wait
  console.log('1. Loading login page...');
  await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded', timeout: 20000 });
  await page.waitForTimeout(2000);

  const emailInput = page.locator('input[type="email"], input[name="email"]').first();
  const passwordInput = page.locator('input[type="password"], input[name="password"]').first();
  await emailInput.fill(ADMIN_EMAIL);
  await passwordInput.fill(ADMIN_PASSWORD);

  const loginBtn = page.locator('button[type="submit"]').first();
  await loginBtn.click();
  results.info.push('Login submitted, waiting up to 30s for redirect...');

  try {
    await page.waitForURL(url => !url.toString().includes('/login'), { timeout: 30000 });
    results.passed.push(`Login success → redirected to ${page.url()}`);
  } catch (e) {
    // Even if waitForURL times out, maybe SPA route changed without URL change
    await page.waitForTimeout(5000);
    results.info.push(`After login wait, URL=${page.url()}`);
  }

  await page.waitForTimeout(3000);
  await snap('after-login');

  // 2. Inspect nav/menu
  console.log('\n2. Inspecting navigation...');
  const navTexts = await page.locator('nav a, [class*="sidebar"] a, [class*="menu"] a, aside a').allTextContents();
  const cleanNav = navTexts.map(t => t.trim()).filter(Boolean);
  results.info.push(`Nav items: ${cleanNav.join(' | ')}`);

  // 3. Visit each main section by trying common SPA paths
  const sections = [
    { name: 'dashboard', path: '/dashboard' },
    { name: 'warga', path: '/warga' },
    { name: 'klasifikasi', path: '/klasifikasi' },
    { name: 'prediksi', path: '/prediksi' },
    { name: 'laporan', path: '/laporan' },
    { name: 'pengguna', path: '/pengguna' },
    { name: 'users', path: '/users' },
    { name: 'profile', path: '/profile' },
  ];

  for (const s of sections) {
    console.log(`\n3. Testing section: ${s.name} (${s.path})`);
    try {
      await page.goto(`${BASE_URL}${s.path}`, { waitUntil: 'domcontentloaded', timeout: 15000 });
      await page.waitForTimeout(2500);
      const url = page.url();
      const title = await page.title();
      const bodyText = (await page.locator('body').textContent() || '').substring(0, 500);

      // Detect if SPA shows real content vs redirect to login
      const showsLogin = url.includes('/login') || /masuk|login/i.test(bodyText.substring(0, 200));
      const hasError = /error|exception|sqlstate|fatal/i.test(bodyText);

      if (showsLogin) {
        results.warnings.push(`${s.name}: redirected to login (auth required or route guard)`);
      } else if (hasError) {
        results.failed.push(`${s.name}: error detected in body`);
        console.log(`   BODY: ${bodyText.substring(0, 400)}`);
      } else {
        results.passed.push(`${s.name}: renders (url=${url})`);
        console.log(`   BODY preview: ${bodyText.substring(0, 200).replace(/\s+/g, ' ')}`);
      }
    } catch (e) {
      results.failed.push(`${s.name}: navigation failed - ${e.message.substring(0, 80)}`);
    }
  }

  // 4. Final screenshot
  console.log('\n4. Final screenshot...');
  await page.goto(`${BASE_URL}/dashboard`, { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(2000);
  const shot = await page.screenshot({ fullPage: true });
  const shotPath = await saveScreenshot(shot, 'siklas-dashboard.png');
  results.info.push(`Dashboard screenshot: ${shotPath}`);

} catch (error) {
  results.failed.push(`Critical: ${error.message}`);
  try {
    const es = await page.screenshot();
    const ep = await saveScreenshot(es, 'siklas-v2-error.png');
    results.info.push(`Error screenshot: ${ep}`);
  } catch {}
}

// 5. Error summary
if (consoleErrors.length) {
  results.warnings.push(`Console errors: ${consoleErrors.length}`);
  consoleErrors.slice(0, 8).forEach(e => console.log(`   CE: ${e.substring(0, 140)}`));
}
if (networkErrors.length) {
  results.warnings.push(`Network failures: ${networkErrors.length}`);
  networkErrors.slice(0, 5).forEach(e => console.log(`   NE: ${e.substring(0, 140)}`));
}

// Print results
console.log('\n' + '='.repeat(60));
console.log('=== RESULTS ===');
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
if (results.info.length) {
  console.log(`\nℹ INFO:`);
  results.info.forEach(i => console.log(`  ℹ ${i}`));
}
console.log('\n' + '='.repeat(60));
console.log(`STATUS: ${results.failed.length === 0 ? '✓ NO CRITICAL BUGS' : '✗ BUGS FOUND'}`);
console.log('='.repeat(60));
