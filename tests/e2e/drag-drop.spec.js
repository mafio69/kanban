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

test('drag&drop karty zmienia etap trwale (stan z DB po reload)', async ({ page }, testInfo) => {
    // Seeduj karte w Nowe
    const token = 'tok_' + Math.random().toString(36).slice(2, 12);
    seed(token, 'nowe');

    await page.goto('/');

    // Sprawdz czy karta jest w Nowe
    const nowe = page.getByTestId('column-nowe');
    await expect(nowe.getByText(token)).toBeVisible();

    // Pobierz pozycje karty i kolumny W trakcie
    const card = nowe.getByTestId('card').first();
    const wTrakcie = page.getByTestId('column-w_trakcie');

    // Symuluj drag&drop (HTML5 drag and drop)
    await card.dragTo(wTrakcie);

    // Poczekaj na odswiezenie
    await page.waitForTimeout(500);

    // Przeładuj stronę - karta powinna być w W trakcie
    await page.reload();

    const wTrakcieAfter = page.getByTestId('column-w_trakcie');
    await expect(wTrakcieAfter.getByText(token)).toBeVisible();

    // Karta nie powinna już być w Nowe
    const noweAfter = page.getByTestId('column-nowe');
    await expect(noweAfter.getByText(token)).toHaveCount(0);

    // Screenshot
    await page.screenshot({ path: testInfo.outputPath('drag-drop.png'), fullPage: true });
});
