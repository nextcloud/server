/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { IFileListFilterChip, INode } from '@nextcloud/files'

import { subscribe } from '@nextcloud/event-bus'
import { FileListFilter, registerFileListFilter } from '@nextcloud/files'
import { ShareType } from '@nextcloud/sharing'
import Vue from 'vue'

import FileListFilterAccount from '../components/FileListFilterAccount.vue'

export interface IAccountData {
	uid: string
	displayName: string
}

type CurrentInstance = Vue & {
	resetFilter: () => void
	setAvailableAccounts: (accounts: IAccountData[]) => void
	toggleAccount: (account: string) => void
}

/**
 * File list filter to filter by owner / sharee
 */
class AccountFilter extends FileListFilter {

	private availableAccounts: IAccountData[]
	private currentInstance?: CurrentInstance
	private filterAccounts?: IAccountData[]

	constructor() {
		super('files_sharing:account', 100)
		this.availableAccounts = []

		subscribe('files:list:updated', ({ contents }) => {
			this.updateAvailableAccounts(contents)
		})
	}

	public mount(el: HTMLElement) {
		if (this.currentInstance) {
			this.currentInstance.$destroy()
		}

		const View = Vue.extend(FileListFilterAccount as never)
		this.currentInstance = new View({ el })
			.$on('update:accounts', (accounts?: IAccountData[]) => this.setAccounts(accounts))
			.$mount() as CurrentInstance
		this.currentInstance
			.setAvailableAccounts(this.availableAccounts)
	}

	public filter(nodes: INode[]): INode[] {
		if (!this.filterAccounts || this.filterAccounts.length === 0) {
			return nodes
		}

		const userIds = this.filterAccounts.map(({ uid }) => uid)
		// Filter if the owner of the node is in the list of filtered accounts
		return nodes.filter((node) => {
			const sharees = node.attributes.sharees?.sharee as { id: string }[] | undefined
			// If the node provides no information lets keep it
			if (!node.owner && !sharees) {
				return true
			}
			// if the owner matches
			if (node.owner && userIds.includes(node.owner)) {
				return true
			}
			// Or any of the sharees (if only one share this will be an object, otherwise an array. So using `.flat()` to make it always an array)
			if (sharees && [sharees].flat().some(({ id }) => userIds.includes(id))) {
				return true
			}
			// Not a valid node for the current filter
			return false
		})
	}

	public reset(): void {
		this.currentInstance?.resetFilter()
	}

	/**
	 * Set accounts that should be filtered.
	 *
	 * @param accounts - Account to filter or undefined if inactive.
	 */
	public setAccounts(accounts?: IAccountData[]) {
		this.filterAccounts = accounts
		let chips: IFileListFilterChip[] = []
		if (this.filterAccounts && this.filterAccounts.length > 0) {
			chips = this.filterAccounts.map(({ displayName, uid }) => ({
				text: displayName,
				user: uid,
				onclick: () => this.currentInstance?.toggleAccount(uid),
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
		}

		this.availableAccounts = [...available.values()]
		if (this.currentInstance) {
			this.currentInstance.setAvailableAccounts(this.availableAccounts)
		}
	}

}

/**
 * Register the file list filter by owner or sharees
 */
export function registerAccountFilter() {
	registerFileListFilter(new AccountFilter())
}
