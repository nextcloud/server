/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export enum LockType {
	User = 0,
	App = 1,
	Token = 2,
}

export type LockState = {
	isLocked: boolean,
	lockOwner: string,
	lockOwnerDisplayName: string,
	lockOwnerType: LockType,
	lockOwnerEditor: string,
	lockTime: number,
}
