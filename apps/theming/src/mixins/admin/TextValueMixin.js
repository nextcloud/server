/**
 * @copyright 2022 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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
				await axios.post(url, {
					setting: this.name,
				})
				this.$emit('update:value', this.defaultValue)
				this.handleSuccess()
			} catch (e) {
				this.errorMessage = e.response.data.data?.message
			}
		},
	},
}
