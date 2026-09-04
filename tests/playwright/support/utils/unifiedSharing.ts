/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { APIRequestContext } from '@playwright/test'

import { runOcc } from '@nextcloud/e2e-test-server/docker'

/**
 * Backend class strings of the unified sharing API. They are hardcoded here the
 * same way the frontend hardcodes them, and are asserted against the
 * capabilities by {@link expectUnifiedSharingRegistered} so a rename on the
 * backend fails loudly instead of silently seeding nothing.
 */
export const SOURCE_TYPE_NODE = 'OCA\\Files\\Sharing\\Source\\NodeShareSourceType'

export const RECIPIENT_TYPE_USER = 'OC\\Core\\Sharing\\Recipient\\UserShareRecipientType'
export const RECIPIENT_TYPE_GROUP = 'OC\\Core\\Sharing\\Recipient\\GroupShareRecipientType'
export const RECIPIENT_TYPE_TOKEN = 'OC\\Core\\Sharing\\Recipient\\TokenShareRecipientType'

export const PRESET_VIEW = 'OC\\Core\\Sharing\\Permission\\ViewSharePermissionPreset'
export const PRESET_EDIT = 'OC\\Core\\Sharing\\Permission\\EditSharePermissionPreset'

export const PERMISSION_UPDATE = 'OCA\\Files\\Sharing\\Permission\\NodeUpdateSharePermissionType'

const API_BASE = '/ocs/v2.php/apps/sharing/api/v1'

/** A share as returned by the unified sharing API. */
export interface UnifiedShare {
	id: string
	state: 'active' | 'draft' | 'deleted'
	recipients: { class: string, value: string, display_name: string }[]
	permissions: { class: string, enabled: boolean }[]
	permission_preset: string | null
}

/**
 * Call the unified sharing API and unwrap the OCS envelope. OCS answers HTTP 200
 * for failures too, so the real status comes from `ocs.meta`.
 *
 * @param request A request context authenticated as the acting user
 * @param method The HTTP verb
 * @param path The path below the API base, e.g. `/share`
 * @param form Form parameters to send
 */
async function ocs<T>(
	request: APIRequestContext,
	method: 'get' | 'post' | 'put' | 'delete',
	path: string,
	form: Record<string, string | boolean> = {},
): Promise<T> {
	const response = await request[method](`${API_BASE}${path}?format=json`, {
		headers: { 'OCS-APIRequest': 'true' },
		...(method === 'get' ? {} : { form }),
	})
	const { ocs: envelope } = await response.json()
	const status = envelope?.meta?.statuscode
	// Creating a share answers 201, everything else 200.
	if (status !== 200 && status !== 201) {
		throw new Error(`${method.toUpperCase()} ${path} failed: ${status} ${envelope?.meta?.message}`)
	}
	return envelope.data as T
}

/**
 * Turn the unified sharing API on for the whole instance and return a restore
 * function. It ships disabled, so every unified spec needs this.
 *
 * The rate limiter is deliberately left on: these specs drive the dialog the
 * way a person does, so they are also the check that the API's limits are
 * livable.
 *
 * The switch is instance-wide, so specs using this must not run next to specs
 * that expect the legacy sidebar. See the serial `sharing` Playwright project.
 */
export async function enableUnifiedSharing(): Promise<() => Promise<void>> {
	await runOcc(['config:system:set', 'sharing.unified_api_enable', '--value', 'true', '--type', 'boolean'])
	return async () => {
		await runOcc(['config:system:delete', 'sharing.unified_api_enable'], { failOnError: false })
	}
}

/**
 * Turn the unified sharing API off for the whole instance, so the sidebar falls
 * back to the previous share editor, and return a restore function.
 */
export async function disableUnifiedSharing(): Promise<() => Promise<void>> {
	await runOcc(['config:system:set', 'sharing.unified_api_enable', '--value', 'false', '--type', 'boolean'])
	return async () => {
		await runOcc(['config:system:delete', 'sharing.unified_api_enable'], { failOnError: false })
	}
}

/**
 * Assert the class strings this file hardcodes are actually registered on the
 * server, so a backend rename surfaces as a clear failure.
 *
 * @param request A request context authenticated as any user
 */
