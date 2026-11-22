/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { loadState } from '@nextcloud/initial-state'

/**
 * Check if the comments app is using the Activity app integration for the sidebar.
 */
export function isUsingActivityIntegration() {
	return loadState('comments', 'activityEnabled', false) && window.OCA?.Activity?.registerSidebarAction !== undefined
}
