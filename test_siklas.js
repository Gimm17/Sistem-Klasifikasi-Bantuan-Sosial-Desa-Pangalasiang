// SIKLAS-NB Website Test - dev-browser compatible
// Usage: dev-browser run test_siklas.js

const BASE_URL = 'https://siklas.pinnhost.my.id';
const ADMIN_EMAIL = 'admin@siklas.test';
const ADMIN_PASSWORD = 'password';

const results = { passed: [], failed: [], warnings: [] };

const page = await browser.getPage('siklas-test');

// Monitor console errors
const consoleErrors = [];
page.on('console', msg => {
  if (msg.type() === 'error') consoleErrors.push(msg.text());
});

// Monitor network failures
const networkErrors = [];
page.on('requestfailed', req => {
  networkErrors.push(`${req.failure().errorText}: ${req.url()}`);
});

console.log('=== SIKLAS-NB WEBSITE TEST ===\n');

try {
  // 1. TEST: Homepage load
  console.log('1. Testing homepage load...');
  await page.goto(BASE_URL, { waitUntil: 'domcontentloaded', timeout: 15000 });

  const title = await page.title();
  const url = page.url();

  if (title || url.includes('siklas')) {
    results.passed.push('Homepage loads successfully');
    console.log(`   Title: ${title}`);
    console.log(`   URL: ${url}`);
  } else {
    results.failed.push('Homepage load failed or unexpected redirect');
  }

  // Wait for any async errors (like the 401)
  await page.waitForTimeout(3000);

  // Check for expected 401 on /api/me
  const has401 = consoleErrors.some(e => e.includes('401') && e.includes('/api/me'));
  if (has401) {
    results.warnings.push('401 on /api/me (expected for unauthenticated user)');
  }

  // 2. TEST: Login page
  console.log('\n2. Testing login page...');

  // Check if already on login page or need to navigate
  const currentUrl = page.url();
  if (!currentUrl.includes('/login')) {
    await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded' });
  }

  await page.waitForTimeout(2000);

  // Find login form inputs
  const emailInput = page.locator('input[type="email"], input[name="email"]').first();
  const passwordInput = page.locator('input[type="password"], input[name="password"]').first();

  const emailExists = await emailInput.count() > 0;
  const passwordExists = await passwordInput.count() > 0;

  if (emailExists && passwordExists) {
    results.passed.push('Login form found with email & password fields');
  } else {
    results.failed.push('Login form incomplete or not found');
    throw new Error('Cannot proceed without login form');
  }

  // 3. TEST: Perform login
  console.log('\n3. Testing login...');

  await emailInput.fill(ADMIN_EMAIL);
  await passwordInput.fill(ADMIN_PASSWORD);

  // Find and click login button
  const loginBtn = page.locator('button[type="submit"]').first();
  const btnExists = await loginBtn.count() > 0;

  if (!btnExists) {
    results.failed.push('Login submit button not found');
    throw new Error('No login button');
  }

  await loginBtn.click();

  // Wait for navigation or error
  try {
    await page.waitForURL(url => !url.includes('/login'), { timeout: 10000 });
    results.passed.push('Login successful - redirected from login page');
    console.log(`   Redirected to: ${page.url()}`);
  } catch (e) {
    results.failed.push('Login failed - still on login page or timeout');
    console.log(`   Still on: ${page.url()}`);
    throw new Error('Login failed');
  }

  await page.waitForTimeout(2000);

  // 4. TEST: Dashboard/authenticated area
  console.log('\n4. Testing dashboard...');

  const mainContent = page.locator('main, [class*="dashboard"], [class*="content"], .container').first();
  const hasContent = await mainContent.count() > 0;

  if (hasContent) {
    results.passed.push('Dashboard/content area found');
  } else {
    results.warnings.push('Dashboard content area not clearly identified');
  }

  // 5. TEST: Navigation
  console.log('\n5. Testing navigation...');

  const navLinks = page.locator('nav a, [class*="sidebar"] a, [class*="menu"] a');
  const navCount = await navLinks.count();

  if (navCount > 0) {
    results.passed.push(`Navigation found with ${navCount} links`);

    // Get nav text
    const navTexts = [];
    for (let i = 0; i < Math.min(navCount, 10); i++) {
      const text = await navLinks.nth(i).textContent();
      if (text.trim()) navTexts.push(text.trim());
    }
    console.log(`   Nav items: ${navTexts.join(', ')}`);
  } else {
    results.warnings.push('No navigation links found');
  }

  // 6. TEST: Warga (residents) data feature
  console.log('\n6. Testing Warga/Data features...');

  const allLinks = page.locator('a');
  const linkCount = await allLinks.count();
  let wargaLink = null;

  for (let i = 0; i < linkCount; i++) {
    const text = await allLinks.nth(i).textContent();
    const href = await allLinks.nth(i).getAttribute('href');

    if (text && (text.toLowerCase().includes('warga') ||
                 text.toLowerCase().includes('data') ||
                 (href && href.includes('/warga')))) {
      wargaLink = allLinks.nth(i);
      break;
    }
  }

  if (wargaLink) {
    results.passed.push('Warga/Data menu link found');

    await wargaLink.click();
    await page.waitForTimeout(3000);

    // Check for table
    const table = page.locator('table, [class*="table"], [class*="grid"]').first();
    const hasTable = await table.count() > 0;

    if (hasTable) {
      results.passed.push('Data table found on Warga page');

      // Count rows
      const rows = await page.locator('table tr, [class*="table"] tr').count();
      console.log(`   Table has ~${rows} rows`);
    } else {
      results.warnings.push('Data table not found on Warga page');
    }

    // Check for add button
    const addBtn = page.locator('button, a').filter({ hasText: /tambah|add|create|buat/i }).first();
    const hasAddBtn = await addBtn.count() > 0;

    if (hasAddBtn) {
      results.passed.push('Add/Create button found');
    } else {
      results.warnings.push('Add/Create button not found');
    }
  } else {
    results.warnings.push('Warga/Data menu link not found');
  }

  // 7. TEST: Import/Export
  console.log('\n7. Testing import/export features...');

  const importBtn = page.locator('button, a').filter({ hasText: /import/i }).first();
  const exportBtn = page.locator('button, a').filter({ hasText: /export|download/i }).first();

  if (await importBtn.count() > 0) {
    results.passed.push('Import button found');
  } else {
    results.warnings.push('Import button not found');
  }

  if (await exportBtn.count() > 0) {
    results.passed.push('Export button found');
  } else {
    results.warnings.push('Export button not found');
  }

  // 8. TEST: Classification/Prediction
  console.log('\n8. Testing classification features...');

  const classifyBtn = page.locator('button, a').filter({
    hasText: /klasifikasi|prediksi|predict|naive\s*bayes/i
  }).first();

  if (await classifyBtn.count() > 0) {
    results.passed.push('Classification/Prediction feature found');
  } else {
    results.warnings.push('Classification feature not clearly visible');
  }

  // 9. TEST: Reports
  console.log('\n9. Testing reports...');

  const reportLink = page.locator('a').filter({ hasText: /laporan|report/i }).first();

  if (await reportLink.count() > 0) {
    results.passed.push('Reports menu found');
  } else {
    results.warnings.push('Reports menu not found');
  }

  // 10. Take screenshot for review
  console.log('\n10. Capturing screenshot...');
  const screenshot = await page.screenshot({ fullPage: false });
  const screenshotPath = await saveScreenshot(screenshot, 'siklas-final-state.png');
  console.log(`   Screenshot saved: ${screenshotPath}`);

} catch (error) {
  results.failed.push(`Critical error: ${error.message}`);
  console.error(`\nCRITICAL ERROR: ${error.message}`);

  // Debug screenshot
  try {
    const errorShot = await page.screenshot();
    const errorPath = await saveScreenshot(errorShot, 'siklas-error-state.png');
    console.log(`Error screenshot: ${errorPath}`);
  } catch (e) {
    console.error('Could not capture error screenshot');
  }
}

