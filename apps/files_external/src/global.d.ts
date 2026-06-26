/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { AuthMechanism } from './services/AuthMachanism.ts'

declare global {
	interface Window {
		OCA: {
			FilesExternal: {
				AuthMechanism?: AuthMechanism
			}
		}
	}
}

export {}
