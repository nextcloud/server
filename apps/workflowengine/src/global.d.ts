/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { CheckPlugin, OperatorPlugin } from './types.ts'

declare global {
	interface Window {
		OCA: {
			WorkflowEngine: {
				registerCheck(plugin: CheckPlugin): void
				registerOperator(plugin: OperatorPlugin): void
			}
		}
	}
}

export {}
