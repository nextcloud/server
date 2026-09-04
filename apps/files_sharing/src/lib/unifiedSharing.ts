/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { SharingPermission, SharingRecipient, SharingShare } from '../types/unifiedSharing.ts'

import { getCapabilities } from '@nextcloud/capabilities'
import { translatePlural as n, translate as t } from '@nextcloud/l10n'
import {
	RECIPIENT_TYPE_EMAIL,
	RECIPIENT_TYPE_GROUP,
	RECIPIENT_TYPE_TEAM,
	RECIPIENT_TYPE_TOKEN,
	RECIPIENT_TYPE_USER,
} from './unifiedSharingConstants.ts'

/**
 * Rank a share by its highest granted permissions: the sum of priorities of its
 * enabled permissions. Used to order the flat share list (highest first).
 *
 * @param share The share to rank
 */
export function sharePermissionRank(share: SharingShare): number {
	return share.permissions
		.filter((permission) => permission.enabled)
		.reduce((sum, permission) => sum + permission.priority, 0)
}

/**
 * Sort shares by highest permissions first, then by recipient count, then by id
 * for a stable order.
 *
 * @param shares The shares to sort (not mutated)
 */
export function sortSharesByPermission(shares: SharingShare[]): SharingShare[] {
	return [...shares].sort((a, b) => {
		const rank = sharePermissionRank(b) - sharePermissionRank(a)
		if (rank !== 0) {
			return rank
		}
		const count = b.recipients.length - a.recipients.length
		if (count !== 0) {
			return count
		}
		return a.id.localeCompare(b.id)
	})
}

type CapabilitiesWithPresets = {
	sharing?: {
		permission_presets?: { class: string, display_name: string }[]
	}
}

/**
 * Human-readable label for a share's permission preset, e.g. "Can edit". Falls
 * back to "Custom permissions" when the enabled permissions match no preset.
 *
 * @param share The share
 */
export function permissionLabel(share: SharingShare): string {
	if (share.permission_preset === null) {
		return t('files_sharing', 'Custom permissions')
	}
	const capabilities = getCapabilities() as CapabilitiesWithPresets
	const preset = (capabilities.sharing?.permission_presets ?? []).find((p) => p.class === share.permission_preset)
	return preset?.display_name ?? t('files_sharing', 'Custom permissions')
}

/**
 * Human-readable label for a set of permissions: the preset whose member
 * permissions are exactly the enabled ones, else "Custom permissions".
 *
 * @param permissions The permissions to label
 */
function labelForPermissions(permissions: SharingPermission[]): string {
	const capabilities = getCapabilities() as CapabilitiesWithPresets
	const enabled = new Set(permissions.filter((permission) => permission.enabled).map((permission) => permission.class))
	for (const preset of capabilities.sharing?.permission_presets ?? []) {
		const members = permissions.filter((permission) => permission.presets.includes(preset.class))
		if (members.length > 0 && members.length === enabled.size && members.every((permission) => enabled.has(permission.class))) {
			return preset.display_name
		}
	}
	return t('files_sharing', 'Custom permissions')
}

/**
 * Human-readable permission label for a single recipient.
 *
 * A recipient's permissions are sparse overrides on top of the share's, so the
 * effective state is the share's permissions with the recipient's applied.
 *
 * @param share The share the recipient belongs to
 * @param recipient The recipient
 */
export function recipientPermissionLabel(share: SharingShare, recipient: SharingRecipient): string {
	const overrides = new Map((recipient.permissions ?? []).map((permission) => [permission.class, permission]))
	return labelForPermissions(share.permissions.map((permission) => ({
		...permission,
		enabled: overrides.get(permission.class)?.enabled ?? permission.enabled,
	})))
}

/**
 * Whether a recipient should render a non-user (initials) avatar.
 *
 * @param recipient The recipient
 */
export function isNoUserRecipient(recipient: SharingRecipient): boolean {
	return recipient.class !== RECIPIENT_TYPE_USER
}

/**
 * Build a human-readable summary of a share's recipients, e.g.
 * "1 person, 2 groups". Categories are listed in a stable order and only
 * non-empty ones are included.
 *
 * @param recipients The share's recipients
 */
export function recipientSummary(recipients: SharingRecipient[]): string {
	const counts: Record<string, number> = {}
	for (const recipient of recipients) {
		counts[recipient.class] = (counts[recipient.class] ?? 0) + 1
	}

	const parts: string[] = []
	const push = (count: number, singular: string, plural: string) => {
		if (count > 0) {
			parts.push(n('files_sharing', singular, plural, count))
		}
	}

	push(counts[RECIPIENT_TYPE_USER] ?? 0, '%n person', '%n people')
	push(counts[RECIPIENT_TYPE_GROUP] ?? 0, '%n group', '%n groups')
	push(counts[RECIPIENT_TYPE_TEAM] ?? 0, '%n team', '%n teams')
	push(counts[RECIPIENT_TYPE_EMAIL] ?? 0, '%n email', '%n emails')
	push(counts[RECIPIENT_TYPE_TOKEN] ?? 0, '%n link', '%n links')

	// Fallback for any unknown recipient class not covered above.
	const known = new Set([
		RECIPIENT_TYPE_USER,
		RECIPIENT_TYPE_GROUP,
		RECIPIENT_TYPE_TEAM,
		RECIPIENT_TYPE_EMAIL,
		RECIPIENT_TYPE_TOKEN,
	])
	const otherCount = recipients.filter((r) => !known.has(r.class)).length
	push(otherCount, '%n recipient', '%n recipients')

	return parts.join(t('files_sharing', ', '))
}

/**
 * Best-effort "Reshared with N people" subtitle: counts recipients that were
 * added by someone other than the share owner (i.e. via a reshare). Returns an
 * empty string when there are none.
 *
 * @param share The share
 */
export function reshareSubtitle(share: SharingShare): string {
	const reshared = share.recipients.filter((recipient) => recipient.initiator !== null && recipient.initiator.user_id !== share.owner.user_id).length
	if (reshared === 0) {
		return ''
	}
	return n('files_sharing', 'Reshared with %n person', 'Reshared with %n people', reshared)
}
