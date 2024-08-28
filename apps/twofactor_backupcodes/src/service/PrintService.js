/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * @param {any} data -
 */
export function print(data) {
	const name = OC.theme.name || 'Nextcloud'
	const newTab = window.open('', t('twofactor_backupcodes', '{name} backup codes', { name }))
	newTab.document.write('<h1>' + t('twofactor_backupcodes', '{name} backup codes', { name }) + '</h1>')
	newTab.document.write('<pre>' + data + '</pre>')
	newTab.print()
	newTab.close()
}
