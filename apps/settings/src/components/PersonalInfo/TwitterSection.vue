<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AccountPropertySection v-bind.sync="value"
		:readable="readable"
		:on-validate="onValidate"
		:placeholder="t('settings', 'Your X (formerly Twitter) handle')" />
</template>

<script setup lang="ts">
import type { AccountProperties } from '../../constants/AccountPropertyConstants.js'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.ts'
import AccountPropertySection from './shared/AccountPropertySection.vue'

const { twitter } = loadState<AccountProperties>('settings', 'personalInfoParameters')

const value = ref({ ...twitter })
const readable = NAME_READABLE_ENUM[twitter.name]

/**
 * Validate that the text might be a twitter handle
 * @param text The potential twitter handle
 */
function onValidate(text: string): boolean {
	return text === '' || text.match(/^@?([a-zA-Z0-9_]{2,15})$/) !== null
}
</script>
