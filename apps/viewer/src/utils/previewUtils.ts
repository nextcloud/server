/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { BasicFileInfo } from './models'
import { encodePath } from '@nextcloud/paths'
import { generateUrl } from '@nextcloud/router'
import { getSharingToken, isPublicShare } from '@nextcloud/sharing/public'

/**
 * @param root0
 * @param root0.fileid
 * @param root0.filename
 * @param root0.previewUrl
 * @param root0.hasPreview
 * @param root0.davPath
 * @param root0.etag
 * @return the preview url if the file have an existing preview or the absolute dav remote path if none.
 */
export function getPreviewIfAny({ fileid, filename, previewUrl, hasPreview, davPath, etag }: BasicFileInfo): string {
	if (previewUrl) {
		return previewUrl
	}

	const searchParams = `fileId=${fileid}`
		+ `&x=${Math.floor(screen.width * devicePixelRatio)}`
		+ `&y=${Math.floor(screen.height * devicePixelRatio)}`
		+ '&a=true'
		+ (etag ? `&etag=${String(etag).replace(/&quot;/g, '')}` : '')

	if (hasPreview) {
		// TODO: find a nicer standard way of doing this?
		if (isPublicShare()) {
			return generateUrl(`/apps/files_sharing/publicpreview/${getSharingToken()}?file=${encodePath(filename)}&${searchParams}`)
		}
		return generateUrl(`/core/preview?${searchParams}`)
	}
	return davPath
}
