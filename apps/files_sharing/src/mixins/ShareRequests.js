/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios, { isAxiosError } from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { generateOcsUrl } from '@nextcloud/router'
import Share from '../models/Share.ts'
import logger from '../services/logger.ts'

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
		 * @param {string} [data.expireDate] expire the share automatically after
		 * @param {string} [data.label] custom label
		 * @param {string} [data.attributes] Share attributes encoded as json
		 * @param {string} data.note custom note to recipient
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
				const errorMessage = getErrorMessage(error) ?? t('files_sharing', 'Error creating the share')
				showError(errorMessage)
				throw new Error(errorMessage, { cause: error })
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
				const errorMessage = getErrorMessage(error) ?? t('files_sharing', 'Error deleting the share')
				showError(errorMessage)
				throw new Error(errorMessage, { cause: error })
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
				logger.error('Error while updating share', { error })
				const errorMessage = getErrorMessage(error) ?? t('files_sharing', 'Error updating the share')
				// the error will be shown in apps/files_sharing/src/mixins/SharesMixin.js
				throw new Error(errorMessage, { cause: error })
			}
		},
	},
}

/**
 * Handle an error response from the server and show a notification with the error message if possible
 *
 * @param {unknown} error - The received error
 * @return {string|undefined} the error message if it could be extracted from the response, otherwise undefined
 */
function getErrorMessage(error) {
	if (isAxiosError(error) && error.response.data?.ocs) {
		/** @type {import('@nextcloud/typings/ocs').OCSResponse} */
		const response = error.response.data
		if (response.ocs.meta?.message) {
			return response.ocs.meta.message
		}
	}
}
