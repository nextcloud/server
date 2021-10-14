/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import $ from 'jquery'
import OC from '../OC'
$.fn.avatar = function(user, size, ie8fix, hidedefault, callback, displayname) {
	const setAvatarForUnknownUser = function(target) {
		target.imageplaceholder('?')
		target.css('background-color', '#b9b9b9')
	}

	if (typeof (user) !== 'undefined') {
		user = String(user)
	}
	if (typeof (displayname) !== 'undefined') {
		displayname = String(displayname)
	}

	if (typeof (size) === 'undefined') {
		if (this.height() > 0) {
			size = this.height()
		} else if (this.data('size') > 0) {
			size = this.data('size')
		} else {
			size = 64
		}
	}

	this.height(size)
	this.width(size)

	if (typeof (user) === 'undefined') {
		if (typeof (this.data('user')) !== 'undefined') {
			user = this.data('user')
		} else {
			setAvatarForUnknownUser(this)
			return
		}
	}

	// sanitize
	user = String(user).replace(/\//g, '')

	const $div = this
	let url

	// If this is our own avatar we have to use the version attribute
	if (user === OC.getCurrentUser().uid) {
		url = OC.generateUrl(
			'/avatar/{user}/{size}?v={version}',
			{
				user,
				size: Math.ceil(size * window.devicePixelRatio),
				version: oc_userconfig.avatar.version,
			})
	} else {
		url = OC.generateUrl(
			'/avatar/{user}/{size}',
			{
				user,
				size: Math.ceil(size * window.devicePixelRatio),
			})
	}

	const img = new Image()

	// If the new image loads successfully set it.
	img.onload = function() {
		$div.clearimageplaceholder()
		$div.append(img)

		if (typeof callback === 'function') {
			callback()
		}
	}
	// Fallback when avatar loading fails:
	// Use old placeholder when a displayname attribute is defined,
	// otherwise show the unknown user placeholder.
	img.onerror = function() {
		$div.clearimageplaceholder()
		if (typeof (displayname) !== 'undefined') {
			$div.imageplaceholder(user, displayname)
		} else {
			setAvatarForUnknownUser($div)
		}

		if (typeof callback === 'function') {
			callback()
		}
	}

	if (size < 32) {
		$div.addClass('icon-loading-small')
	} else {
		$div.addClass('icon-loading')
	}
	img.width = size
	img.height = size
	img.src = url
	img.alt = ''
}
