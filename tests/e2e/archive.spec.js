const { test, expect } = require('@playwright/test');
const { execSync } = require('child_process');

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

test('archiwizacja i usuwanie spraw', async ({ page }, testInfo) => {
    // Seeduj karte
    const token = 'tok_' + Math.random().toString(36).slice(2, 12);
    seed(token, 'nowe');

    await page.goto('/');

    // Sprawdz czy karta jest widoczna
    const nowe = page.getByTestId('column-nowe');
    await expect(nowe.getByText(token)).toBeVisible();

    // Archiwizuj przez API
    const card = nowe.getByTestId('card').first();
    const cardId = await card.getAttribute('data-id');

    await page.evaluate(async (id) => {
        await fetch('/api/sprawy/' + id + '/archive', {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ archived: true }),
        });
    }, cardId);

    // Karta powinna zniknac z Nowe (bo archived=false domyslnie)
    await page.reload();
    await expect(nowe.getByText(token)).toHaveCount(0);

    // Pokaz zarchiwizowane - karta powinna byc widoczna
    await page.getByLabel('pokaż zarchiwizowane').check();
    await expect(page.getByText(token)).toBeVisible();

    // Screenshot
    await page.screenshot({ path: testInfo.outputPath('archive.png'), fullPage: true });
});

test('usuwanie sprawy', async ({ page }, testInfo) => {
    const token = 'tok_' + Math.random().toString(36).slice(2, 12);
    seed(token, 'nowe');

    await page.goto('/');

    const nowe = page.getByTestId('column-nowe');
    await expect(nowe.getByText(token)).toBeVisible();

    const card = nowe.getByTestId('card').first();
    const cardId = await card.getAttribute('data-id');

    // Usun przez API
    await page.evaluate(async (id) => {
        await fetch('/api/sprawy/' + id, { method: 'DELETE' });
    }, cardId);

    await page.reload();
    await expect(nowe.getByText(token)).toHaveCount(0);

    await page.screenshot({ path: testInfo.outputPath('delete.png'), fullPage: true });
});
