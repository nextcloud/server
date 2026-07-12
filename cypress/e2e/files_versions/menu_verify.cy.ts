/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * [menu-verify] THROWAWAY probe. Does a plain single open-click on a file-row
 * actions toggle get LOST on a slow (0.2 CPU) runner? A lost click leaves
 * aria-expanded="false" and the menu never opens — which the raised
 * defaultCommandTimeout cannot fix (it would just wait the full timeout on a
 * menu that will never open). This is what the openActionsMenu re-click poll
 * exists to handle. If aria-expanded stays "false" here, the poll is still
 * needed even with the timeout raise.
 */

import { getActionButtonForFile } from '../files/FilesUtils.ts'

describe('[menu-verify] single-click actions-menu open', () => {
	it('detects lost open-clicks (no re-click)', () => {
		const files = Array.from({ length: 12 }, (_, i) => `mv${i}.txt`)
		cy.createRandomUser().then((user) => {
			files.forEach((f) => cy.uploadContent(user, new Blob([f], { type: 'text/plain' }), 'text/plain', `/${f}`))
			cy.login(user)
			cy.visit('/apps/files')

			const lost: string[] = []
			cy.wrap(files).each((f: string) => {
				getActionButtonForFile(f).scrollIntoView()
				// A single, plain click — exactly what master's helper did before
				// the openActionsMenu re-click poll was added.
				getActionButtonForFile(f).click({ force: true })
				// Give the click's handler ample time to fire (1s is plenty on any
				// runner if the click actually registered), then inspect the toggle.
				// eslint-disable-next-line cypress/no-unnecessary-waiting
				cy.wait(1000)
				getActionButtonForFile(f).then(($t) => {
					const exp = $t.attr('aria-expanded')
					cy.task('log', `[menu-verify] ${f}: aria-expanded=${exp} 1s after single click`)
					if (exp !== 'true') {
						lost.push(f)
					}
				})
				// Reset for the next file (close the menu if it did open).
				cy.get('body').type('{esc}')
			}).then(() => {
				cy.task('log', `[menu-verify] LOST OPEN-CLICKS: ${lost.length}/${files.length} -> [${lost.join(', ')}]`)
			})
		})
	})
})
