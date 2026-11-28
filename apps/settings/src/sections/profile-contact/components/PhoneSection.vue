<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { CountryCode } from 'libphonenumber-js'
import type { IAccountProperty } from '../../../constants/AccountPropertyConstants.ts'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { isValidPhoneNumber } from 'libphonenumber-js'
import { ref } from 'vue'
import AccountPropertySection from './shared/AccountPropertySection.vue'
import { NAME_READABLE_ENUM } from '../../../constants/AccountPropertyConstants.ts'

const {
	defaultPhoneRegion,
	phone,
} = loadState<{ phone: IAccountProperty, defaultPhoneRegion?: CountryCode }>('settings', 'personalInfoParameters')
const property = ref({ ...phone, readable: NAME_READABLE_ENUM[phone.name] })

/**
 * @param value The entered phone number
 */
function onValidate(value: string): boolean {
	if (value === '') {
		return true
	}

	if (defaultPhoneRegion) {
		return isValidPhoneNumber(value, defaultPhoneRegion)
	}
	return isValidPhoneNumber(value)
}
</script>

<template>
	<AccountPropertySection
		v-bind="property"
		v-model:value="property.value"
		v-model:scope="property.scope"
		:placeholder="t('settings', 'Your phone number')"
		autocomplete="tel"
		type="tel"
		:onValidate="onValidate" />
</template>
