<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AccountPropertySection v-bind.sync="pronouns"
		:placeholder="randomPronounsPlaceholder" />
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import AccountPropertySection from './shared/AccountPropertySection.vue'

import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'

const { pronouns } = loadState('settings', 'personalInfoParameters', {})

export default {
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
				this.t('settings', 'she/her'),
				this.t('settings', 'he/him'),
				this.t('settings', 'they/them'),
			]
			const pronounsExample = pronouns[Math.floor(Math.random() * pronouns.length)]
			return this.t('settings', `Your pronouns. E.g. ${pronounsExample}`, { pronounsExample })
		},
	},
}
</script>
