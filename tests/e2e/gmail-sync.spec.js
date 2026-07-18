const { test, expect } = require('@playwright/test');
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

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

test('sync Gmail z fixture pokazuje maile jako karty w Nowe, drugi sync nie dubluje', async ({ page }, testInfo) => {
    // Laduj fixture z watkami Gmail
    const fixturePath = path.join(__dirname, '..', 'fixtures', 'gmail-threads.json');
    const fixture = JSON.parse(fs.readFileSync(fixturePath, 'utf-8'));

    // Ustaw sciezke do fixture w env (zeby GmailSyncService czytal z pliku)
    process.env.GMAIL_FIXTURE_PATH = fixturePath;

    // Otworz appke
    await page.goto('/');

    // Na start kolumna Nowe powinna byc pusta
    const nowe = page.getByTestId('column-nowe');
    await expect(nowe.getByTestId('card')).toHaveCount(0);

    // Kliknij Odswiez
    await page.getByTestId('btn-refresh').click();

    // Poczekaj az karty sie pojawią
    for (const thread of fixture.threads) {
        await expect(nowe.getByText(thread.snippet)).toBeVisible();
    }

    // Sprawdz czy liczba kart = liczba watkow
    const cards = nowe.getByTestId('card');
    await expect(cards).toHaveCount(fixture.threads.length);

    // Kliknij Odswiez ponownie - nie powinno byc duplikatow
    await page.getByTestId('btn-refresh').click();
    await expect(cards).toHaveCount(fixture.threads.length);

    // Screenshot
    await page.screenshot({ path: testInfo.outputPath('gmail-sync.png'), fullPage: true });
});
