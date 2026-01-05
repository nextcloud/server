/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ISidebar } from '@nextcloud/files'

import { getPinia } from './store/index.ts'
import { useSidebarStore } from './store/sidebar.ts'

// Provide sidebar implementation which is proxied by the `@nextcloud/files` library for app usage.
window.OCA.Files ??= {}
window.OCA.Files._sidebar = () => useSidebarStore(getPinia()) satisfies Omit<ISidebar, 'available' | 'registerAction' | 'registerTab' | 'registerAction'>
