/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { AxiosResponse } from '@nextcloud/axios'

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { CancelablePromise } from 'cancelable-promise'

/**
 * Search groups
 *
 * @param {object} options Options
 * @param {string} options.search Search query
 * @param {number} options.offset Offset
 * @param {number} options.limit Limit
 */
export const searchGroups = ({ search, offset, limit }): CancelablePromise<AxiosResponse> => {
	const controller = new AbortController()
	return new CancelablePromise(async (resolve, reject, onCancel) => {
		onCancel(() => controller.abort())
		try {
			const response = await axios.get(
				generateOcsUrl('/cloud/groups/details?search={search}&offset={offset}&limit={limit}', { search, offset, limit }), {
					signal: controller.signal,
				},
			)
			resolve(response)
		} catch (error) {
			reject(error)
		}
	})
}
