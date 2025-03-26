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
			console.error("DEBUG: watch modelValue mixin")
			this.updateInternalValue()
		},
	},
	methods: {
		updateInternalValue() {
			console.error("DEBUG: updateInternalValue filemimetype " + this.modelValue)
			this.newValue = this.modelValue
		},
	},
}

export default valueMixin
