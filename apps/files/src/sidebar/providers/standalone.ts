/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, INode, IView } from '@nextcloud/files'
import type { ISidebarDataProvider } from '../types.ts'

import { shallowRef } from 'vue'

/**
 * Create the data provider used when the sidebar is embedded into another app.
 *
 * Nodes are fetched from the WebDAV API on demand,
 * there is neither an active folder nor an active view outside of the files app.
 */
export function createStandaloneDataProvider(): ISidebarDataProvider {
	const node = shallowRef<INode>()
	const folder = shallowRef<IFolder>()
	const view = shallowRef<IView>()

	/**
	 * Set the node to render the sidebar for.
	 *
	 * @param newNode - The node to render the sidebar for
	 */
	function setNode(newNode?: INode): void {
		node.value = newNode
	}

	return {
		node,
		folder,
		view,

		setNode,
	}
}
