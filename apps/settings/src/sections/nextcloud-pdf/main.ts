/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'

const hasPdf = loadState('settings', 'has-reasons-use-nextcloud-pdf') === true
const path = '/Reasons to use Nextcloud.pdf'

/**
 * Look the file up so the viewer can be handed a node.
 */
async function openInViewer() {
	// Only needed on click, so they stay out of every personal settings page load
	const [{ getClient, getDefaultPropfind, getRootPath, resultToNode }, { getViewer }] = await Promise.all([
		import('@nextcloud/files/dav'),
		import('@nextcloud/viewer'),
	])
	const { data } = await getClient().stat(getRootPath() + path, {
		details: true,
		data: getDefaultPropfind(),
	})
	const node = resultToNode(data)
	await getViewer().open([node], node)
}

window.addEventListener('DOMContentLoaded', () => {
	const link = document.getElementById('open-reasons-use-nextcloud-pdf')
	if (!link || !hasPdf) {
		return
	}
	link.addEventListener('click', (event) => {
		event.preventDefault()
		openInViewer()
	})
})
