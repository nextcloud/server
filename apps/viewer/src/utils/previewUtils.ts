/**
 * @copyright Copyright (c) 2023 Louis Chmn <louis@chmn.me>
 *
 * @author Louis Chmn <louis@chmn.me>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

import { generateUrl } from '@nextcloud/router'
import { getToken, isPublic } from './davUtils'
import type { BasicFileInfo } from './models'
import { encodePath } from '@nextcloud/paths'

/**
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
		+ (etag !== null ? `&etag=${etag.replace(/&quot;/g, '')}` : '')

	if (hasPreview) {
		// TODO: find a nicer standard way of doing this?
		if (isPublic()) {
			return generateUrl(`/apps/files_sharing/publicpreview/${getToken()}?file=${encodePath(filename)}&${searchParams}`)
		}
		return generateUrl(`/core/preview?${searchParams}`)
	}
	return davPath
}
