/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getClient, getDefaultPropfind, getRootPath, resultToNode } from '@nextcloud/files/dav'
import { loadState } from '@nextcloud/initial-state'
import { getViewer } from '@nextcloud/viewer'

const hasPdf = loadState('settings', 'has-reasons-use-nextcloud-pdf') === true
const path = '/Reasons to use Nextcloud.pdf'

/**
 * Look the file up so the viewer can be handed a node.
 */
async function openInViewer() {
	const { data } = await getClient().stat(getRootPath() + path, {
		details: true,
		data: getDefaultPropfind(),
	})
	const node = resultToNode(data)
	await getViewer().open([node], node)
}

window.addEventListener('DOMContentLoaded', function() {
	const link = document.getElementById('open-reasons-use-nextcloud-pdf')
	if (link && hasPdf) {
		link.addEventListener('click', function(event) {
			event.preventDefault()
			openInViewer()
		})
	}
})
