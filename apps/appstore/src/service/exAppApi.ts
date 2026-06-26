/*!
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 */

import type { IAppstoreExApp, IDeployDaemon, IDeployOptions, IExAppStatus } from '../apps.d.ts'

import axios from '@nextcloud/axios'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateUrl } from '@nextcloud/router'

/**
 * Fetch all external (app_api) apps from the server.
 */
export async function fetchApps() {
	const { data } = await axios.get(generateUrl('/apps/app_api/apps/list'))
	return data.apps as IAppstoreExApp[]
}

/**
 * Get the status of an external app.
 *
 * @param appId - The app to fetch
 */
export async function fetchAppStatus(appId: string) {
	const { data } = await axios.get<IExAppStatus>(generateUrl(`/apps/app_api/apps/status/${appId}`))
	return data
}

/**
 * Enable an external app.
 *
 * @param app - The app to enable
 * @param daemon - The daemon to use for deployment
 * @param deployOptions - Additional options for deployment
 */
export async function enableExApp(app: IAppstoreExApp, daemon: IDeployDaemon, deployOptions?: IDeployOptions) {
	await confirmPassword()
	await axios.post(generateUrl(`/apps/app_api/apps/enable/${app.id}/${daemon.name}`), { deployOptions })
}

/**
 * Force enable an external app
 *
 * @param appId - The app to force-enable
 */
export async function forceEnableExApp(appId: string) {
	await confirmPassword()
	await axios.post(generateUrl('/apps/app_api/apps/force'), { appId })
}

/**
 * Disable an external app.
 *
 * @param appId - The app to disable
 */
export async function disableExApp(appId: string) {
	await confirmPassword()
	await axios.get(generateUrl(`apps/app_api/apps/disable/${appId}`))
}

/**
 * Remove an external app.
 *
 * @param appId - The app to uninstall
 * @param removeData - If all data should be removed
 */
export async function uninstallExApp(appId: string, removeData = false) {
	await confirmPassword()
	await axios.get(generateUrl(`/apps/app_api/apps/uninstall/${appId}?removeData=${removeData}`))
}
