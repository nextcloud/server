/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { IFileListFilterChip, INode } from '@nextcloud/files'

import { FileListFilter, registerFileListFilter } from '@nextcloud/files'
import Vue from 'vue'
import FileListFilterAccount from '../components/FileListFilterAccount.vue'

export interface IAccountData {
	uid: string
	displayName: string
}

type CurrentInstance = Vue & { resetFilter: () => void, toggleAccount: (account: string) => void }

/**
 * File list filter to filter by owner / sharee
 */
class AccountFilter extends FileListFilter {

	private currentInstance?: CurrentInstance
	private filterAccounts?: IAccountData[]

	constructor() {
		super('files_sharing:account', 100)
	}

	public mount(el: HTMLElement) {
		if (this.currentInstance) {
			this.currentInstance.$destroy()
		}

		const View = Vue.extend(FileListFilterAccount as never)
		this.currentInstance = new View({
			el,
		})
			.$on('update:accounts', this.setAccounts.bind(this))
			.$mount() as CurrentInstance
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

}

/**
 * Register the file list filter by owner or sharees
 */
export function registerAccountFilter() {
	registerFileListFilter(new AccountFilter())
}
