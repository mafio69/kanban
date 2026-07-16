const { test, expect } = require('@playwright/test');
const { execSync } = require('child_process');

// Seed leci do bazy TESTOWEJ (te same zmienne co webServer w playwright.config.js).
const seedEnv = {
    ...process.env,
    DB_HOST: process.env.DB_HOST || '127.0.0.1',
    DB_NAME: process.env.DB_NAME || 'kanban_urzedowy_test',
};

function seed(subject, stage) {
    execSync(`php tests/e2e/seed.php ${JSON.stringify(subject)} ${stage}`, { env: seedEnv, stdio: 'pipe' });
}

test.beforeEach(() => {
    execSync('php tests/e2e/seed.php __truncate__', { env: seedEnv, stdio: 'pipe' });
});

test('sprawa z losowym tokenem renderuje sie w swojej kolumnie', async ({ page }, testInfo) => {
    // Token losowy per uruchomienie — musi realnie przejsc DB -> API -> render, nie da sie zafalszowac.
    const token = 'tok_' + Math.random().toString(36).slice(2, 12);
    seed(token, 'czekam');

    await page.goto('/');

    const czekam = page.getByTestId('column-czekam');
    await expect(czekam.getByText(token)).toBeVisible();

    // Ten sam token NIE moze pojawic sie w innej kolumnie.
    const nowe = page.getByTestId('column-nowe');
    await expect(nowe.getByText(token)).toHaveCount(0);

    // Screenshot idzie do folderu Playwrighta; datowana kopie robi scripts/e2e.js.
    await page.screenshot({ path: testInfo.outputPath('board.png'), fullPage: true });
});
