<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AccountPropertySection v-bind.sync="pronouns"
		:placeholder="randomPronounsPlaceholder" />
</template>

<script lang="ts">
import type { IAccountProperty } from '../../constants/AccountPropertyConstants.ts'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'
import AccountPropertySection from './shared/AccountPropertySection.vue'
import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.ts'

const { pronouns } = loadState<{ pronouns: IAccountProperty }>('settings', 'personalInfoParameters')

export default defineComponent({
	name: 'PronounsSection',

	components: {
		AccountPropertySection,
	},

	data() {
		return {
			pronouns: { ...pronouns, readable: NAME_READABLE_ENUM[pronouns.name] },
		}
	},

	computed: {
		randomPronounsPlaceholder() {
			const pronouns = [
				t('settings', 'she/her'),
				t('settings', 'he/him'),
				t('settings', 'they/them'),
			]
			const pronounsExample = pronouns[Math.floor(Math.random() * pronouns.length)]
			return t('settings', 'Your pronouns. E.g. {pronounsExample}', { pronounsExample })
		},
	},
})
</script>
