/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const valueMixin = {
	props: {
		value: {
			type: String,
			default: '',
		},
		check: {
			type: Object,
			default: () => { return {} },
		},
	},
	data() {
		return {
			newValue: '',
		}
	},
	watch: {
		value: {
			immediate: true,
			handler(value) {
				this.updateInternalValue(value)
			},
		},
	},
	methods: {
		updateInternalValue(value) {
			this.newValue = value
		},
	},
}

export default valueMixin
