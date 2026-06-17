/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IContactsMenuAction } from '../types/contactsMenuAction.ts'

export default class ContactsMenuService {
	private _actions: IContactsMenuAction[]

	constructor() {
		this._actions = []
	}

	get actions(): IContactsMenuAction[] {
		return this._actions
	}

	/*
	 * Register an action for the contacts menu
	 * Actions use NcButton
	 */
	addAction(action: IContactsMenuAction): void {
		this._actions.push(action)
	}
}
