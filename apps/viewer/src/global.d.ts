/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/// <reference types="@nextcloud/typings" />

import type Viewer from './services/Viewer.js'

declare global {
	interface Window {
		OCA: {
			Viewer: Viewer
		}
		OCP: Nextcloud.v29.OCP
	}

	const appVersion: string
}

export {}
