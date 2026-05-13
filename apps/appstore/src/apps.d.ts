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
	lastModified?: string
	translations: {
		[key: string]: {
			changelog: string
		}
	}
}

type IAppInfoTypes = 'prelogin' | 'filesystem' | 'authentication' | 'extended_authentication' | 'logging' | 'dav' | 'prevent_group_restriction' | 'session'

/**
 * The metadata that is available in the info.xml of an app.
 * This is sourced by the appstore but also available for already installed apps (e.g. shipped apps).
 */
interface IAppInfoData {
	id: string
	name: string
	summary: string
	description: string
	/** The license of the app */
	license: string
	/** The author(s) of the app (either list of names or object for XML nodes) */
	author: string[] | Record<string, string>
	/** The support level of this app (e.g. maintained by Nextcloud GmbH) */
	level: number
	/** The version of the app */
	version: string
	/** The category(s) this app belongs to */
	category: string | string[]
	/** The URL of the app's screenshot */
	screenshot?: string
	/** The types this app supports */
	types?: IAppInfoTypes[]

	documentation?: {
		admin: string
		user: string
		developer: string
	}
	website?: string
	discussion?: string
	bugs?: string
}

/**
 * Metadata added when this app is sourced from the appstore.
 * It is not available for non-appstore apps.
 */
interface IAppstoreMetadata {
	fromAppStore: true
	/** List of appstore release information (e.g. changelog) */
	releases: IAppstoreAppRelease[]
	/** The overall rating of the app */
	ratingOverall: number
	/** The number of ratings for the app */
	ratingNumOverall: number
}

export interface IAppstoreAppResponse extends IAppInfoData, Partial<IAppstoreMetadata> {
	/** The app icon to use */
	icon?: string

	// App dependency information
	dependencies: unknown

	// App state information

	/** Whether the app is an ExApp (docker based app) */
	app_api: false
	/** Whether the app is internal = always enabled an cannot be disabled */
	internal: boolean
	/** Whether the app is shipped / bundled with Nextcloud (not from appstore) */
	shipped: boolean
	/** Whether the app is currently active (enabled) */
	active: boolean
	/** Whether the app can be removed */
	removable: boolean
	/** Whether the app is installed */
	installed: boolean
	/** If all dependencies are met */
	isCompatible: boolean
	/** Whether the app needs to be downloaded (not locally available) */
	needsDownload: boolean
	/** List of missing dependencies */
	missingDependencies?: string[]
	/** Available update version */
	update?: string
	/** User groups this app is limited to */
	groups?: string[]
}

export interface IAppstoreApp extends IAppstoreAppResponse {
	loading?: boolean
}

export interface IComputeDevice {
	id: string
	label: string
}

export interface IDeployConfig {
	computeDevice: IComputeDevice
	net: string
	nextcloud_url: string
}

export interface IDeployDaemon {
	accepts_deploy_id: string
	deploy_config: IDeployConfig
	display_name: string
	host: string
	id: number
	name: string
	protocol: string
	exAppsCount: number
}

export interface IExAppStatus {
	action: string
	deploy: number
	deploy_start_time?: number
	error?: string
	init: number
	init_start_time?: number
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
	app_api: true
	daemon: IDeployDaemon | null | undefined
	status: IExAppStatus | Record<string, never>
	error?: string
	releases: IAppstoreExAppRelease[]
}

export interface IAppBundle {
	id: string
	name: string
	appIdentifiers: readonly string[]
}
