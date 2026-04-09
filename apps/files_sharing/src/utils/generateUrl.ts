/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCapabilities } from '@nextcloud/capabilities'
import { generateUrl, getBaseUrl } from '@nextcloud/router'

interface IGlobalScaleCapabilities {
	token?: string
}

/**
 * @param fileid - The file ID to generate the direct file link for
 */
export function generateFileUrl(fileid: number): string {
	const baseURL = getBaseUrl()

	const { globalscale } = getCapabilities() as { globalscale?: IGlobalScaleCapabilities }
	if (globalscale?.token) {
		return generateUrl('/gf/{token}/{fileid}', {
			token: globalscale.token,
			fileid,
		}, { baseURL })
	}

	return generateUrl('/f/{fileid}', {
		fileid,
	}, {
		baseURL,
	})
}
