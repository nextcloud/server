/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Types mirroring the unified sharing API (`@nextcloud/sharing/dialog`).
 *
 * The dialog library is Vue 3 and cannot be imported into this Vue 2 frontend,
 * so the subset of the schema needed to list shares in the sidebar is kept here.
 * Keep in sync with the library's `lib/dialog/types/api.ts`.
 */

export interface SharingIconSVG {
	svg: string
}

export interface SharingIconURL {
	light: string
	dark: string
}

export type SharingIcon = SharingIconSVG | SharingIconURL

export interface SharingOwner {
	user_id: string
	instance: string | null
	display_name: string
	icon: SharingIcon
}

export interface SharingSource {
	class: string
	value: string
	display_name: string
	icon: SharingIcon | null
}

export interface SharingRecipientSecret {
	updatable: boolean
	value?: string
	url?: string
}

export interface SharingRecipient {
	class: string
	value: string
	instance: string | null
	display_name: string
	icon: SharingIcon | null
	secret: SharingRecipientSecret
	initiator: SharingOwner | null
	/** Per-recipient permissions, capped at the share-level ones */
	permissions: SharingPermission[]
}

export interface SharingPermission {
	class: string
	source_class: string | null
	display_name: string
	hint: string | null
	priority: number
	presets: string[]
	enabled: boolean
}

export type SharingState = 'active' | 'draft' | 'deleted'

export interface SharingShare {
	id: string
	owner: SharingOwner
	last_updated: number
	state: SharingState
	sources: SharingSource[]
	recipients: SharingRecipient[]
	permissions: SharingPermission[]
	permission_preset: string | null
}
