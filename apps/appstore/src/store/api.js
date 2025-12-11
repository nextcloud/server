/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { confirmPassword } from '@nextcloud/password-confirmation'

/**
 * @param {string} url - The url to sanitize
 */
function sanitize(url) {
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
