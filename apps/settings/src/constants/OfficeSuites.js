/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export const OFFICE_SUITES = [
	{
		id: 'nextcloud-office',
		appId: 'richdocuments',
		name: 'Nextcloud Office',
		features: [
			t('settings', 'Best Nextcloud integration'),
			t('settings', 'Open source'),
			t('settings', 'Good performance'),
			t('settings', 'Best security: documents never leave your server'),
			t('settings', 'Best ODF compatibility'),
			t('settings', 'Best support for legacy files'),
		],
		learnMoreUrl: 'https://nextcloud.com/collaboraonline/',
		isPrimary: true,
	},
	{
		id: 'onlyoffice',
		appId: 'onlyoffice',
		name: 'Onlyoffice',
		features: [
			t('settings', 'Good Nextcloud integration'),
			t('settings', 'Open core'),
			t('settings', 'Best performance'),
			t('settings', 'Limited ODF compatibility'),
			t('settings', 'Best Microsoft compatibility'),
		],
		learnMoreUrl: 'https://nextcloud.com/onlyoffice/',
		isPrimary: false,
	},
]

/**
 * Get office suite configuration by ID
 *
 * @param {string} id - The suite ID
 * @return {object|undefined} The suite configuration or undefined if not found
 */
export function getOfficeSuiteById(id) {
	return OFFICE_SUITES.find((suite) => suite.id === id)
}

/**
 * Get office suite configuration by app ID
 *
 * @param {string} appId - The app ID (richdocuments, onlyoffice, etc.)
 * @return {object|undefined} The suite configuration or undefined if not found
 */
export function getOfficeSuiteByAppId(appId) {
	return OFFICE_SUITES.find((suite) => suite.appId === appId)
}
