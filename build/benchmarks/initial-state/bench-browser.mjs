/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Client side cost of each initial state variant in real browsers.
 *
 * Usage: node bench-browser.mjs [--iterations=30] [--browsers=chromium,firefox]
 *                               [--throttle=4] [--fixtures=files,xlarge] [--out=results.json]
 *
 * Per page load it records:
 * - parse: domInteractive - responseStart, i.e. the main thread cost of
 *   parsing the document including any inline script
 * - block: main thread time spent between two inline marks around the markup
 * - first: first loadState() call (cold)
 * - all:   first loadState() call plus one call for every other key (cold)
 * - warm:  one pass over all keys once everything is cached
 */

import { execFileSync } from 'node:child_process'
import { createServer } from 'node:http'
import { readdirSync, readFileSync, writeFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { brotliCompressSync, constants, gzipSync } from 'node:zlib'
import { chromium, firefox, webkit } from 'playwright'

const dir = dirname(fileURLToPath(import.meta.url))
const args = Object.fromEntries(process.argv.slice(2).map((arg) => arg.replace(/^--/, '').split('=')))
const iterations = Number(args.iterations ?? 30)
const browsers = (args.browsers ?? 'chromium,firefox').split(',')
const throttle = Number(args.throttle ?? 1)
const fixtureNames = args.fixtures?.split(',')
	?? readdirSync(join(dir, 'fixtures')).filter((file) => file.endsWith('.json')).map((file) => file.slice(0, -5))
const NONCE = 'benchnonce'

// @nextcloud/initial-state 3.0.0 dist/index.js, error logging trimmed
const LIB_V3 = `function loadState(app, key, fallback) {
	const selector = \`#initial-state-\${app}-\${key}\`;
	if (window._nc_initial_state?.has(selector)) {
		return window._nc_initial_state.get(selector);
	} else if (!window._nc_initial_state) {
		window._nc_initial_state = new Map();
	}
	const elem = document.querySelector(selector);
	if (elem === null) {
		if (fallback !== undefined) {
			return fallback;
		}
		throw new Error(\`Could not find initial state \${key} of \${app}\`);
	}
	try {
		const parsedValue = JSON.parse(atob(elem.value));
		window._nc_initial_state.set(selector, parsedValue);
		return parsedValue;
	} catch (error) {
		if (fallback !== undefined) {
			return fallback;
		}
		throw new Error(\`Could not parse initial state \${key} of \${app}\`, { cause: error });
	}
}`

// Parses the single JSON blob on first access to window._nc_initial_state,
// as core could do it before any app bundle runs.
const SINGLE_BOOTSTRAP = `Object.defineProperty(window, '_nc_initial_state', {
	configurable: true,
	get() {
		const elem = document.getElementById('initial-state');
		const states = Object.freeze(elem ? JSON.parse(elem.textContent) : {});
		const value = Object.freeze({ has: (k) => Object.hasOwn(states, k), get: (k) => states[k], set() {} });
		Object.defineProperty(window, '_nc_initial_state', { configurable: true, writable: true, value });
		return value;
	},
});`

// Facade that parses each <script type="application/json"> lazily on first get(),
// keyed by the selector @nextcloud/initial-state 3.x looks up.
const PER_KEY_BOOTSTRAP = `{
	const cache = new Map();
	window._nc_initial_state = Object.freeze({
		has: (k) => cache.has(k) || document.getElementById(k.slice(1)) !== null,
		get(k) {
			if (!cache.has(k)) {
				const elem = document.getElementById(k.slice(1));
				cache.set(k, elem === null ? undefined : JSON.parse(elem.textContent));
			}
			return cache.get(k);
		},
		set() {},
	});
}`

const LOADERS = {
	none: '',
	'input-base64': LIB_V3,
	'input-json': LIB_V3.replace('JSON.parse(atob(elem.value))', 'JSON.parse(elem.value)'),
	'script-json-per-key': PER_KEY_BOOTSTRAP + LIB_V3,
	'script-json-single': SINGLE_BOOTSTRAP + LIB_V3,
	'inline-js-object': LIB_V3,
	'inline-js-map': LIB_V3,
	'inline-js-json-parse': LIB_V3,
}

const HARNESS = `document.addEventListener('DOMContentLoaded', () => {
	const keys = KEYS;
	const first = FIRST;
	const split = (k) => [k.slice(0, k.indexOf('-')), k.slice(k.indexOf('-') + 1)];
	const now = () => performance.now();
	const result = { block: window.__t1 - window.__t0, isolated: self.crossOriginIsolated };
	if (keys.length && typeof loadState === 'function') {
		const t2 = now();
		loadState(...split(first));
		const t3 = now();
		for (const k of keys) loadState(...split(k));
		const t4 = now();
		for (let r = 0; r < 20; r++) for (const k of keys) loadState(...split(k));
		const t5 = now();
		Object.assign(result, { first: t3 - t2, all: t4 - t2, warm: (t5 - t4) / 20 });
		if (CHECK) result.values = JSON.stringify(Object.fromEntries(keys.map((k) => [k, loadState(...split(k))])));
	}
	const nav = performance.getEntriesByType('navigation')[0];
	result.parse = nav.domInteractive - nav.responseStart;
	window.__result = result;
});`

/**
 * Builds a full page around the markup of one variant. `token` makes every
 * inline script unique so no browser compile cache is hit between loads.
 */
function page(markup, variant, keys, first, token, check) {
	const harness = HARNESS
		.replace('KEYS', JSON.stringify(variant === 'none' ? [] : keys))
		.replace('FIRST', JSON.stringify(first))
		.replace('CHECK', String(check))
	return `<!DOCTYPE html>
<html lang="en">
<head><meta charset="utf-8"><title>initial state benchmark</title></head>
<body>
<script>/*${token}*/window.__t0=performance.now()</script>
${markup.replaceAll(`<script nonce="${NONCE}">`, `<script nonce="${NONCE}">/*${token}*/`)}
<script>/*${token}*/window.__t1=performance.now()</script>
<div id="content"></div>
<script>/*${token}*/${LOADERS[variant]}
${harness}</script>
</body>
</html>`
}

const fixtures = fixtureNames.map((name) => {
	const path = join(dir, 'fixtures', `${name}.json`)
	const states = JSON.parse(readFileSync(path, 'utf8'))
	const keys = Object.keys(states)
	const markup = JSON.parse(execFileSync('php', [join(dir, 'render.php'), path, NONCE], { maxBuffer: 1 << 28 }).toString())
	const expected = JSON.stringify(Object.fromEntries(keys.map((k) => [k, JSON.parse(states[k])])))
	const sizes = Object.fromEntries(Object.entries(markup).map(([variant, html]) => [variant, {
		raw: Buffer.byteLength(html),
		gzip: gzipSync(html, { level: 6 }).length,
		brotli: brotliCompressSync(html, { params: { [constants.BROTLI_PARAM_QUALITY]: 5 } }).length,
	}]))
	return { name, keys, first: keys.includes('core-config') ? 'core-config' : keys[0], markup, expected, sizes }
})
const variants = Object.keys(fixtures[0].markup)

const pending = new Map()
const server = createServer((req, res) => {
	const html = pending.get(req.url)
	res.writeHead(html ? 200 : 404, {
		'Content-Type': 'text/html; charset=utf-8',
		'Cross-Origin-Opener-Policy': 'same-origin',
		'Cross-Origin-Embedder-Policy': 'require-corp',
		'Cache-Control': 'no-store',
	})
	res.end(html ?? '')
})
await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve))
const origin = `http://localhost:${server.address().port}`

