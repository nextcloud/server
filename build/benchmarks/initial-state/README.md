<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Initial state transport benchmarks

Compares how the server hands initial state to the frontend: the current
base64 encoded hidden `<input>` elements against JSON in `<script>` elements
and inline scripts that populate `window._nc_initial_state` directly.

## Variants

| variant | markup | consumed by |
|---|---|---|
| `input-base64` (current) | one `<input type="hidden" value="base64(json)">` per key | `@nextcloud/initial-state` 3.0.0 as is: `querySelector` + `atob` + `JSON.parse`, cached per key |
| `input-json` | one `<input type="hidden" value="htmlspecialchars(json)">` per key | 3.0.0 with `atob` removed |
| `script-json-per-key` | one `<script type="application/json" id="initial-state-<app>-<key>">` per key | 3.0.0 as is, core installs a has/get facade on `window._nc_initial_state` that parses each element lazily on first `get()` |
| `script-json-single` | one `<script type="application/json" id="initial-state">` holding all keys | 3.0.0 as is, core installs a lazy getter on `window._nc_initial_state` that parses the whole blob on first access and wraps the frozen result in a has/get facade |
| `inline-js-object` | `<script nonce>window._nc_initial_state = facade(Object.freeze({…}))</script>` | 3.0.0 as is |
| `inline-js-map` | `<script nonce>window._nc_initial_state = new Map([[…], …])</script>` | 3.0.0 as is |
| `inline-js-json-parse` | `<script nonce>window._nc_initial_state = facade(Object.freeze(JSON.parse("…")))</script>` | 3.0.0 as is |

The has/get facade is `s => Object.freeze({ has: k => Object.hasOwn(s, k), get: k => s[k], set() {} })`,
keyed by `#initial-state-<app>-<key>`, which is what 3.0.0 looks up before it touches the DOM.
`@nextcloud/initial-state` 2.x only reads the hidden inputs, so every variant
without them breaks apps that still bundle 2.x.

JSON embedded in `<script>` replaces `<` with `<`. `<` only occurs inside
JSON strings, so the value is unchanged while `</script` and `<!--` can not
appear. The PHP side builds the combined object from the already encoded
per-key values without decoding and encoding them again.

## Fixtures

`fixtures/{login,files,dashboard,settings-users,settings-admin}.json` were
captured from a fresh master instance (30 default apps) with `capture.mjs`.
`synthesize.php` derives two bigger ones from the Files page:

- `large`: 148 keys, 64 kB of JSON (100 extra small app keys, 10 medium lists, inflated capabilities)
- `xlarge`: 150 keys, 978 kB of JSON (`large` plus 2 × 2000 entity lists, as in big user/group settings pages)

## Running

```sh
cd build/benchmarks/initial-state
php synthesize.php                  # creates fixtures/large.json and fixtures/xlarge.json
php bench-php.php 2000              # server side render time
node bench-browser.mjs --iterations=100 --out=results/chromium-firefox.json
node bench-browser.mjs --iterations=50 --browsers=chromium --throttle=4
node bench-micro.mjs                # decode cost without HTML parsing
node capture.mjs http://localhost:8080 admin admin   # refresh the captured fixtures
```

`bench-browser.mjs` serves every page cross origin isolated (COOP + COEP) so
`performance.now()` has full resolution, puts a unique token into every inline
script so no compile cache is hit between loads, interleaves the variants in
random order and first verifies that every variant yields exactly the fixture
values. It records per load:

- **parse**: `domInteractive - responseStart` minus the median of a page without any state, i.e. the extra main thread work while the HTML parser runs, including inline scripts
- **first**: the first `loadState()` call
- **all**: the first call plus one `loadState()` per remaining key, all cold
- **total**: parse + all, the overall main thread cost if every key is read
- **warm**: one pass over all keys once everything is cached

## Results (2026-11-03, PHP 8.5.12, Chromium 1248 / Firefox 1555 via Playwright 1.64, Linux x86_64)

### Transfer size, kB raw / gzip -6

| fixture | input-base64 (current) | script-json-per-key | script-json-single | inline-js-object |
|---|---|---|---|---|
| files (38 keys) | 12.9 / 4.7 | 10.6 / 2.9 | 9.1 / 2.9 | 9.2 / 3.0 |
| settings-users (17 keys) | 15.1 / 5.6 | 11.7 / 3.6 | 11.0 / 3.6 | 11.1 / 3.7 |
| large (148 keys) | 94.1 / 23.1 | 74.1 / 17.1 | 68.7 / 16.8 | 68.8 / 16.8 |
| xlarge (150 keys) | 1284 / 231 | 967 / 180 | 961 / 179 | 961 / 179 |

