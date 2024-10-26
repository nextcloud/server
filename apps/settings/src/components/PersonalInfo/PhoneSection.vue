<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AccountPropertySection v-bind.sync="phone"
		:placeholder="t('settings', 'Your phone number')"
		autocomplete="tel"
		type="tel"
		:on-validate="onValidate" />
</template>

<script>
import { isValidPhoneNumber } from 'libphonenumber-js'
import { loadState } from '@nextcloud/initial-state'

import AccountPropertySection from './shared/AccountPropertySection.vue'

import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'

const {
	defaultPhoneRegion,
	phone,
} = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'PhoneSection',

	components: {
		AccountPropertySection,
	},

	data() {
		return {
			phone: { ...phone, readable: NAME_READABLE_ENUM[phone.name] },
		}
	},

	methods: {
		onValidate(value) {
			if (defaultPhoneRegion) {
				return isValidPhoneNumber(value, defaultPhoneRegion)
			}
			return isValidPhoneNumber(value)
		},
	},
}
</script>
