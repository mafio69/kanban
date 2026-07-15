// Uruchamia Playwright, potem robi LEKKA, SPLASZCZONA, datowana kopie wynikow
// (wszystkie *.png + .last-run.json) z test-results/ do e2e-historia/DD-MM-GG-MM/.
// Datowany folder jest samowystarczalny (png i json obok siebie). Kod wyjscia = kod Playwrighta.

const { spawnSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const run = spawnSync('npx', ['playwright', 'test', ...process.argv.slice(2)], { stdio: 'inherit' });

const pad = (n) => String(n).padStart(2, '0');
const d = new Date();
const stamp = `${pad(d.getDate())}-${pad(d.getMonth() + 1)}-${pad(d.getHours())}-${pad(d.getMinutes())}`;

const src = path.join('e2e-historia', '_ostatni');
const dest = path.join('e2e-historia', stamp);

function collect(dir, out) {
    if (!fs.existsSync(dir)) {
        return out;
    }
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
        const p = path.join(dir, entry.name);
        entry.isDirectory() ? collect(p, out) : out.push(p);
    }
    return out;
}

let copied = 0;
for (const file of collect(src, [])) {
    const base = path.basename(file);
    if (base.endsWith('.png') || base === '.last-run.json') {
        fs.mkdirSync(dest, { recursive: true });
        fs.copyFileSync(file, path.join(dest, base));
        copied++;
    }
}

if (copied > 0) {
    console.log(`\nArchiwum e2e (${copied} plikow: png + json): ${dest}`);
}

process.exit(run.status ?? 1);
