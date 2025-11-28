/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import AutoRenewSvg from '@mdi/svg/svg/autorenew.svg?raw'
import { getCapabilities } from '@nextcloud/capabilities'
import { FileAction, registerFileAction } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { convertFile, convertFiles } from './convertUtils.ts'

type ConversionsProvider = {
	from: string
	to: string
	displayName: string
}

export const ACTION_CONVERT = 'convert'

/**
 *
 */
export function registerConvertActions() {
	// Generate sub actions
	const convertProviders = getCapabilities()?.files?.file_conversions as ConversionsProvider[] ?? []
	const actions = convertProviders.map(({ to, from, displayName }) => {
		return new FileAction({
			id: `convert-${from}-${to}`,
			displayName: () => t('files', 'Save as {displayName}', { displayName }),
			iconSvgInline: () => generateIconSvg(to),
			enabled: ({ nodes }) => {
				// Check that all nodes have the same mime type
				return nodes.every((node) => from === node.mime)
			},

			async exec({ nodes }) {
				if (!nodes[0]) {
					return false
				}

				// If we're here, we know that the node has a fileid
				convertFile(nodes[0].fileid as number, to)

				// Silently terminate, we'll handle the UI in the background
				return null
			},

			async execBatch({ nodes }) {
				const fileIds = nodes.map((node) => node.fileid).filter(Boolean) as number[]
				convertFiles(fileIds, to)

				// Silently terminate, we'll handle the UI in the background
				return Array(nodes.length).fill(null)
			},

			parent: ACTION_CONVERT,
		})
	})

	// Register main action
	registerFileAction(new FileAction({
		id: ACTION_CONVERT,
		displayName: () => t('files', 'Save as …'),
		iconSvgInline: () => AutoRenewSvg,
		enabled: (context) => {
			return actions.some((action) => action.enabled!(context))
		},
		async exec() {
			return null
		},
		order: 25,
	}))

	// Register sub actions
	actions.forEach(registerFileAction)
}

/**
 *
 * @param mime
 */
export function generateIconSvg(mime: string) {
	// Generate icon based on mime type
	const url = generateUrl('/core/mimeicon?mime=' + encodeURIComponent(mime))
	return `<svg width="32" height="32" viewBox="0 0 32 32"
		xmlns="http://www.w3.org/2000/svg">
		<image href="${url}" height="32" width="32" />
	</svg>`
}
