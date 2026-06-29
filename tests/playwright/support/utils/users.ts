/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { APIRequestContext } from '@playwright/test'

/**
 * Grant subadmin rights to `subadminId` over `groupId` via the OCS Provisioning API.
 * Uses admin Basic Auth because this call is typically made in fixture setup before
 * any browser session exists.
 *
 * @param request - A Playwright APIRequestContext (global or browser-level)
 * @param subadminId - The user id to promote
 * @param groupId - The group id to grant subadmin rights over
 */
export async function makeSubAdmin(
	request: APIRequestContext,
	subadminId: string,
	groupId: string,
): Promise<void> {
	const response = await request.post(`/ocs/v2.php/cloud/users/${subadminId}/subadmins`, {
		headers: {
			Accept: 'application/json',
			'OCS-APIRequest': 'true',
			Authorization: 'Basic ' + Buffer.from('admin:admin').toString('base64'),
		},
		form: { groupid: groupId },
	})
	const meta = (await response.json()).ocs?.meta
	if (meta?.statuscode !== 200 && meta?.statuscode !== 100) {
		throw new Error(`makeSubAdmin(${subadminId}, ${groupId}) failed: ${meta?.statuscode} ${meta?.message}`)
	}
}

/**
 * Set a user's display name through the OCS Provisioning API. A user may edit
 * their own display name, so `request` must be authenticated as `userId`.
 *
 * @param request - A request context authenticated as the user being modified
 * @param userId - The id of the user whose display name to set
 * @param displayName - The new display name
 */
export async function setUserDisplayName(
	request: APIRequestContext,
	userId: string,
	displayName: string,
): Promise<void> {
	const response = await request.put(`/ocs/v2.php/cloud/users/${userId}?format=json`, {
		headers: {
			Accept: 'application/json',
			'OCS-APIRequest': 'true',
		},
		form: { key: 'display', value: displayName },
	})
	// OCS returns HTTP 200 even on failure; the real status lives in ocs.meta
	const meta = (await response.json()).ocs?.meta
	if (meta?.statuscode !== 200) {
		throw new Error(`Setting display name for ${userId} failed: ${meta?.statuscode} ${meta?.message}`)
	}
}
