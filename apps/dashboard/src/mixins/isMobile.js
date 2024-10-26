/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export default {
	data() {
		return {
			isMobile: this._isMobile(),
		}
	},
	beforeMount() {
		window.addEventListener('resize', this._onResize)
	},
	beforeDestroy() {
		window.removeEventListener('resize', this._onResize)
	},
	methods: {
		_onResize() {
			// Update mobile mode
			this.isMobile = this._isMobile()
		},
		_isMobile() {
			// check if content width is under 768px
			return document.documentElement.clientWidth < 768
		},
	},
}
