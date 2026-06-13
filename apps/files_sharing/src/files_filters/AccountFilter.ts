/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IFileListFilterChip, IFileListFilterWithUi, INode } from '@nextcloud/files'

import svgAccountMultipleOutline from '@mdi/svg/svg/account-multiple-outline.svg?raw'
import { subscribe } from '@nextcloud/event-bus'
import { FileListFilter, registerFileListFilter } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { ShareType } from '@nextcloud/sharing'
import { isPublicShare } from '@nextcloud/sharing/public'
import wrap from '@vue/web-component-wrapper'
import Vue from 'vue'
import FileListFilterAccount from '../components/FileListFilterAccount.vue'

// once files_sharing is migrated to the new frontend use the import instead:
// import { TRASHBIN_VIEW_ID } from '../../../files_trashbin/src/files_views/trashbinView.ts'
const TRASHBIN_VIEW_ID = 'trashbin'

export interface IAccountData {
	uid: string
	displayName: string
}

const tagName = 'files_sharing-file-list-filter-account'

/**
 * File list filter to filter by owner / sharee
 */
class AccountFilter extends FileListFilter implements IFileListFilterWithUi {
	#availableAccounts: IAccountData[]
	#filterAccounts?: IAccountData[]

	public readonly displayName = t('files_sharing', 'People')
	public readonly iconSvgInline = svgAccountMultipleOutline
	public readonly tagName = tagName

	constructor() {
		super('files_sharing:account', 100)
		this.#availableAccounts = []

		subscribe('files:list:updated', ({ contents }) => {
			this.updateAvailableAccounts(contents)
		})
	}

	public get availableAccounts() {
		return this.#availableAccounts
	}

	public get filterAccounts() {
		return this.#filterAccounts
	}

	public filter(nodes: INode[]): INode[] {
		if (!this.#filterAccounts || this.#filterAccounts.length === 0) {
			return nodes
		}

		const userIds = this.#filterAccounts.map(({ uid }) => uid)
		// Filter if the owner of the node is in the list of filtered accounts
		return nodes.filter((node) => {
			if (window.OCP.Files.Router.params.view === TRASHBIN_VIEW_ID) {
				const deletedBy = node.attributes?.['trashbin-deleted-by-id']
				if (deletedBy && userIds.includes(deletedBy)) {
					return true
				}
				return false
			}

			// if the owner matches
			if (node.owner && userIds.includes(node.owner)) {
				return true
			}

			// Or any of the sharees (if only one share this will be an object, otherwise an array. So using `.flat()` to make it always an array)
			const sharees = node.attributes.sharees?.sharee as { id: string }[] | undefined
			if (sharees && [sharees].flat().some(({ id }) => userIds.includes(id))) {
				return true
			}

			// If the node provides no information lets keep it
			if (!node.owner && !sharees) {
				return true
			}

			// Not a valid node for the current filter
			return false
		})
	}

	public reset(): void {
		this.dispatchEvent(new CustomEvent('reset'))
	}

	/**
	 * Set accounts that should be filtered.
	 *
	 * @param accounts - Account to filter or undefined if inactive.
	 */
	public setAccounts(accounts?: IAccountData[]) {
		this.#filterAccounts = accounts
		let chips: IFileListFilterChip[] = []
		if (this.#filterAccounts && this.#filterAccounts.length > 0) {
			chips = this.#filterAccounts.map(({ displayName, uid }) => ({
				text: displayName,
				user: uid,
				onclick: () => this.dispatchEvent(new CustomEvent('deselect', { detail: uid })),
			}))
		}

		this.updateChips(chips)
		this.filterUpdated()
	}

	/**
	 * Update the accounts owning nodes or have nodes shared to them.
	 *
	 * @param nodes - The current content of the file list.
	 */
	protected updateAvailableAccounts(nodes: INode[]): void {
		const available = new Map<string, IAccountData>()

		for (const node of nodes) {
			const owner = node.owner
			if (owner && !available.has(owner)) {
				available.set(owner, {
					uid: owner,
					displayName: node.attributes['owner-display-name'] ?? node.owner,
				})
			}

			// ensure sharees is an array (if only one share then it is just an object)
			const sharees: { id: string, 'display-name': string, type: ShareType }[] = [node.attributes.sharees?.sharee].flat().filter(Boolean)
			for (const sharee of [sharees].flat()) {
				// Skip link shares and other without user
				if (sharee.id === '') {
					continue
				}
				if (sharee.type !== ShareType.User && sharee.type !== ShareType.Remote) {
					continue
				}
				// Add if not already added
				if (!available.has(sharee.id)) {
					available.set(sharee.id, {
						uid: sharee.id,
						displayName: sharee['display-name'],
					})
				}
			}

			// lets also handle trashbin
			const deletedBy = node.attributes?.['trashbin-deleted-by-id']
			if (deletedBy) {
				available.set(deletedBy, {
					uid: deletedBy,
					displayName: node.attributes?.['trashbin-deleted-by-display-name'] || deletedBy,
				})
			}
		}

		this.#availableAccounts = [...available.values()]
		this.dispatchEvent(new CustomEvent('accounts-updated'))
	}
}

export type { AccountFilter }

/**
 * Register the file list filter by owner or sharees
 */
export function registerAccountFilter() {
	if (isPublicShare()) {
		// We do not show the filter on public pages - it makes no sense
		return
	}

	const WrappedComponent = wrap(Vue, FileListFilterAccount)
	// In Vue 2, wrap doesn't support disabling shadow :(
	// Disable with a hack
	Object.defineProperty(WrappedComponent.prototype, 'attachShadow', {
		value() {
			return this
		},
	})
	Object.defineProperty(WrappedComponent.prototype, 'shadowRoot', {
		get() {
			return this
		},
	})

	customElements.define(tagName, WrappedComponent)
	registerFileListFilter(new AccountFilter())
}
