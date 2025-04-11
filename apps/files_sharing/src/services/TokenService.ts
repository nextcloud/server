/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'

interface TokenData {
	ocs: {
		data: {
			token: string,
		}
	}
}

export const generateToken = async (): Promise<string> => {
	const { data } = await axios.get<TokenData>(generateOcsUrl('/apps/files_sharing/api/v1/token'))
	return data.ocs.data.token
}