export async function expectUnifiedSharingRegistered(request: APIRequestContext): Promise<void> {
	const response = await request.get('/ocs/v2.php/cloud/capabilities?format=json', {
		headers: { 'OCS-APIRequest': 'true' },
	})
	const { sharing } = (await response.json()).ocs.data.capabilities
	const missing: string[] = []
	if (!sharing?.api_versions?.length) {
		missing.push('sharing.api_versions (is sharing.unified_api_enable set?)')
	}
	if (!sharing?.source_types?.some((type: { class: string }) => type.class === SOURCE_TYPE_NODE)) {
		missing.push(SOURCE_TYPE_NODE)
	}
	// Recipient types are not advertised, but the presets are, and the specs
	// assert their labels.
	for (const preset of [PRESET_VIEW, PRESET_EDIT]) {
		if (!sharing?.permission_presets?.some((type: { class: string }) => type.class === preset)) {
			missing.push(preset)
		}
	}
	if (missing.length > 0) {
		throw new Error(`Unified sharing is not usable, missing: ${missing.join(', ')}`)
	}
}

/** Options for {@link createUnifiedShare}. */
export interface CreateUnifiedShareOptions {
	/** The file id of the node to share (returned by `mkdir`/`uploadContent`). */
	fileId: string
	/** Recipients to add, as `[class, value]` pairs. */
	recipients?: [string, string][]
	/** The permission preset to apply, e.g. {@link PRESET_EDIT}. */
	preset?: string
	/** Whether to activate the share; a draft is not listed in the sidebar. */
	activate?: boolean
}

/**
 * Seed a share through the unified sharing API: create the draft, attach the
 * node, add the recipients, apply a preset and activate it.
 *
 * Legacy shares (the `files_sharing` OCS API) are invisible to this API until a
 * legacy backend is registered, so unified specs have to seed through here.
 *
 * @param request A request context authenticated as the share owner
 * @param options The node, recipients, preset and state to seed
 */
export async function createUnifiedShare(
	request: APIRequestContext,
	options: CreateUnifiedShareOptions,
): Promise<UnifiedShare> {
	const { fileId, recipients = [], preset, activate = true } = options

	let share = await ocs<UnifiedShare>(request, 'post', '/share')
	await ocs(request, 'post', `/share/${share.id}/source`, { class: SOURCE_TYPE_NODE, value: fileId })
	for (const [recipientClass, value] of recipients) {
		await ocs(request, 'post', `/share/${share.id}/recipient`, { class: recipientClass, value })
	}
	if (preset !== undefined) {
		await ocs(request, 'put', `/share/${share.id}/permission/preset`, { permissionPresetClass: preset })
	}
	if (activate) {
		share = await ocs<UnifiedShare>(request, 'put', `/share/${share.id}/state`, { state: 'active' })
	}
	return share
}

/**
 * List the unified shares of a node, as the sidebar does.
 *
 * @param request A request context authenticated as the share owner
 * @param fileId The file id of the shared node
 */
export async function getUnifiedShares(request: APIRequestContext, fileId: string): Promise<UnifiedShare[]> {
	const response = await request.get(
		`${API_BASE}/shares?format=json&filterSourceTypeClass=${encodeURIComponent(SOURCE_TYPE_NODE)}`
		+ `&filterSourceTypeValue=${fileId}&filterState=active`,
		{ headers: { 'OCS-APIRequest': 'true' } },
	)
	return (await response.json()).ocs.data as UnifiedShare[]
}

/**
 * Set the share's permission preset, which is the default and the cap for its
 * recipients.
 *
 * @param request A request context authenticated as the share owner
 * @param shareId The share id
 * @param preset The preset class to apply
 */
export async function setSharePreset(request: APIRequestContext, shareId: string, preset: string): Promise<void> {
	await ocs(request, 'put', `/share/${shareId}/permission/preset`, { permissionPresetClass: preset })
}

/**
 * Set a single permission of one recipient, the way the per-recipient menu does.
 *
 * @param request A request context authenticated as the share owner
 * @param shareId The share id
 * @param recipient The recipient as a `[class, value]` pair
 * @param permissionClass The permission to change
 * @param enabled The new state
 */
export async function setRecipientPermission(
	request: APIRequestContext,
	shareId: string,
	recipient: [string, string],
	permissionClass: string,
	enabled: boolean,
): Promise<void> {
	await ocs(request, 'put', `/share/${shareId}/recipient/permission`, {
		recipientClass: recipient[0],
		recipientValue: recipient[1],
		permissionClass,
		enabled,
	})
}
