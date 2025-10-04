/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const additionalPseudoSelectors = [
	// Vue <style scoped>
	// See: https://vuejs.org/api/sfc-css-features.html
	'deep',
	'slotted',

	// CSS Modules (including Vue <style module>)
	// See: https://github.com/css-modules/css-modules/blob/master/docs/composition.md#exceptions
	'global',
	'local',
]

/** @type {import('stylelint').Config} */
export default {
	extends: '@nextcloud/stylelint-config',
	ignoreFiles: [
		'**/*.(!(vue|scss))',
	],

	// remove with nextcloud/stylelint-config 3.1.1+
	rules: {
		'selector-pseudo-class-no-unknown': [
			true,
			{
				ignorePseudoClasses: additionalPseudoSelectors,
			},
		],
		'selector-pseudo-element-no-unknown': [
			true,
			{
				ignorePseudoElements: additionalPseudoSelectors,
			},
		],
	},
}
