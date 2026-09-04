/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { getCSPNonce } from '@nextcloud/auth'
import { exposeSidebarMount, initializeSidebar } from './sidebar/setup.ts'

__webpack_nonce__ = getCSPNonce()

// apps can render the sidebar within their own layout at any time
exposeSidebarMount()

// the files app registers its data provider while loading, so wait for all scripts to be executed
if (document.readyState === 'loading') {
	window.addEventListener('DOMContentLoaded', initializeSidebar)
} else {
	initializeSidebar()
}
