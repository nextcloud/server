/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import $ from 'jquery'

import { getRequestToken } from '../OC/requesttoken.ts'

$(document).on('ajaxSend', function(elm, xhr, settings) {
	if (settings.crossDomain === false) {
		xhr.setRequestHeader('requesttoken', getRequestToken())
		xhr.setRequestHeader('OCS-APIREQUEST', 'true')
	}
})
