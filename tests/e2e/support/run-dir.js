const fs = require('fs');
const path = require('path');

const pad = (n) => String(n).padStart(2, '0');

// Folder na artefakty jednego przebiegu: test-results/DD-MM-HH-MM (dzien-miesiac-godzina-minuta).
// Liczony raz i przekazywany do workerow przez RUN_DIR, zeby config i testy trafialy w ten sam katalog.
function runDir() {
    if (process.env.RUN_DIR) {
        return process.env.RUN_DIR;
    }

    const d = new Date();
    const stamp = `${pad(d.getDate())}-${pad(d.getMonth() + 1)}-${pad(d.getHours())}-${pad(d.getMinutes())}`;
    const dir = path.join('test-results', stamp);

    fs.mkdirSync(dir, { recursive: true });
    process.env.RUN_DIR = dir;

    return dir;
}

module.exports = { runDir };
