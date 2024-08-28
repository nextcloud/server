/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Navigation } from '@nextcloud/files'

declare module 'vue/types/vue' {
	interface Vue {
		$navigation: Navigation
	}
}
