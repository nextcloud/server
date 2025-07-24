<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AccountPropertySection v-bind.sync="value"
		:readable="readable"
		:on-validate="onValidate"
		:placeholder="t('settings', 'Bluesky handle')" />
</template>

<script setup lang="ts">
import type { AccountProperties } from '../../constants/AccountPropertyConstants.js'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.ts'
import AccountPropertySection from './shared/AccountPropertySection.vue'

const { bsky } = loadState<AccountProperties>('settings', 'personalInfoParameters')

const value = ref({ ...bsky })
const readable = NAME_READABLE_ENUM[bsky.name]

/**
 * Validate that the text might be a twitter handle
 * @param text The potential twitter handle
 */
function onValidate(text: string): boolean {
	return text === '' || text.match(/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/) !== null
}
</script>
