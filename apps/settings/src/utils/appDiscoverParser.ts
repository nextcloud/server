/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
