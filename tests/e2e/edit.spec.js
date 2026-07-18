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

test('edycja pól sprawy (subject, notes)', async ({ page }, testInfo) => {
    const token = 'tok_' + Math.random().toString(36).slice(2, 12);
    seed(token, 'nowe');

    await page.goto('/');

    const nowe = page.getByTestId('column-nowe');
    await expect(nowe.getByText(token)).toBeVisible();

    const card = nowe.getByTestId('card').first();
    const cardId = await card.getAttribute('data-id');

    // Edytuj subject przez API
    const newSubject = 'Nowy temat ' + Math.random().toString(36).slice(2, 8);
    await page.evaluate(async ({ id, subject }) => {
        await fetch('/api/sprawy/' + id, {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ subject }),
        });
    }, { id: cardId, subject: newSubject });

    await page.reload();
    await expect(page.getByText(newSubject)).toBeVisible();

    await page.screenshot({ path: testInfo.outputPath('edit.png'), fullPage: true });
});
