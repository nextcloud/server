<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AccountPropertySection v-bind.sync="value"
		:readable="readable"
		:on-validate="onValidate"
		:placeholder="t('settings', 'Your handle')" />
</template>

<script setup lang="ts">
import type { AccountProperties } from '../../constants/AccountPropertyConstants.js'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'

import AccountPropertySection from './shared/AccountPropertySection.vue'

const { fediverse } = loadState<AccountProperties>('settings', 'personalInfoParameters', {})

const value = ref({ ...fediverse })
const readable = NAME_READABLE_ENUM[fediverse.name]

/**
 * Validate a fediverse handle
 * @param text The potential fediverse handle
 */
function onValidate(text: string): boolean {
	const result = text.match(/^@?([^@/]+)@([^@/]+)$/)
	if (result === null) {
		return false
	}

	try {
		return URL.parse(`https://${result[2]}/`) !== null
	} catch {
		return false
	}
}
</script>
