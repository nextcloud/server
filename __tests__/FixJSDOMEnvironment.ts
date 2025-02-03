/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import JSDOMEnvironment from 'jest-environment-jsdom'

// https://github.com/facebook/jest/blob/v29.4.3/website/versioned_docs/version-29.4/Configuration.md#testenvironment-string
export default class FixJSDOMEnvironment extends JSDOMEnvironment {

	constructor(...args: ConstructorParameters<typeof JSDOMEnvironment>) {
		super(...args)

		// https://github.com/jsdom/jsdom/issues/3363
		// 31 ad above switched to vitest and don't have that issue
		this.global.structuredClone = structuredClone
	}

}
