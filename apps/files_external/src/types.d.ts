/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface IStorage {
	id?: number

	mountPoint: string
	backend: string
	authMechanism: string
	backendOptions: Record<string, unknown>
	priority?: number
	applicableUsers?: string[]
	applicableGroups?: string[]
	mountOptions?: Record<string, unknown>
	status?: number
	statusMessage?: string
	userProvided: bool
	type: 'personal' | 'system'
}
