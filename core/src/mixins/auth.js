/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

export default {

	computed: {
		userNameInputLengthIs255() {
			return this.user.length >= 255
		},
		userInputHelperText() {
			if (this.userNameInputLengthIs255) {
				return t('core', 'Email length is at max (255)')
			}
			return undefined
		},
	},
}
