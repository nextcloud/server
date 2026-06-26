/*!
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCapabilities } from '@nextcloud/capabilities'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'

/**
 * Open a new tab and print the given backup codes
 *
 * @param data - The backup codes to print
 */
export function print(data: string[]): void {
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	const name = (getCapabilities() as any).theming.name || 'Nextcloud'
	const newTab = window.open('', t('twofactor_backupcodes', '{name} backup codes', { name }))
	if (!newTab) {
		showError(t('twofactor_backupcodes', 'Unable to open a new tab for printing'))
		throw new Error('Unable to open a new tab for printing')
	}

	const heading = newTab.document.createElement('h1')
	heading.textContent = t('twofactor_backupcodes', '{name} backup codes', { name })
	const pre = newTab.document.createElement('pre')
	for (const code of data) {
		const codeLine = newTab.document.createTextNode(code)
		pre.appendChild(codeLine)
		pre.appendChild(newTab.document.createElement('br'))
	}

	newTab.document.body.innerHTML = ''
	newTab.document.body.appendChild(heading)
	newTab.document.body.appendChild(pre)

	newTab.print()
	newTab.close()
}
