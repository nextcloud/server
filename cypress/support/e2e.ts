/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import 'cypress-axe'
import './commands.ts'
// Remove with Node 22
// Ensure that we can use `Promise.withResolvers` - works in browser but on Node we need Node 22+
import 'core-js/actual/promise/with-resolvers.js'

// Fix ResizeObserver loop limit exceeded happening in Cypress only
// @see https://github.com/cypress-io/cypress/issues/20341
Cypress.on('uncaught:exception', (err) => !err.message.includes('ResizeObserver loop limit exceeded'))
Cypress.on('uncaught:exception', (err) => !err.message.includes('ResizeObserver loop completed with undelivered notifications'))

// Defer ResizeObserver callbacks one frame to break floating UI sync loops
// that otherwise tank the renderer and trigger Electron's unresponsive kill.
Cypress.on('window:before:load', (win) => {
	const Original = win.ResizeObserver
	win.ResizeObserver = class extends Original {
		constructor(callback: ResizeObserverCallback) {
			super((entries, observer) => {
				win.requestAnimationFrame(() => callback(entries, observer))
			})
		}
	}
})

// Optional CPU throttling to reproduce CI-like renderer slowness locally.
// Usage: CYPRESS_CPU_THROTTLE=8 npx cypress run ...
const cpuThrottle = Number(Cypress.env('CPU_THROTTLE'))
if (cpuThrottle > 1) {
	beforeEach(() => {
		Cypress.automation('remote:debugger:protocol', {
			command: 'Emulation.setCPUThrottlingRate',
			params: { rate: cpuThrottle },
		})
	})
}

// Repro-only: delay file-preview responses so the row re-render they trigger
// lands during the actions-menu open window (the flake we are chasing).
// Usage: CYPRESS_PREVIEW_DELAY=1500 npx cypress run ...
const previewDelay = Number(Cypress.env('PREVIEW_DELAY'))
if (previewDelay > 0) {
	beforeEach(() => {
		cy.intercept('GET', '**/core/preview?**', (req) => {
			req.on('response', (res) => {
				res.setDelay(previewDelay)
			})
		}).as('delayedPreview')
	})
	// Report to the terminal how many preview requests were intercepted/delayed,
	// so we can confirm the repro knob is actually engaging.
	afterEach(() => {
		cy.get<{ length: number }>('@delayedPreview.all', { log: false }).then((calls) => {
			cy.task('log', `[preview-delay] delayed ${calls?.length ?? 0} preview request(s) by ${previewDelay}ms`)
		})
	})
}

// Repro-only: deterministically recreate the real flake mechanism — a file-row
// preview finishing loading re-renders the row and closes a just-opened actions
// menu. A MutationObserver watches for a row action toggle reporting
// aria-expanded="true" and, at that instant, forces the row's preview <img> to
// reload so its @load handler fires a reactive re-render right on top of the
// opening menu. Usage: CYPRESS_FORCE_RERENDER=1 npx cypress run ...
if (Cypress.env('FORCE_RERENDER')) {
	Cypress.on('window:before:load', (win) => {
		const forceReloadPreviewForToggle = (toggle: Element) => {
			const row = toggle.closest('[data-cy-files-list-row]')
			const img = row?.querySelector<HTMLImageElement>('.files-list__row-icon-preview, img')
			if (img?.src) {
				const src = img.src
				img.src = ''
				// Reassign on the next frame so the browser refetches and re-fires @load
				win.requestAnimationFrame(() => {
					img.src = src.includes('?') ? `${src}&_r=${Date.now()}` : `${src}?_r=${Date.now()}`
				})
			}
		}
		const observer = new win.MutationObserver((mutations) => {
			for (const m of mutations) {
				const target = m.target as Element
				if (m.attributeName === 'aria-expanded'
					&& target.getAttribute('aria-expanded') === 'true'
					&& target.closest('[data-cy-files-list-row-actions]')) {
					forceReloadPreviewForToggle(target)
				}
			}
		})
		win.document.addEventListener('DOMContentLoaded', () => {
			observer.observe(win.document.body, { attributes: true, subtree: true, attributeFilter: ['aria-expanded'] })
		})
	})
}
