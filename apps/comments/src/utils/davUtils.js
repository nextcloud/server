/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { generateRemoteUrl } from '@nextcloud/router'

const getRootPath = function() {
	return generateRemoteUrl('dav/comments')
}

export { getRootPath }
