/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
window.OC = {
	...window.OC,
	config: {
		version: '32.0.0',
		...(window.OC?.config ?? {}),
	},
}
window.OCA = { ...window.OCA }
window.OCP = { ...window.OCP }

window._oc_webroot = ''
