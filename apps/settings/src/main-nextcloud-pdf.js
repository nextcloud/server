/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'

const hasPdf = loadState('settings', 'has-reasons-use-nextcloud-pdf') === true

window.addEventListener('DOMContentLoaded', function() {
	const link = document.getElementById('open-reasons-use-nextcloud-pdf')
	if (link && hasPdf) {
		link.addEventListener('click', function(event) {
			event.preventDefault()
			OCA.Viewer.open({
				path: '/Reasons to use Nextcloud.pdf',
			})
		})
	}
})
