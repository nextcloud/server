/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

import FieldMixin from './FieldMixin.js'

export default {
	mixins: [
		FieldMixin,
	],

	watch: {
		value(value) {
			this.localValue = value
		},
	},

	data() {
		return {
			localValue: this.value,
		}
	},

	methods: {
		async save() {
			this.reset()
			const url = generateUrl('/apps/theming/ajax/updateStylesheet')
			// Convert boolean to string as server expects string value
			const valueToPost = this.localValue === true ? 'yes' : this.localValue === false ? 'no' : this.localValue
			try {
				await axios.post(url, {
					setting: this.name,
					value: valueToPost,
				})
				this.$emit('update:value', this.localValue)
				this.handleSuccess()
			} catch (e) {
				this.errorMessage = e.response.data.data?.message
			}
		},

		async undo() {
			this.reset()
			const url = generateUrl('/apps/theming/ajax/undoChanges')
			try {
				const { data } = await axios.post(url, {
					setting: this.name,
				})

				if (data.data.value) {
					this.$emit('update:defaultValue', data.data.value)
				}
				this.$emit('update:value', data.data.value || this.defaultValue)
				this.handleSuccess()
			} catch (e) {
				this.errorMessage = e.response.data.data?.message
			}
		},
	},
}
