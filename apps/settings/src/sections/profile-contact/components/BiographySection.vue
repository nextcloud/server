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

const { biography } = loadState<{ biography: IAccountProperty }>('settings', 'personalInfoParameters')
const property = ref({ ...biography, readable: NAME_READABLE_ENUM[biography.name] })
</script>

<template>
	<AccountPropertySection
		v-bind="property"
		v-model:value="property.value"
		v-model:scope="property.scope"
		multiLine
		:placeholder="t('settings', 'Your biography. Markdown is supported.')" />
</template>
