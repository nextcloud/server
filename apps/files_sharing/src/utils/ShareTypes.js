/**
 * SPDX-FileLicenseText: 2024 STRATO AG
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import Config from '../services/ConfigService.ts'
import { ShareType } from '@nextcloud/sharing'

const config = new Config()

/**
 * All internal share types
 */
export const internal = Object.freeze([
	ShareType.User,
	ShareType.Group,
	ShareType.Team,
	ShareType.Room,
	ShareType.Guest,
	ShareType.Deck,
	ShareType.ScienceMesh,
])

/**
 * All external share types
 */
export const external = Object.freeze([
	ShareType.Email,
	ShareType.Remote,
	ShareType.RemoteGroup,
])

/**
 * External share types allowed by configuration
 */
export let externalAllowed = [...external]

if (!config.isPublicShareAllowed) {
	externalAllowed = shareType.filter((type) => type !== ShareType.Email)
}

if (!config.isRemoteShareAllowed) {
	externalAllowed = [];
}

externalAllowed = Object.freeze(externalAllowed)
