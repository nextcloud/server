/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Callback to render a section in the sharing tab.
 *
 * @callback registerSectionCallback
 * @param {undefined} el - Deprecated and will always be undefined (formerly the root element)
 * @param {object} fileInfo - File info object
 */

export default class TabSections {
	_sections

	constructor() {
		this._sections = []
	}

	/**
	 * @param {registerSectionCallback} section To be called to mount the section to the sharing sidebar
	 */
	registerSection(section) {
		this._sections.push(section)
	}

	getSections() {
		return this._sections
	}
}
