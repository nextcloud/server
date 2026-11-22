/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export interface ITwoFactorBackupCodesState {
	enabled: boolean
	total: number
	used: number
}

export interface IApiResponse {
	codes: string[]
	state: ITwoFactorBackupCodesState
}

/**
 * Generate new backup codes
 */
export async function generateCodes(): Promise<IApiResponse> {
	const url = generateUrl('/apps/twofactor_backupcodes/settings/create')

	const { data } = await axios.post<IApiResponse>(url)
	return data
}