// Final error checks
console.log('\n11. Checking for errors...');

if (consoleErrors.length > 0) {
  results.warnings.push(`Console errors: ${consoleErrors.length} found`);
  consoleErrors.slice(0, 5).forEach(err => {
    console.log(`   - ${err.substring(0, 150)}`);
  });
} else {
  results.passed.push('No console errors');
}

if (networkErrors.length > 0) {
  results.failed.push(`Network errors: ${networkErrors.length} found`);
  networkErrors.forEach(err => {
    console.log(`   - ${err.substring(0, 150)}`);
  });
} else {
  results.passed.push('No network failures');
}

// Print results
console.log('\n' + '='.repeat(50));
console.log('=== TEST RESULTS ===');
console.log('='.repeat(50) + '\n');

console.log(`✓ PASSED (${results.passed.length}):`);
results.passed.forEach(p => console.log(`  ✓ ${p}`));

if (results.warnings.length > 0) {
  console.log(`\n⚠ WARNINGS (${results.warnings.length}):`);
  results.warnings.forEach(w => console.log(`  ⚠ ${w}`));
}

if (results.failed.length > 0) {
  console.log(`\n✗ FAILED (${results.failed.length}):`);
  results.failed.forEach(f => console.log(`  ✗ ${f}`));
}

console.log('\n' + '='.repeat(50));
console.log('SUMMARY:');
console.log(`  Total checks: ${results.passed.length + results.warnings.length + results.failed.length}`);
console.log(`  Status: ${results.failed.length === 0 ? '✓ ALL CRITICAL TESTS PASSED' : '✗ SOME TESTS FAILED'}`);
console.log(`  Current URL: ${page.url()}`);
console.log('='.repeat(50));
