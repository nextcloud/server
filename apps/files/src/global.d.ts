/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { ISidebar } from '@nextcloud/files'
import type { Pinia } from 'pinia'
import type Router from './services/RouterService.ts'

declare global {
	interface Window {
		/** private pinia instance to share it between entry points (needed with Webpack) */
		_nc_files_pinia: Pinia

		OCP: {
			Files: {
				/** The files router service to allow apps to interact with the files router instance */
				Router: Router
			}
		}

		OCA: Record<string, unknown> & {
			Files?: {
				/** private implementation of the sidebar to be proxied by `@nextcloud/files` */
				_sidebar?: () => Omit<ISidebar, 'available' | 'registerTab' | 'registerAction' | 'registerAction'>
			}
		}
	}
}
