/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateRemoteUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'

export const getRootPath = function() {
	if (getCurrentUser()) {
		return generateRemoteUrl(`dav/files/${getCurrentUser().uid}`)
	} else {
		return generateRemoteUrl('webdav').replace('/remote.php', '/public.php')
	}
}

export const isPublic = function() {
	return !getCurrentUser()
}

export const getToken = function() {
	return document.getElementById('sharingToken') && document.getElementById('sharingToken').value
}
