<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAccountProperty } from '../../../constants/AccountPropertyConstants.ts'

import { emit } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import AccountPropertySection from './shared/AccountPropertySection.vue'
import { NAME_READABLE_ENUM } from '../../../constants/AccountPropertyConstants.ts'

const { displayName } = loadState<{ displayName: IAccountProperty }>('settings', 'personalInfoParameters')
const { displayNameChangeSupported } = loadState<{ displayNameChangeSupported: boolean }>('settings', 'accountParameters')
const property = ref({ ...displayName, readable: NAME_READABLE_ENUM[displayName.name] })

/**
 * @param value The entered display name
 */
function onValidate(value: string): boolean {
	return value !== ''
}

/**
 * @param value The saved display name
 */
function onSave(value: string) {
	if (window.oc_userconfig.avatar.generated) {
		// Update the avatar version so that avatar update handlers refresh correctly
		window.oc_userconfig.avatar.version = Date.now()
	}
	emit('settings:display-name:updated', value)
}
</script>

<template>
	<AccountPropertySection
		v-bind="property"
		v-model:value="property.value"
		v-model:scope="property.scope"
		:placeholder="t('settings', 'Your full name')"
		autocomplete="username"
		:disabled="!displayNameChangeSupported"
		:onValidate="onValidate"
		:onSave="onSave" />
</template>
