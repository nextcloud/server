/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export interface SettingData {
	el: () => HTMLElement
	open?: () => void
	close?: () => void
	order?: number
}

export default class Setting {
	#name: string
	#options: Required<SettingData>

	/**
	 * Create a new files app setting
	 *
	 * @param name - The name of this setting
	 * @param options - The setting options
	 * @param options.el - Function that returns an unmounted dom element to be added
	 * @param options.open - Callback for when setting is added
	 * @param options.close - Callback for when setting is closed
	 * @param options.order - The order of this setting, lower numbers are shown first
	 * @since 19.0.0
	 */
	constructor(name: string, options: SettingData) {
		this.#name = name
		this.#options = {
			open: () => {},
			close: () => {},
			order: 0,
			...options,
		}

		if (typeof this.#options.el !== 'function') {
			throw new Error('Setting must have an `el` function that returns a DOM element')
		}

		if (typeof this.#name !== 'string') {
			throw new Error('Setting must have a `name` string')
		}

		if (typeof this.#options.order !== 'number') {
			throw new Error('Setting must have an `order` number')
		}
	}

	get name() {
		return this.#name
	}

	get el() {
		return this.#options.el
	}

	get open() {
		return this.#options.open
	}

	get close() {
		return this.#options.close
	}

	get order() {
		return this.#options.order
	}
}
