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

/**
 * Currently known types of app discover section elements
 */
export const APP_DISCOVER_KNOWN_TYPES = ['post', 'showcase', 'carousel'] as const

/**
 * Helper for localized values
 */
export type ILocalizedValue<T> = Record<string, T | undefined> & { en: T }

export interface IAppDiscoverElement {
	/**
	 * Type of the element
	 */
	type: typeof APP_DISCOVER_KNOWN_TYPES[number]

	/**
	 * Identifier for this element
	 */
	id: string,

	/**
	 * Order of this element to pin elements (smaller = shown on top)
	 */
	order?: number

	/**
	 * Optional, localized, headline for the element
	 */
	headline?: ILocalizedValue<string>

	/**
	 * Optional link target for the element
	 */
	link?: string

	/**
	 * Optional date when this element will get valid (only show since then)
	 */
	date?: number

	/**
	 * Optional date when this element will be invalid (only show until then)
	 */
	expiryDate?: number
}

/** Wrapper for media source and MIME type */
type MediaSource = { src: string, mime: string }

/**
 * Media content type for posts
 */
interface IAppDiscoverMediaContent {
	/**
	 * The media source to show - either one or a list of sources with their MIME type for fallback options
	 */
	src: MediaSource | MediaSource[]

	/**
	 * Alternative text for the media
	 */
	alt: string

	/**
	 * Optional link target for the media (e.g. to the full video)
	 */
	link?: string
}

/**
 * Wrapper for post media
 */
interface IAppDiscoverMedia {
	/**
	 * The alignment of the media element
	 */
	alignment?: 'start' | 'end' | 'center'

	/**
	 * The (localized) content
	 */
	content: ILocalizedValue<IAppDiscoverMediaContent>
}

/**
 * An app element only used for the showcase type
 */
export interface IAppDiscoverApp {
	/** The App ID */
	type: 'app'
	appId: string
}

export interface IAppDiscoverPost extends IAppDiscoverElement {
	type: 'post'
	text?: ILocalizedValue<string>
	media?: IAppDiscoverMedia
}

export interface IAppDiscoverShowcase extends IAppDiscoverElement {
	type: 'showcase'
	content: (IAppDiscoverPost | IAppDiscoverApp)[]
}

export interface IAppDiscoverCarousel extends IAppDiscoverElement {
	type: 'carousel'
	text?: ILocalizedValue<string>
	content: IAppDiscoverPost[]
}

export type IAppDiscoverElements = IAppDiscoverPost | IAppDiscoverCarousel | IAppDiscoverShowcase
