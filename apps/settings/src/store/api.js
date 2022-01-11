/**
 * @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Sujith Haridasan <sujith.h@gmail.com>
 *
 * @license AGPL-3.0-or-later
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

import axios from '@nextcloud/axios'
import confirmPassword from '@nextcloud/password-confirmation'

const sanitize = function(url) {
	return url.replace(/\/$/, '') // Remove last url slash
}

export default {

	/**
	 * This Promise is used to chain a request that require an admin password confirmation
	 * Since chaining Promise have a very precise behavior concerning catch and then,
	 * you'll need to be careful when using it.
	 * e.g
	 * // store
	 * action(context) {
	 *   return api.requireAdmin().then((response) => {
	 *     return api.get('url')
	 *       .then((response) => {API success})
	 *       .catch((error) => {API failure});
	 *   }).catch((error) => {requireAdmin failure});
	 * }
	 * // vue
	 * this.$store.dispatch('action').then(() => {always executed})
	 *
	 * Since Promise.then().catch().then() will always execute the last then
	 * this.$store.dispatch('action').then will always be executed
	 *
	 * If you want requireAdmin failure to also catch the API request failure
	 * you will need to throw a new error in the api.get.catch()
	 *
	 * e.g
	 * api.requireAdmin().then((response) => {
	 *   api.get('url')
	 *     .then((response) => {API success})
	 *     .catch((error) => {throw error;});
	 * }).catch((error) => {requireAdmin OR API failure});
	 *
	 * @return {Promise}
	 */
	requireAdmin() {
		return confirmPassword()
	},
	get(url, options) {
		return axios.get(sanitize(url), options)
	},
	post(url, data) {
		return axios.post(sanitize(url), data)
	},
	patch(url, data) {
		return axios.patch(sanitize(url), data)
	},
	put(url, data) {
		return axios.put(sanitize(url), data)
	},
	delete(url, data) {
		return axios.delete(sanitize(url), { params: data })
	},
}
