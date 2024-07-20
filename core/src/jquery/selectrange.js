/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import $ from 'jquery'

/**
 * select a range in an input field
 *
 * @see {@link http://stackoverflow.com/questions/499126/jquery-set-cursor-position-in-text-area}
 * @param {number} start start selection from
 * @param {number} end number of char from start
 * @return {void}
 */
$.fn.selectRange = function(start, end) {
	return this.each(function() {
		if (this.setSelectionRange) {
			this.focus()
			this.setSelectionRange(start, end)
		} else if (this.createTextRange) {
			const range = this.createTextRange()
			range.collapse(true)
			range.moveEnd('character', end)
			range.moveStart('character', start)
			range.select()
		}
	})
}
