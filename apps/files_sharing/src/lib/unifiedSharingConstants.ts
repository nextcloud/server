/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Hardcoded backend class strings from the unified sharing API, mirrored from
 * the dialog library's constants. They are validated against the server
 * capabilities at runtime before use (see `unifiedShares.ts`).
 */

/** Node (file/folder) source type. */
export const SOURCE_TYPE_NODE = 'OCA\\Files\\Sharing\\Source\\NodeShareSourceType'

/** Recipient type classes. */
export const RECIPIENT_TYPE_USER = 'OC\\Core\\Sharing\\Recipient\\UserShareRecipientType'
export const RECIPIENT_TYPE_EMAIL = 'OC\\Core\\Sharing\\Recipient\\EmailShareRecipientType'
export const RECIPIENT_TYPE_GROUP = 'OC\\Core\\Sharing\\Recipient\\GroupShareRecipientType'
export const RECIPIENT_TYPE_TEAM = 'OC\\Core\\Sharing\\Recipient\\TeamShareRecipientType'
export const RECIPIENT_TYPE_TOKEN = 'OC\\Core\\Sharing\\Recipient\\TokenShareRecipientType'
