/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface IAppstoreCategory {
	/**
	 * The category ID
	 */
	id: string
	/**
	 * The display name (can be localized)
	 */
	displayName: string
	/**
	 * Inline SVG path
	 */
	icon: string
}

export interface IAppstoreAppRelease {
	version: string
	translations: {
		[key: string]: {
			changelog: string
		}
	}
}

export interface IAppstoreApp {
	id: string
	name: string
	summary: string
	description: string
	licence: string
	author: string[] | Record<string, string>
	level: number
	version: string
	category: string|string[]

	preview?: string
	screenshot?: string

	app_api: boolean
	active: boolean
	internal: boolean
	removable: boolean
	installed: boolean
	canInstall: boolean
	canUnInstall: boolean
	isCompatible: boolean
	needsDownload: boolean
	update?: string

	appstoreData: Record<string, never>
	releases?: IAppstoreAppRelease[]
}

export interface IComputeDevice {
	id: string,
	label: string,
}

export interface IDeployConfig {
	computeDevice: IComputeDevice,
	net: string,
	nextcloud_url: string,
}

export interface IDeployDaemon {
	accepts_deploy_id: string,
	deploy_config: IDeployConfig,
	display_name: string,
	host: string,
	id: number,
	name: string,
	protocol: string,
}

export interface IExAppStatus {
	action: string
	deploy: number
	deploy_start_time: number
	error: string
	init: number
	init_start_time: number
	type: string
}

export interface IDeployEnv {
	envName: string
	displayName: string
	description: string
	default?: string
}

export interface IDeployMount {
	hostPath: string
	containerPath: string
	readOnly: boolean
}

export interface IDeployOptions {
	environment_variables: IDeployEnv[]
	mounts: IDeployMount[]
}

export interface IAppstoreExAppRelease extends IAppstoreAppRelease {
	environmentVariables?: IDeployEnv[]
}

export interface IAppstoreExApp extends IAppstoreApp {
	daemon: IDeployDaemon | null | undefined
	status: IExAppStatus | Record<string, never>
	error: string
	releases: IAppstoreExAppRelease[]
}
