/*
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import Toastify from 'toastify-js'

const TOAST_TYPE_CLASES = {
	error: 'toast-error',
	info: 'toast-info',
	warning: 'toast-warning',
	success: 'toast-success',
	permanent: 'permanent'
}

const Toast = {

	success(text, options = {}) {
		options.type = 'success'
		return this.message(text, options)
	},

	warning(text, options = {}) {
		options.type = 'warning'
		return this.message(text, options)
	},

	error(text, options = {}) {
		options.type = 'error'
		return this.message(text, options)
	},

	info(text, options = {}) {
		options.type = 'info'
		return this.message(text, options)
	},

	message(text, options) {
		options = options || {}
		_.defaults(options, {
			timeout: 7,
			isHTML: false,
			type: undefined,
			close: true,
			callback: () => {}
		})
		if (!options.isHTML) {
			text = $('<div/>').text(text).html()
		}
		let classes = ''
		if (options.type) {
			classes = TOAST_TYPE_CLASES[options.type]
		}

		const toast = Toastify({
			text: text,
			duration: options.timeout ? options.timeout * 1000 : null,
			callback: options.callback,
			close: options.close,
			gravity: 'top',
			selector: !window.TESTING ? 'content' : 'testArea',
			positionLeft: false,
			backgroundColor: '',
			className: 'toast ' + classes
		})
		toast.showToast()
		// add toastify object to the element for reference in legacy OC.Notification
		toast.toastElement.toastify = toast
		return toast
	}
}
export default Toast
