/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { mdiCheckNetworkOutline, mdiCloseNetworkOutline, mdiHelpNetworkOutline, mdiNetworkOffOutline, mdiNetworkOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'

export const Visibility = Object.freeze({
	None: 0,
	Personal: 1,
	Admin: 2,
	Default: 3,
})

export const ConfigurationType = Object.freeze({
	String: 0,
	Boolean: 1,
	Password: 2,
})

export const ConfigurationFlag = Object.freeze({
	None: 0,
	Optional: 1,
	UserProvided: 2,
	Hidden: 4,
})

export const StorageStatus = Object.freeze({
	Success: 0,
	Error: 1,
	Indeterminate: 2,
	IncompleteConf: 3,
	Unauthorized: 4,
	Timeout: 5,
	NetworkError: 6,
})

export const MountOptionsCheckFilesystem = Object.freeze({
	/**
	 * Never check the underlying filesystem for updates
	 */
	Never: 0,

	/**
	 * check the underlying filesystem for updates once every request for each file
	 */
	OncePerRequest: 1,

	/**
	 * Always check the underlying filesystem for updates
	 */
	Always: 2,
})

export const StorageStatusIcons = Object.freeze({
	[StorageStatus.Success]: mdiCheckNetworkOutline,
	[StorageStatus.Error]: mdiCloseNetworkOutline,
	[StorageStatus.Indeterminate]: mdiNetworkOutline,
	[StorageStatus.IncompleteConf]: mdiHelpNetworkOutline,
	[StorageStatus.Unauthorized]: mdiCloseNetworkOutline,
	[StorageStatus.Timeout]: mdiNetworkOffOutline,
	[StorageStatus.NetworkError]: mdiNetworkOffOutline,
})

export const StorageStatusMessage = Object.freeze({
	[StorageStatus.Success]: t('files_external', 'Connected'),
	[StorageStatus.Error]: t('files_external', 'Error'),
	[StorageStatus.Indeterminate]: t('files_external', 'Indeterminate'),
	[StorageStatus.IncompleteConf]: t('files_external', 'Incomplete configuration'),
	[StorageStatus.Unauthorized]: t('files_external', 'Unauthorized'),
	[StorageStatus.Timeout]: t('files_external', 'Timeout'),
	[StorageStatus.NetworkError]: t('files_external', 'Network error'),
})

export interface IConfigurationOption {
	/**
	 * Bitmask of ConfigurationFlag
	 *
	 * @see ConfigurationFlag
	 */
	flags: number
	/**
	 * Type of the configuration option
	 *
	 * @see ConfigurationType
	 */
	type: typeof ConfigurationType[keyof typeof ConfigurationType]
	/**
	 * Visible name of the configuration option
	 */
	value: string
	/**
	 * Optional tooltip for the configuration option
	 */
	tooltip?: string
}

export interface IAuthMechanism {
	name: string
	identifier: string
	identifierAliases: string[]
	scheme: string
	/**
	 * The visibility of this auth mechanism
	 *
	 * @see Visibility
	 */
	visibility: number
	configuration: Record<string, IConfigurationOption>
}

export interface IBackend {
	name: string
	identifier: string
	identifierAliases: string[]
	authSchemes: Record<string, boolean>
	priority: number
	configuration: Record<string, IConfigurationOption>
}

export interface IMountOptions {
	encrypt: boolean
	previews: boolean
	enable_sharing: boolean
	/**
	 * @see MountOptionsCheckFilesystem
	 */
	filesystem_check_changes: typeof MountOptionsCheckFilesystem[keyof typeof MountOptionsCheckFilesystem]
	encoding_compatibility: boolean
	readonly: boolean
}

export interface IStorage {
	id?: number

	mountPoint: string
	backend: string
	authMechanism: string
	backendOptions: Record<string, string | boolean>
	priority?: number
	applicableUsers?: string[]
	applicableGroups?: string[]
	mountOptions?: Record<string, unknown>
	/**
	 * @see StorageStatus
	 */
	status?: typeof StorageStatus[keyof typeof StorageStatus]
	statusMessage?: string
	userProvided: boolean
	type: 'personal' | 'system'
}
