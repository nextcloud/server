/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** @type {import('stylelint').Config} */
const config = {
	extends: '@nextcloud/stylelint-config',
	plugins: ['stylelint-use-logical'],
	rules: {
		'csstools/use-logical': ['always',
			{
				except: [
					// For now ignore block rules for logical properties
					/(^|-)(height|width)$/, /(^|-)(top|bottom)(-|$)/,
					// Also ignore float as this is not well supported (I look at you Samsung)
					'clear', 'float',
				],
			},
		],
	},
}

module.exports = config
