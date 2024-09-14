/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Node, View } from '@nextcloud/files'

import { DefaultType, FileAction, Permission, registerFileAction } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import svgEye from '@mdi/svg/svg/eye.svg?raw'

/**
 * @param node The file to open
 * @param view any The files view
 * @param dir the directory path
 */
function pushToHistory(node: Node, view: View, dir: string) {
	window.OCP.Files.Router.goToRoute(
		null,
		{ view: view.id, fileid: String(node.fileid) },
		{ dir, openfile: 'true' },
		true,
	)
}

/**
 * Execute the viewer files action
 * @param node The active node
 * @param view The current view
 * @param dir The current path
 */
async function execAction(node: Node, view: View, dir: string): Promise<boolean|null> {
	const onClose = () => {
		// This can sometime be called with the openfile set to true already. But we don't want to keep openfile when closing the viewer.
		const newQuery = { ...window.OCP.Files.Router.query }
		delete newQuery.openfile
		window.OCP.Files.Router.goToRoute(null, window.OCP.Files.Router.params, newQuery)
	}

	pushToHistory(node, view, dir)
	window.OCA.Viewer.open({ path: node.path, onPrev: pushToHistory, onNext: pushToHistory, onClose })

	return null
}

/**
 * Register the viewer action on the files API
 */
export function registerViewerAction() {
	registerFileAction(new FileAction({
		id: 'view',
		displayName: () => t('viewer', 'View'),
		iconSvgInline: () => svgEye,
		default: DefaultType.DEFAULT,
		enabled: (nodes) => {
			// Disable if not located in user root
			if (nodes.some(node => !(node.isDavRessource && node.root?.startsWith('/files')))) {
				return false
			}

			return nodes.every((node) =>
				Boolean(node.permissions & Permission.READ)
				&& window.OCA.Viewer.mimetypes.includes(node.mime),
			)
		},
		exec: execAction,
	}))
}
