/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { User } from "@nextcloud/cypress"
import { createShare } from "./FilesSharingUtils"
import { createLinkShare, openLinkShareDetails } from "./PublicShareUtils"

describe('files_sharing: sidebar tab', () => {
	let alice: User

	beforeEach(() => {
		cy.createRandomUser()
			.then((user) => {
				alice = user
				cy.mkdir(user, '/test')
				cy.login(user)
				cy.visit('/apps/files')
			})
	})

	/**
	 * Regression tests of https://github.com/nextcloud/server/issues/53566
	 * Where the ' char was shown as &#39;
	 */
	it('correctly lists shares by label with special characters', () => {
		createLinkShare({ user: alice }, 'test')
		openLinkShareDetails(0)
		cy.findByRole('textbox', { name: /share label/i })
			.should('be.visible')
			.type('Alice\' share')

		cy.intercept('PUT', '**/ocs/v2.php/apps/files_sharing/api/v1/shares/*').as('PUT')
		cy.findByRole('button', { name: /update share/i }).click()
		cy.wait('@PUT')

		// see the label is shown correctly
		cy.findByRole('list', { name: /link shares/i })
			.findAllByRole('listitem')
			.should('have.length', 1)
			.first()
			.should('contain.text', 'Share link (Alice\' share)')
	})
})
