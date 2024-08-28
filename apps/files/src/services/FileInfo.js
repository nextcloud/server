/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { davGetDefaultPropfind } from '@nextcloud/files'

/**
 * @param {any} url -
 */
export default async function(url) {
	const response = await axios({
		method: 'PROPFIND',
		url,
		data: davGetDefaultPropfind(),
	})

	// TODO: create new parser or use cdav-lib when available
	const file = OC.Files.getClient()._client.parseMultiStatus(response.data)
	// TODO: create new parser or use cdav-lib when available
	const fileInfo = OC.Files.getClient()._parseFileInfo(file[0])

	// TODO remove when no more legacy backbone is used
	fileInfo.get = (key) => fileInfo[key]
	fileInfo.isDirectory = () => fileInfo.mimetype === 'httpd/unix-directory'
	fileInfo.canEdit = () => Boolean(fileInfo.permissions & OC.PERMISSION_UPDATE)

	return fileInfo
}
