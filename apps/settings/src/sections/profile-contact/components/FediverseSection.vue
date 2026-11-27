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

const { fediverse } = loadState<{ fediverse: IAccountProperty }>('settings', 'personalInfoParameters')

const property = ref({ ...fediverse, readable: NAME_READABLE_ENUM[fediverse.name] })

/**
 * Validate a fediverse handle
 *
 * @param text The potential fediverse handle
 */
function onValidate(text: string): boolean {
	// allow to clear the value
	if (text === '') {
		return true
	}

	// check its in valid format
	const result = text.match(/^@?([^@/]+)@([^@/]+)$/)
	if (result === null) {
		return false
	}

	// check its a valid URL
	try {
		return URL.parse(`https://${result[2]}/`) !== null
	} catch {
		return false
	}
}
</script>

<template>
	<AccountPropertySection
		v-bind="property"
		v-model:value="property.value"
		v-model:scope="property.scope"
		:onValidate="onValidate"
		:placeholder="t('settings', 'Your handle')" />
</template>
