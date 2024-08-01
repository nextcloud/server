/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCurrentUser } from '@nextcloud/auth'

export const isPublic = function() {
	return !getCurrentUser()
}

export const getToken = function() {
	return document.getElementById('sharingToken') && document.getElementById('sharingToken').value
}
