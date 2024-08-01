/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import $ from 'jquery'

/**
 * Filter jQuery selector by attribute value
 *
 * @param {string} attrName attribute name
 * @param {string} attrValue attribute value
 * @return {void}
 */
$.fn.filterAttr = function(attrName, attrValue) {
	return this.filter(function() {
		return $(this).attr(attrName) === attrValue
	})
}
