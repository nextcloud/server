/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
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

// TODO: remove when ie not supported
import 'url-search-params-polyfill'

import { generateOcsUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import Share from '../models/Share.js'
import { emit } from '@nextcloud/event-bus'

const shareUrl = generateOcsUrl('apps/files_sharing/api/v1/shares')

export default {
	methods: {
		/**
		 * Create a new share
		 *
		 * @param {object} data destructuring object
		 * @param {string} data.path  path to the file/folder which should be shared
		 * @param {number} data.shareType  0 = user; 1 = group; 3 = public link; 6 = federated cloud share
		 * @param {string} data.shareWith  user/group id with which the file should be shared (optional for shareType > 1)
		 * @param {boolean} [data.publicUpload]  allow public upload to a public shared folder
		 * @param {string} [data.password]  password to protect public link Share with
		 * @param {number} [data.permissions]  1 = read; 2 = update; 4 = create; 8 = delete; 16 = share; 31 = all (default: 31, for public shares: 1)
		 * @param {boolean} [data.sendPasswordByTalk] send the password via a talk conversation
		 * @param {string} [data.expireDate] expire the shareautomatically after
		 * @param {string} [data.label] custom label
		 * @param {string} [data.attributes] Share attributes encoded as json
		 * @param data.note
		 * @return {Share} the new share
		 * @throws {Error}
		 */
		async createShare({ path, permissions, shareType, shareWith, publicUpload, password, sendPasswordByTalk, expireDate, label, note, attributes }) {
			try {
				const request = await axios.post(shareUrl, { path, permissions, shareType, shareWith, publicUpload, password, sendPasswordByTalk, expireDate, label, note, attributes })
				if (!request?.data?.ocs) {
					throw request
				}
				const share = new Share(request.data.ocs.data)
				emit('files_sharing:share:created', { share })
				return share
			} catch (error) {
				console.error('Error while creating share', error)
				const errorMessage = error?.response?.data?.ocs?.meta?.message
				OC.Notification.showTemporary(
					errorMessage ? t('files_sharing', 'Error creating the share: {errorMessage}', { errorMessage }) : t('files_sharing', 'Error creating the share'),
					{ type: 'error' },
				)
				throw error
			}
		},

		/**
		 * Delete a share
		 *
		 * @param {number} id share id
		 * @throws {Error}
		 */
		async deleteShare(id) {
			try {
				const request = await axios.delete(shareUrl + `/${id}`)
				if (!request?.data?.ocs) {
					throw request
				}
				emit('files_sharing:share:deleted', { id })
				return true
			} catch (error) {
				console.error('Error while deleting share', error)
				const errorMessage = error?.response?.data?.ocs?.meta?.message
				OC.Notification.showTemporary(
					errorMessage ? t('files_sharing', 'Error deleting the share: {errorMessage}', { errorMessage }) : t('files_sharing', 'Error deleting the share'),
					{ type: 'error' },
				)
				throw error
			}
		},

		/**
		 * Update a share
		 *
		 * @param {number} id share id
		 * @param {object} properties key-value object of the properties to update
		 */
		async updateShare(id, properties) {
			try {
				const request = await axios.put(shareUrl + `/${id}`, properties)
				emit('files_sharing:share:updated', { id })
				if (!request?.data?.ocs) {
					throw request
				} else {
					return request.data.ocs.data
				}
			} catch (error) {
				console.error('Error while updating share', error)
				if (error.response.status !== 400) {
					const errorMessage = error?.response?.data?.ocs?.meta?.message
					OC.Notification.showTemporary(
						errorMessage ? t('files_sharing', 'Error updating the share: {errorMessage}', { errorMessage }) : t('files_sharing', 'Error updating the share'),
						{ type: 'error' },
					)
				}
				const message = error.response.data.ocs.meta.message
				throw new Error(message)
			}
		},
	},
}
