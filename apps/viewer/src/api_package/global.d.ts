/*!
 * SPDX-License-Identifier: AGPL-3.0-or-later
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 */

import type { IHandler } from './index.ts'

declare global {
	interface Window {
		/**
		 * Registered viewer handlers.
		 */
		// eslint-disable-next-line camelcase
		_oca_viewer_handlers: Map<string, IHandler>
	}
}
