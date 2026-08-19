#!/usr/bin/env node
import { existsSync, mkdirSync, cpSync, readFileSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const repoRoot = resolve(__dirname, '..');

// 1. Copy PHP endpoints from public/ into dist/
for (const file of ['contacto.php']) {
  const source = resolve(repoRoot, 'public', file);
  if (existsSync(source)) {
    cpSync(source, resolve(repoRoot, 'dist', file));
    console.log(`[postbuild] PHP endpoint copied: public/${file} → dist/${file}`);
  }
}

// 1b. Copy PHP helper libs into dist/lib/
mkdirSync(resolve(repoRoot, 'dist/lib'), { recursive: true });
for (const file of ['load_env.php', 'append_csv.php']) {
  const source = resolve(repoRoot, 'public/lib', file);
  if (existsSync(source)) {
    cpSync(source, resolve(repoRoot, 'dist/lib', file));
    console.log(`[postbuild] PHP lib copied: public/lib/${file} → dist/lib/${file}`);
  }
}

// 2. Fail loudly if the deployed endpoint is stale.
const endpoint = resolve(repoRoot, 'dist/contacto.php');
if (!existsSync(endpoint) || !readFileSync(endpoint, 'utf8').includes('contact_debug_log')) {
  console.error('[postbuild] dist/contacto.php is missing the contact_debug_log handler.');
  process.exit(1);
}

// 3. Copy PHPMailer into dist/lib/
const candidates = [
  resolve(repoRoot, '../lib/PHPMailer/src'), // server layout
  resolve(repoRoot, 'lib/PHPMailer/src'),    // fallback: inside repo
];
const src = candidates.find((p) => existsSync(p));
const dest = resolve(repoRoot, 'dist/lib/PHPMailer/src');
if (!src) {
  console.log('[postbuild] PHPMailer not found locally — skipping copy (OK for dev/preview).');
  process.exit(0);
}
mkdirSync(dirname(dest), { recursive: true });
cpSync(src, dest, { recursive: true });
console.log(`[postbuild] PHPMailer copied: ${src} → ${dest}`);
