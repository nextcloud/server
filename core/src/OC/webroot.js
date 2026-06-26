/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

let webroot = window._oc_webroot

if (typeof webroot === 'undefined') {
	webroot = location.pathname
	const pos = webroot.indexOf('/index.php/')
	if (pos !== -1) {
		webroot = webroot.substr(0, pos)
	} else {
		webroot = webroot.substr(0, webroot.lastIndexOf('/'))
	}
}

export default webroot
