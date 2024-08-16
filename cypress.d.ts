/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/* eslint-disable */
import { mount } from '@cypress/vue2'

type MountParams = Parameters<typeof mount>;
type OptionsParam = MountParams[1];

declare global {
	namespace Cypress {
		interface Chainable {
			mount: typeof mount;
			/**
			 * Mock an initial state for component testing
			 *
			 * @param app App name of the initial state
			 * @param key Key of the initial state
			 * @param value The mocked value of the initial state
			 */
			mockInitialState: (app: string, key: string, value: any) => void
			/**
			 * Unmock all initial states or one defined by app and key
			 *
			 * @param app app name of the initial state
			 * @param key the key of the initial state
			 */
			unmockInitialState: (app?: string, key?: string) => void
		}
	}
}
