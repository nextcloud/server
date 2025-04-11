/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

const styleRefreshFields = [
	'color',
	'logo',
	'background',
	'logoheader',
	'favicon',
	'disable-user-theming',
]

export default {
	emits: [
		'update:theming',
	],

	data() {
		return {
			showSuccess: false,
			errorMessage: '',
		}
	},

	computed: {
		id() {
			return `admin-theming-${this.name}`
		},
	},

	methods: {
		reset() {
			this.showSuccess = false
			this.errorMessage = ''
		},

		handleSuccess() {
			this.showSuccess = true
			setTimeout(() => { this.showSuccess = false }, 2000)
			if (styleRefreshFields.includes(this.name)) {
				this.$emit('update:theming')
			}
		},
	},
}
