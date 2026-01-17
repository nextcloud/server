/**
 * SPDX-FileCopyrightText: Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { logger } from './logger.ts'

export interface IProfileSection {
	/**
	 * Unique identifier for the section
	 */
	id: string
	/**
	 * The order in which the section should appear
	 */
	order: number
	/**
	 * The custom element tag name to be used for this section
	 *
	 * The custom element must have been registered beforehand,
	 * and must have the a `user` property of type `string | undefined`.
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/API/Web_components
	 */
	tagName: string
	/**
	 * Static parameters to be passed to the custom web component
	 */
	params?: Record<string, unknown>
}

export default class ProfileSections {
	#sections: Map<string, IProfileSection>

	constructor() {
		this.#sections = new Map()
	}

	/**
	 * @param section To be called to mount the section to the profile page
	 */
	registerSection(section: IProfileSection) {
		if (this.#sections.has(section.id)) {
			logger.warn(`Profile section with id '${section.id}' is already registered.`)
		}
		this.#sections.set(section.id, section)
	}

	getSections() {
		return [...this.#sections.values()]
	}
}
