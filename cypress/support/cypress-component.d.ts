/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { mount } from '@cypress/vue2'

declare global {
	// eslint-disable-next-line @typescript-eslint/no-namespace
	namespace Cypress {
		interface Chainable {
			mount: typeof mount
			mockInitialState: (app: string, key: string, value: unknown) => Cypress.Chainable<void>
			unmockInitialState: (app?: string, key?: string) => Cypress.Chainable<void>
		}
	}
}
