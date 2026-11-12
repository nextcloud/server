<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAccountProperty } from '../../../constants/AccountPropertyConstants.ts'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import AccountPropertySection from './shared/AccountPropertySection.vue'
import { NAME_READABLE_ENUM } from '../../../constants/AccountPropertyConstants.ts'

const { pronouns } = loadState<{ pronouns: IAccountProperty }>('settings', 'personalInfoParameters')
const property = ref({ ...pronouns, readable: NAME_READABLE_ENUM[pronouns.name] })

const examples = [
	t('settings', 'she/her'),
	t('settings', 'he/him'),
	t('settings', 'they/them'),
]
const pronounsExample = examples[Math.floor(Math.random() * examples.length)]
const randomPronounsPlaceholder = t('settings', 'Your pronouns. E.g. {pronounsExample}', { pronounsExample })
</script>

<template>
	<AccountPropertySection
		v-bind="property"
		v-model:value="property.value"
		v-model:scope="property.scope"
		:placeholder="randomPronounsPlaceholder" />
</template>
