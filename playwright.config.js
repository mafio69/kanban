const { defineConfig } = require('@playwright/test');

// e2e uruchamia realna appke PHP wpieta w baze TESTOWA (kanban_urzedowy_test),
// na osobnym porcie, zeby nie ruszac danych roboczych ani docker-owego 8090.
// Playwright zarzadza swoim test-results/ po swojemu; datowana kopie robi scripts/e2e.js.
module.exports = defineConfig({
    testDir: 'tests/e2e',
    // Wszystko pod jednym folderem e2e-historia/: _ostatni to swiezy przebieg Playwrighta
    // (czyszczony na starcie), datowane kopie robi obok scripts/e2e.js.
    outputDir: 'e2e-historia/_ostatni',

    // UWAGA: workers=1 (sekwencyjnie) - NIE zmieniac na wiecej!
    // Problem: wszystkie testy e2e dzielą tę samą bazę testową (kanban_urzedowy_test).
    // beforeEach() wywołuje __truncate__, ale przy równoległym uruchomieniu (workers > 1)
    // race condition: test A czyści bazę w momencie gdy test B już wstawił dane.
    // Objawy: losowe faily "element not found" przy 4 workerach, zawsze przechodzi przy 1.
    // Rozwiązanie długoterminowe: osobna baza per worker LUB transakcje z rollback.
    // Na razie: workers=1 gwarantuje poprawność (testy i tak są szybkie, ~4s całość).
    workers: 1,

    use: {
        baseURL: 'http://127.0.0.1:8099',
        screenshot: 'only-on-failure',
        trace: 'retain-on-failure',
    },
    webServer: {
        command: 'php -S 127.0.0.1:8099 -t public tests/e2e/router.php',
        url: 'http://127.0.0.1:8099/api/health',
        reuseExistingServer: false,
        env: {
            DB_HOST: process.env.DB_HOST || '127.0.0.1',
            DB_NAME: process.env.DB_NAME || 'kanban_urzedowy_test',
        },
    },
});
