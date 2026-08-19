#!/usr/bin/env node
/**
 * Prerenders each public route into static HTML so crawlers that do not run
 * JavaScript (Bing, many AI answer engines, social scrapers) see real content.
 * Runs after `vite build` + `vite build --ssr`.
 */
import { readFileSync, writeFileSync, mkdirSync, existsSync } from 'node:fs';
import { resolve, dirname } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const template = readFileSync(resolve(root, 'dist/index.html'), 'utf8');
const ssrEntry = resolve(root, 'dist-ssr/entry-server.js');

if (!existsSync(ssrEntry)) {
  console.error('[prerender] Missing dist-ssr/entry-server.js — run the SSR build first.');
  process.exit(1);
}

const { render } = await import(pathToFileURL(ssrEntry).href);

const routes = ['/', '/portfolio', '/contact'];

for (const url of routes) {
  const { html, head } = render(url);

  let page = template;
  // Replace the fallback head tags with the route-specific ones.
  page = page.replace(
    /<!--\s*seo:start\s*-->[\s\S]*?<!--\s*seo:end\s*-->/,
    head
  );
  page = page.replace(
    '<div id="root"></div>',
    `<div id="root">${html}</div>`
  );

  const out =
    url === '/'
      ? resolve(root, 'dist/index.html')
      : resolve(root, `dist${url}/index.html`);
  mkdirSync(dirname(out), { recursive: true });
  writeFileSync(out, page);
  console.log(`[prerender] ${url} → ${out.replace(root + '/', '')}`);
}
