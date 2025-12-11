/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import type { Ref } from 'vue'
import type { IAppstoreApp } from '../app-types.ts'

import { mdiCog, mdiCogOutline } from '@mdi/js'
import { computed, ref, watchEffect } from 'vue'
import AppstoreCategoryIcons from '../constants/AppstoreCategoryIcons.ts'
import logger from '../utils/logger.ts'

/**
 * Get the app icon raw SVG for use with `NcIconSvgWrapper` (do never use without sanitizing)
 * It has a fallback to the categroy icon.
 *
 * @param app The app to get the icon for
 */
export function useAppIcon(app: Ref<IAppstoreApp>) {
	const appIcon = ref<string | null>(null)

	/**
	 * Fallback value if no app icon available
	 */
	const categoryIcon = computed(() => {
		let path: string
		if (app.value?.app_api) {
			// Use different default icon for ExApps (AppAPI)
			path = mdiCogOutline
		} else {
			path = [app.value?.category ?? []].flat()
				.map((name) => AppstoreCategoryIcons[name])
				.filter((icon) => !!icon)
				.at(0)
				?? (!app.value?.app_api ? mdiCog : mdiCogOutline)
		}
		return path ? `<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="${path}" /></svg>` : null
	})

	watchEffect(async () => {
		// Note: Only variables until the first `await` will be watched!
		if (!app.value?.preview) {
			appIcon.value = categoryIcon.value
		} else {
			appIcon.value = null
			// Now try to load the real app icon
			try {
				const response = await window.fetch(app.value.preview)
				const blob = await response.blob()
				const rawSvg = await blob.text()
				appIcon.value = rawSvg.replaceAll(/fill="#(fff|ffffff)([a-z0-9]{1,2})?"/ig, 'fill="currentColor"')
			} catch (error) {
				appIcon.value = categoryIcon.value
				logger.error('Could not load app icon', { error })
			}
		}
	})

	return {
		appIcon,
	}
}
