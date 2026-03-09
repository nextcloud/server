/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { type Node } from '@nextcloud/files'

export const lockFile = async (node: Node) => {
	const result = await axios.put(
		generateOcsUrl(`/apps/files_lock/lock/${node.fileid}`),
	)
	console.debug('lock result', result)
	return result?.data?.ocs?.data
}

export const unlockFile = async (node: Node) => {
	const result = await axios.delete(
		generateOcsUrl(`/apps/files_lock/lock/${node.fileid}`),
	)
	console.debug('lock result', result)
	return result?.data?.ocs?.data
}
