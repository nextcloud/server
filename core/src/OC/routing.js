/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import {
	getRootUrl as realGetRootUrl,
} from '@nextcloud/router'

/**
 * Creates a relative url for remote use
 *
 * @param {string} service id
 * @return {string} the url
 */
export const linkToRemoteBase = service => {
	return realGetRootUrl() + '/remote.php/' + service
}
