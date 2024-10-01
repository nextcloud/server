/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { IAppDiscoverCarousel, IAppDiscoverElement, IAppDiscoverElements, IAppDiscoverPost, IAppDiscoverShowcase } from '../constants/AppDiscoverTypes.ts'

/**
 * Helper to transform the JSON API results to proper frontend objects (app discover section elements)
 *
 * @param element The JSON API element to transform
 */
export const parseApiResponse = (element: Record<string, unknown>): IAppDiscoverElements => {
	const appElement = { ...element }
	if (appElement.date) {
		appElement.date = Date.parse(appElement.date as string)
	}
	if (appElement.expiryDate) {
		appElement.expiryDate = Date.parse(appElement.expiryDate as string)
	}

	if (appElement.type === 'post') {
		return appElement as unknown as IAppDiscoverPost
	} else if (appElement.type === 'showcase') {
		return appElement as unknown as IAppDiscoverShowcase
	} else if (appElement.type === 'carousel') {
		return appElement as unknown as IAppDiscoverCarousel
	}
	throw new Error(`Invalid argument, app discover element with type ${element.type ?? 'unknown'} is unknown`)
}

/**
 * Filter outdated or upcoming elements
 * @param element Element to check
 */
export const filterElements = (element: IAppDiscoverElement) => {
	const now = Date.now()
	// Element not yet published
	if (element.date && element.date > now) {
		return false
	}

	// Element expired
	if (element.expiryDate && element.expiryDate < now) {
		return false
	}
	return true
}
