/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { OCSResponse } from '@nextcloud/typings/ocs'
import type { IAppstoreApp, IAppstoreCategory } from '../apps.d.ts'

import axios from '@nextcloud/axios'
import { addPasswordConfirmationInterceptors, PwdConfirmationMode } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'
import PQueue from 'p-queue'
import { APPSTORE_CATEGORY_ICONS } from '../constants.ts'

addPasswordConfirmationInterceptors(axios)

const BASE_URL = generateOcsUrl('apps/appstore/api/v1')
const Url = Object.freeze({
	apps: `${BASE_URL}/apps`,
	categories: `${BASE_URL}/apps/categories`,
	enable: `${BASE_URL}/apps/enable`,
	disable: `${BASE_URL}/apps/disable`,
	uninstall: `${BASE_URL}/apps/uninstall`,
	update: `${BASE_URL}/apps/update`,
	bundleEnable: `${BASE_URL}/bundles/enable`,
})

const queue = new PQueue({ concurrency: 1 })

/**
 * Enable an app by its app id
 *
 * @param appId - The app to enable
 * @param force - Whether to force enable the app
 * @param groups - The groups to enable the app for
 */
export async function enableApp(appId: string, force = false, groups?: string[]) {
	return queue.add(async () => {
		await axios.post(Url.enable, { appId, groups, force: force || undefined }, { confirmPassword: PwdConfirmationMode.Strict })
	})
}

/**
 * Disable app by its app id
 *
 * @param appId - The app to disable
 */
export async function disableApp(appId: string) {
	return queue.add(async () => {
		await axios.post(Url.disable, { appId }, { confirmPassword: PwdConfirmationMode.Lax })
	})
}

/**
 * Update an app by its app id
 *
 * @param appId - The app id to update
 */
export async function updateApp(appId: string) {
	return queue.add(async () => {
		await axios.post(Url.update, { appId }, { confirmPassword: PwdConfirmationMode.Strict })
	})
}

/**
 * Uninstall an app by its app id
 *
 * @param appId - The app to uninstall
 */
export async function uninstallApp(appId: string) {
	return queue.add(async () => {
		await axios.post(Url.uninstall, { appId }, { confirmPassword: PwdConfirmationMode.Strict })
	})
}

/**
 * Get all apps from the appstore
 */
export async function getApps() {
	const { data } = await axios.get<OCSResponse<IAppstoreApp[]>>(Url.apps)
	return data.ocs.data
}

/**
 * Get app categories
 */
export async function getCategories() {
	const { data } = await axios.get<OCSResponse<IAppstoreCategory[]>>(Url.categories)
	for (const category of data.ocs.data) {
		category.icon = APPSTORE_CATEGORY_ICONS[category.id] ?? ''
	}
	return data.ocs.data
}

/**
 * Enable an app bundle by its id
 *
 * @param bundleId - The id of the bundle to enable
 */
export async function enableBundle(bundleId: string) {
	return queue.add(async () => {
		await axios.post(Url.bundleEnable, { bundleId }, { confirmPassword: PwdConfirmationMode.Strict })
	})
}
