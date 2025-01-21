/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

// eslint-disable-next-line n/no-extraneous-import
import type { AxiosResponse } from 'axios'

declare global {
	// eslint-disable-next-line @typescript-eslint/no-namespace
	namespace Cypress {
		// eslint-disable-next-line @typescript-eslint/no-explicit-any, @typescript-eslint/no-unused-vars
		interface Chainable<Subject = any> {
			/**
			 * Enable or disable a given user
			 */
			// eslint-disable-next-line @typescript-eslint/no-explicit-any
			enableUser(user: User, enable?: boolean): Cypress.Chainable<Cypress.Response<any>>,

			/**
			 * Upload a file from the fixtures folder to a given user storage.
			 * **Warning**: Using this function will reset the previous session
			 */
			uploadFile(user: User, fixture?: string, mimeType?: string, target?: string): Cypress.Chainable<void>,

			/**
			 * Upload a raw content to a given user storage.
			 * **Warning**: Using this function will reset the previous session
			 */
			uploadContent(user: User, content: Blob, mimeType: string, target: string, mtime?: number): Cypress.Chainable<AxiosResponse>,

			/**
			 * Create a new directory
			 * **Warning**: Using this function will reset the previous session
			 */
			mkdir(user: User, target: string): Cypress.Chainable<void>,

			/**
			 * Set a file as favorite (or remove from favorite)
			 */
			setFileAsFavorite(user: User, target: string, favorite?: boolean): Cypress.Chainable<void>,

			/**
			 * Reset the admin theming entirely.
			 * **Warning**: Using this function will reset the previous session
			 */
			resetAdminTheming(): Cypress.Chainable<void>,

			/**
			 * Reset the user theming settings.
			 * If provided, will clear session and login as the given user.
			 * **Warning**:  Providing a user will reset the previous session.
			 */
			resetUserTheming(user?: User): Cypress.Chainable<void>,

			userFileExists(user: string, path: string): Cypress.Chainable<number>
		}
	}
}
