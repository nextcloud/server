/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Pure decode cost of each encoding, without HTML parsing or DOM access.
 *
 * Usage: node bench-micro.mjs [--rounds=200] [--browsers=chromium,firefox]
 *
 * Object literal and JSON.parse(string literal) are evaluated through an
 * indirect eval with a unique suffix, so every round pays compile and run
 * like an inline <script> would.
 */

import { createServer } from 'node:http'
import { readdirSync, readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { chromium, firefox } from 'playwright'

const dir = dirname(fileURLToPath(import.meta.url))
const args = Object.fromEntries(process.argv.slice(2).map((arg) => arg.replace(/^--/, '').split('=')))
const rounds = Number(args.rounds ?? 200)
const browsers = (args.browsers ?? 'chromium,firefox').split(',')
const fixtures = readdirSync(join(dir, 'fixtures'))
	.filter((file) => file.endsWith('.json'))
	.map((file) => [file.slice(0, -5), readFileSync(join(dir, 'fixtures', file), 'utf8')])

const server = createServer((req, res) => {
	res.writeHead(200, {
		'Content-Type': 'text/html; charset=utf-8',
		'Cross-Origin-Opener-Policy': 'same-origin',
		'Cross-Origin-Embedder-Policy': 'require-corp',
	})
	res.end('<!DOCTYPE html><title>micro</title>')
})
await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve))

const variantNames = ['atob + JSON.parse per key', 'JSON.parse per key', 'JSON.parse single blob', 'object literal', 'JSON.parse(string literal)']
console.log(`## Decode µs per page, median of ${rounds}\n`)
console.log(`| browser | fixture | ${variantNames.join(' | ')} |\n|---|---|${variantNames.map(() => '---|').join('')}`)

for (const browserName of browsers) {
	const browser = await { chromium, firefox }[browserName].launch()
	const page = await browser.newPage()
	await page.goto(`http://localhost:${server.address().port}/`)
	for (const [name, source] of fixtures) {
		const result = await page.evaluate(([source, rounds]) => {
			const states = JSON.parse(source)
			const keys = Object.keys(states)
			const base64 = keys.map((k) => btoa(states[k]))
			const json = keys.map((k) => states[k])
			const blob = '{' + keys.map((k) => JSON.stringify('#initial-state-' + k) + ':' + states[k]).join(',') + '}'
			const blobLiteral = JSON.stringify(blob)
			const indirectEval = eval
			const variants = {
				'atob + JSON.parse per key': () => base64.map((s) => JSON.parse(atob(s))),
				'JSON.parse per key': () => json.map((s) => JSON.parse(s)),
				'JSON.parse single blob': () => JSON.parse(blob),
				'object literal': (i) => indirectEval(`(${blob})//${i}`),
				'JSON.parse(string literal)': (i) => indirectEval(`JSON.parse(${blobLiteral})//${i}`),
			}
			const samples = Object.fromEntries(Object.keys(variants).map((v) => [v, []]))
			for (let i = 0; i < rounds + 10; i++) {
				for (const [variant, run] of Object.entries(variants)) {
					const start = performance.now()
					run(`${i}-${Math.random()}`)
					const duration = performance.now() - start
					if (i >= 10) {
						samples[variant].push(duration)
					}
				}
			}
			return Object.fromEntries(Object.entries(samples).map(([variant, list]) => {
				list.sort((a, b) => a - b)
				return [variant, list[Math.floor(list.length / 2)] * 1000]
			}))
		}, [source, rounds])
		console.log(`| ${browserName} | ${name} | ${variantNames.map((v) => result[v].toFixed(1)).join(' | ')} |`)
	}
	await browser.close()
}
server.close()
