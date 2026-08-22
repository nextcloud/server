/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFolder, INode, IView } from '@nextcloud/files'
import type { Ref } from 'vue'

/**
 * Adapter between the sidebar and the data source of the app rendering it.
 */
export interface ISidebarDataProvider {
	/**
	 * The node to render the sidebar for.
	 */
	readonly node: Readonly<Ref<INode | undefined>>

	/**
	 * The folder containing `node`, if the app provides one.
	 */
	readonly folder: Readonly<Ref<IFolder | undefined>>

	/**
	 * The view the sidebar is rendered in, if the app provides one.
	 */
	readonly view: Readonly<Ref<IView | undefined>>

	/**
	 * Set the node the sidebar is rendered for, the provider resolves the matching context.
	 *
	 * @param node - The node to render the sidebar for, `undefined` to reset it
	 */
	setNode(node?: INode): void

	/**
	 * Called after the sidebar was opened or closed, for apps that need to
	 * synchronize side effects like the current URL or the page layout.
	 *
	 * @param isOpen - The new open state
	 */
	onOpenStateChanged?(isOpen: boolean): void
}