const shuffle = (list) => list.map((v) => [Math.random(), v]).sort((a, b) => a[0] - b[0]).map(([, v]) => v)
const median = (list) => {
	const sorted = list.toSorted((a, b) => a - b)
	return sorted[Math.floor(sorted.length / 2)]
}

const results = []
let counter = 0
for (const browserName of browsers) {
	const browser = await { chromium, firefox, webkit }[browserName].launch()
	const context = await browser.newContext()
	const tab = await context.newPage()
	const cdp = browserName === 'chromium' && throttle > 1 ? await context.newCDPSession(tab) : null
	const samples = {}

	// first round only verifies the values and warms up the browser
	for (let iteration = -1; iteration < iterations; iteration++) {
		for (const fixture of fixtures) {
			for (const variant of shuffle(variants)) {
				const token = `${Date.now()}-${counter++}`
				const url = `/${token}`
				pending.set(url, page(fixture.markup[variant], variant, fixture.keys, fixture.first, token, iteration === -1))
				await cdp?.send('Emulation.setCPUThrottlingRate', { rate: throttle })
				await tab.goto(origin + url, { waitUntil: 'load' })
				const result = await tab.evaluate(() => window.__result)
				pending.delete(url)

				if (iteration === -1) {
					if (!result.isolated) {
						console.warn(`${browserName}: page is not cross origin isolated, timers are coarse`)
					}
					if (variant !== 'none' && result.values !== fixture.expected) {
						throw new Error(`${browserName}/${fixture.name}/${variant}: decoded values differ from fixture`)
					}
					continue
				}
				const key = `${fixture.name}/${variant}`
				samples[key] ??= { parse: [], block: [], first: [], all: [], warm: [] }
				for (const metric of Object.keys(samples[key])) {
					samples[key][metric].push(result[metric] ?? 0)
				}
			}
		}
		process.stderr.write(`\r${browserName}: ${iteration + 1}/${iterations}`)
	}
	process.stderr.write('\n')
	await browser.close()

	for (const fixture of fixtures) {
		const baseline = median(samples[`${fixture.name}/none`].parse)
		for (const variant of variants) {
			const s = samples[`${fixture.name}/${variant}`]
			const parse = median(s.parse) - baseline
			results.push({
				browser: browserName + (cdp ? ` ${throttle}x throttled` : ''),
				fixture: fixture.name,
				variant,
				...fixture.sizes[variant],
				parse,
				block: median(s.block),
				first: median(s.first),
				all: median(s.all),
				warm: median(s.warm),
				total: median(s.parse.map((p, i) => p - baseline + s.all[i])),
			})
		}
	}
}
server.close()

