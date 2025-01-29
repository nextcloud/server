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
			/** @type {string|boolean} */
			localValue: this.value,
		}
	},

	computed: {
		valueToPost() {
			if (this.type === 'url') {
				// if this is already encoded just make sure there is no doublequote (HTML XSS)
				// otherwise simply URL encode
				return this.isUrlEncoded(this.localValue)
					? this.localValue.replaceAll('"', '%22')
					: encodeURI(this.localValue)
			}
			// Convert boolean to string as server expects string value
			if (typeof this.localValue === 'boolean') {
				return this.localValue ? 'yes' : 'no'
			}
			return this.localValue
		},
	},

	methods: {
		/**
		 * Check if URL is percent-encoded
		 * @param {string} url The URL to check
		 * @return {boolean}
		 */
		isUrlEncoded(url) {
			try {
				return decodeURI(url) !== url
			} catch {
				return false
			}
		},

		async save() {
			this.reset()
			const url = generateUrl('/apps/theming/ajax/updateStylesheet')

			try {
				await axios.post(url, {
					setting: this.name,
					value: this.valueToPost,
				})
				this.$emit('update:value', this.localValue)
				this.handleSuccess()
			} catch (e) {
				console.error('Failed to save changes', e)
				this.errorMessage = e.response?.data.data?.message
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
