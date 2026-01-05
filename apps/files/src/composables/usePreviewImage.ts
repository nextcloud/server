/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { INode } from '@nextcloud/files'
import type { MaybeRefOrGetter } from '@vueuse/core'

import { FileType } from '@nextcloud/files'
import { generateUrl } from '@nextcloud/router'
import { getSharingToken, isPublicShare } from '@nextcloud/sharing/public'
import { toValue } from '@vueuse/core'
import { computed } from 'vue'

/**
 * Get the preview URL for a given node.
 *
 * @param node - The node to get the preview for
 * @param options - The preview options
 * @param options.crop - Whether to crop the preview (default: true)
 * @param options.fallback - Whether to use a mime type icon as fallback (default: true)
 * @param options.size - The size of the preview in pixels (default: 128). Can be a number or a tuple [width, height]
 */
export function usePreviewImage(
	node: MaybeRefOrGetter<INode | undefined>,
	options: MaybeRefOrGetter<{ crop?: boolean, fallback?: boolean, size?: number | [number, number] }> = {},
) {
	return computed(() => {
		const source = toValue(node)
		if (!source) {
			return
		}

		if (source.type === FileType.Folder) {
			return
		}

		const fallback = toValue(options).fallback ?? true
		if (source.attributes['has-preview'] !== true
			&& source.mime !== undefined
			&& source.mime !== 'application/octet-stream'
		) {
			if (!fallback) {
				return
			}

			const previewUrl = generateUrl('/core/mimeicon?mime={mime}', {
				mime: source.mime,
			})
			const url = new URL(window.location.origin + previewUrl)
			return url.href
		}

		const crop = toValue(options).crop ?? true
		const [sizeX, sizeY] = [toValue(options).size ?? 128].flat()

		try {
			const previewUrl = source.attributes.previewUrl
				|| (isPublicShare()
					? generateUrl('/apps/files_sharing/publicpreview/{token}?file={file}', {
							token: getSharingToken()!,
							file: source.path,
						})
					: generateUrl('/core/preview?fileId={fileid}', {
							fileid: String(source.fileid),
						})
				)
			const url = new URL(window.location.origin + previewUrl)

			// Request tiny previews
			url.searchParams.set('x', sizeX.toString())
			url.searchParams.set('y', (sizeY ?? sizeX).toString())
			url.searchParams.set('mimeFallback', fallback.toString())

			// Etag to force refresh preview on change
			const etag = source.attributes.etag || source.mtime?.getTime() || ''
			url.searchParams.set('v', etag.slice(0, 6))

			// Handle cropping
			url.searchParams.set('a', crop ? '0' : '1')
			return url.href
		} catch {
			return
		}
	})
}
