/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { createPinia } from 'pinia'

export const getPinia = () => {
	if (window._nc_files_pinia) {
		return window._nc_files_pinia
	}

	window._nc_files_pinia = createPinia()
	return window._nc_files_pinia
}
