/*!
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 */

import type { IHandler } from './index.ts'
import type { Viewer } from './viewer.ts'

declare global {
	interface Window {
		/**
		 * Registered viewer handlers.
		 */
		_oca_viewer_handlers: Map<string, IHandler>

		/**
		 * The viewer global instance.
		 */
		_oca_viewer_service: Viewer
	}
}
