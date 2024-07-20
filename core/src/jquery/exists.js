/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import $ from 'jquery'

/**
 * check if an element exists.
 * allows you to write if ($('#myid').exists()) to increase readability
 *
 * @see {@link http://stackoverflow.com/questions/31044/is-there-an-exists-function-for-jquery}
 * @return {boolean}
 */
$.fn.exists = function() {
	return this.length > 0
}
