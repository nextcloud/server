/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { OCSResponse } from '@nextcloud/typings/ocs'

import axios, { isAxiosError } from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

export const TrustedServerStatus = Object.freeze({
	/** after a user list was exchanged at least once successfully */
	STATUS_OK: 1,
	/** waiting for shared secret or initial user list exchange */
	STATUS_PENDING: 2,
	/** something went wrong, misconfigured server, software bug,... user interaction needed */
	STATUS_FAILURE: 3,
	/** remote server revoked access */
	STATUS_ACCESS_REVOKED: 4,
})

export interface ITrustedServer {
	id: number
	url: string
	status: typeof TrustedServerStatus[keyof typeof TrustedServerStatus]
}

export class ApiError extends Error {}

/**
 * Add a new trusted server
 *
 * @param url - The new URL to add
 */
export async function addServer(url: string): Promise<ITrustedServer> {
	try {
		const { data } = await axios.post<OCSResponse<Omit<ITrustedServer, 'status'>>>(
			generateOcsUrl('apps/federation/trusted-servers'),
			{ url },
		)

		const serverData = data.ocs.data
		return {
			id: serverData.id,
			url: serverData.url,
			status: TrustedServerStatus.STATUS_PENDING,
		}
	} catch (error) {
		throw mapError(error)
	}
}

/**
 * @param id - The id of the trusted server to remove
 */
export async function deleteServer(id: number): Promise<void> {
	try {
		await axios.delete(generateOcsUrl(`apps/federation/trusted-servers/${id}`))
	} catch (error) {
		throw mapError(error)
	}
}

/**
 * Error handling for API calls
 *
 * @param error - The catch error
 */
function mapError(error: unknown): ApiError | unknown {
	if (isAxiosError(error) && error.response?.data?.ocs) {
		return new ApiError((error.response.data as OCSResponse).ocs.meta.message, { cause: error })
	}
	return error
}