if (args.out) {
	writeFileSync(args.out, JSON.stringify({ iterations, date: new Date().toISOString(), results }, null, '\t') + '\n')
}

const ms = (value) => value.toFixed(3)
const kb = (value) => (value / 1024).toFixed(1)
const shown = variants.filter((variant) => variant !== 'none')

console.log('\n## Markup size in kB (raw / gzip -6 / brotli -q5)\n')
console.log(`| fixture | keys | ${shown.join(' | ')} |\n|---|---|${shown.map(() => '---|').join('')}`)
for (const fixture of fixtures) {
	console.log(`| ${fixture.name} | ${fixture.keys.length} | ${shown.map((v) => `${kb(fixture.sizes[v].raw)} / ${kb(fixture.sizes[v].gzip)} / ${kb(fixture.sizes[v].brotli)}`).join(' | ')} |`)
}

for (const [metric, title] of [
	['total', 'Total main thread ms (parse over baseline + loading all keys)'],
	['parse', 'Document parse ms over the no-state baseline'],
	['first', 'First loadState() ms'],
	['all', 'loadState() of all keys ms (cold)'],
	['warm', 'loadState() of all keys ms (cached)'],
]) {
	console.log(`\n## ${title}, median of ${iterations}\n`)
	console.log(`| browser | fixture | ${shown.join(' | ')} |\n|---|---|${shown.map(() => '---|').join('')}`)
	for (const browserName of [...new Set(results.map((r) => r.browser))]) {
		for (const fixture of fixtures) {
			const row = results.filter((r) => r.browser === browserName && r.fixture === fixture.name)
			console.log(`| ${browserName} | ${fixture.name} | ${shown.map((v) => ms(row.find((r) => r.variant === v)[metric])).join(' | ')} |`)
		}
	}
}
