/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export default {
	extends: [
		'@nextcloud/stylelint-config',
	],
	ignoreFiles: '!**/*.{vue,css,scss}',
	rules: {
		'no-invalid-position-at-import-rule': null,
	},
}
