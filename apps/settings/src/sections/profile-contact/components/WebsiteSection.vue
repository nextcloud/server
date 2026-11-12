<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAccountProperty } from '../../../constants/AccountPropertyConstants.ts'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import AccountPropertySection from './shared/AccountPropertySection.vue'
import { NAME_READABLE_ENUM } from '../../../constants/AccountPropertyConstants.ts'
import { validateUrl } from '../../../utils/validate.js'

const { website } = loadState<{ website: IAccountProperty }>('settings', 'personalInfoParameters')
const property = ref({ ...website, readable: NAME_READABLE_ENUM[website.name] })

/**
 * @param value The entered website
 */
function onValidate(value: string): boolean {
	return validateUrl(value) || value === ''
}
</script>

<template>
	<AccountPropertySection
		v-bind="property"
		v-model:value="property.value"
		v-model:scope="property.scope"
		:placeholder="t('settings', 'Your website')"
		autocomplete="url"
		type="url"
		:onValidate="onValidate" />
</template>
