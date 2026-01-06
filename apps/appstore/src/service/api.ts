/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { OCSResponse } from '@nextcloud/typings/ocs'
import type { IAppstoreApp, IAppstoreCategory } from '../apps.d.ts'

import axios from '@nextcloud/axios'
import { confirmPassword } from '@nextcloud/password-confirmation'
import { generateOcsUrl } from '@nextcloud/router'
import { APPSTORE_CATEGORY_ICONS } from '../constants.ts'

const BASE_URL = generateOcsUrl('apps/appstore/api/v1')
const Url = Object.freeze({
	apps: `${BASE_URL}/apps`,
	categories: `${BASE_URL}/apps/categories`,
	enable: `${BASE_URL}/apps/enable`,
	disable: `${BASE_URL}/apps/disable`,
	uninstall: `${BASE_URL}/apps/uninstall`,
	update: `${BASE_URL}/apps/update`,
})

/**
 * Update an app by its app id
 *
 * @param appId - The app id to update
 */
export async function updateApp(appId: string) {
	await confirmPassword()
	await axios.post(Url.update, { appId })
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
