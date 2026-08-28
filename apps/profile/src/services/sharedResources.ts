/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { AxiosResponse } from '@nextcloud/axios'

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

export interface SharedResource {
	label: string
	text: string
	href: string
	img: string
}

interface OcsResponse<T> {
	ocs: {
		data: T
	}
}

/**
 * Fetch resources shared between the current user and the given user.
 *
 * @param userId - The user ID of the profile being viewed
 */
export async function getSharedResources(userId: string): Promise<SharedResource[]> {
	const url = generateOcsUrl('/apps/profile/api/v1/resources/{userId}', { userId })
	const response = await axios.get(url) as AxiosResponse<OcsResponse<SharedResource[]>>
	return response.data.ocs.data
}
