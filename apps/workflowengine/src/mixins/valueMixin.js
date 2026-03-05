/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const valueMixin = {
	data() {
		return {
			newValue: [],
		}
	},
	watch: {
		modelValue() {
			this.updateInternalValue()
		},
	},
	methods: {
		updateInternalValue() {
			this.newValue = this.modelValue
		},
	},
}

export default valueMixin
