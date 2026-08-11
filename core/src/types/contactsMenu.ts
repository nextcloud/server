/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface IPreviewUser {
	uid: string
	fullName: string
	isUser: boolean
}

export interface ITeam {
	teamId: string
	displayName: string
	link: string
}
