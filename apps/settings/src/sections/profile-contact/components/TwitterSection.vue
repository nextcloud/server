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

const { twitter } = loadState<{ twitter: IAccountProperty }>('settings', 'personalInfoParameters')

const property = ref({ ...twitter, readable: NAME_READABLE_ENUM[twitter.name] })

/**
 * Validate that the text might be a twitter handle
 *
 * @param text The potential twitter handle
 */
function onValidate(text: string): boolean {
	return text === '' || text.match(/^@?([a-zA-Z0-9_]{2,15})$/) !== null
}
</script>

<template>
	<AccountPropertySection
		v-bind="property"
		v-model:value="property.value"
		v-model:scope="property.scope"
		:onValidate="onValidate"
		:placeholder="t('settings', 'Your X (formerly Twitter) handle')" />
</template>
