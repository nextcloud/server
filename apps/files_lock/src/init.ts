/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { registerDavProperty } from '@nextcloud/files/dav'

registerDavProperty('nc:lock', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:lock-owner', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:lock-owner-displayname', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:lock-owner-type', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:lock-owner-editor', { nc: 'http://nextcloud.org/ns' })
registerDavProperty('nc:lock-time', { nc: 'http://nextcloud.org/ns' })