Base64 defeats compression: the current markup is ~35 % bigger raw and 30–60 % bigger after gzip/brotli than any JSON variant.

### Server render time, µs (`bench-php.php`, median of 2000)

| fixture | input-base64 | script-json-per-key | script-json-single | inline-js-object | inline-js-json-parse |
|---|---|---|---|---|---|
| files | 46.8 | 24.1 | 8.0 | 8.5 | 13.8 |
| large | 279.2 | 95.8 | 33.0 | 33.5 | 116.5 |
| xlarge | 3076.7 | 206.0 | 178.3 | 178.4 | 1961.7 |

### Decode CPU only, µs (`bench-micro.mjs`, median of 200; Firefox timer granularity is 20 µs)

| browser | fixture | atob + JSON.parse per key | JSON.parse per key | JSON.parse single blob | object literal | JSON.parse(string literal) |
|---|---|---|---|---|---|---|
| chromium | files | 20 | 10 | 10 | 45 | 25 |
| chromium | large | 235 | 175 | 205 | 630 | 535 |
| chromium | xlarge | 3165 | 2250 | 2585 | 10740 | 7995 |
| firefox | files | 40 | 20 | 20 | 80 | 60 |
| firefox | xlarge | 4380 | 3020 | 3080 | 9320 | 7800 |

Compiling and running an inline object literal costs 3–4× a `JSON.parse` of the same data, `JSON.parse("…")` in a script 2.5–3.5×.

### Total main thread ms in the page (`bench-browser.mjs`: parse over baseline + cold loadState() of every key, median)

| browser | fixture | input-base64 | script-json-per-key | script-json-single | inline-js-object | inline-js-map | inline-js-json-parse |
|---|---|---|---|---|---|---|---|
| chromium | files | 0.54 | 0.54 | 0.45 | 0.33 | 0.20 | 0.19 |
| chromium | large | 1.76 | 1.67 | 0.96 | 1.53 | 1.53 | 1.52 |
| chromium | xlarge | 12.6 | **10.3** | 11.2 | 17.0 | 16.6 | 14.8 |
| firefox | files | 0.78 | 1.38 | 0.74 | 1.06 | 0.76 | 0.92 |
| firefox | large | 3.36 | 4.56 | 3.12 | 3.74 | 3.54 | 2.68 |
| firefox | xlarge | 11.1 | 9.9 | **8.7** | 16.5 | 16.6 | 13.9 |
| chromium 4× throttled | files | 2.80 | 1.79 | 1.86 | 1.79 | 1.63 | 1.59 |
| chromium 4× throttled | large | 8.55 | 8.04 | **5.49** | 6.72 | 6.67 | 6.52 |
| chromium 4× throttled | xlarge | 52.7 | **43.5** | 46.1 | 75.7 | 74.5 | 67.8 |

Full tables (parse, first, all, warm, all fixtures, `input-json`) are printed by the script.

## Findings

- On real pages (5–11 kB of state) every variant stays below ~1.5 ms of main thread time on desktop and ~3–4 ms at 4× CPU throttling. The differences there are a few hundred µs and partly within noise. The measurable wins are transfer size and server render time.
- Inline scripts (`inline-js-*`) make `loadState()` nearly free, but move the cost into HTML parsing: the whole state is compiled and run eagerly while the parser is blocked, whether a key is read or not. Script compilation of an object literal is 3–4× slower than `JSON.parse`: at 64 kB they are on par with the current implementation, at 1 MB they are ~35–50 % slower (Chromium 17.0 vs 12.6 ms, Firefox 16.5 vs 11.1 ms, 4× throttled 75.7 vs 52.7 ms). They also need the CSP nonce.
- The frozen object with a has/get facade and a real `Map` perform the same, also for cached lookups (`warm`). The facade's value is compatibility with `@nextcloud/initial-state` 3.x, not speed.
- `inline-js-json-parse` mitigates compile cost only partially (string literal escaping adds ~20 % raw payload and makes PHP rendering 1.7–11× slower than the plain object literal).
- JSON in a non-executed `<script type="application/json">` is the best overall: smallest payload, no CSP involvement, the cheapest parser and lazily parsed.
  - `script-json-single` is the cheapest to render (8 µs vs 47 µs for Files) and the fastest in Firefox, but the first access pays for parsing everything (2.8 ms at 1 MB in Chromium, 12 ms throttled).
  - `script-json-per-key` keeps per-key laziness, so keys nobody reads cost only HTML tokenization; best in Chromium for big pages.
- Both script variants work with an unchanged `@nextcloud/initial-state` 3.x when core installs a has/get facade on `window._nc_initial_state` before app bundles run. Apps that still bundle 2.x read only the hidden inputs, so dropping them needs either a transition period with both outputs (doubles the payload) or a cut-off release.
