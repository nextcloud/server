<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AccountPropertySection v-bind.sync="twitter"
		:placeholder="t('settings', 'Your X (formerly Twitter) handle')" />
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import AccountPropertySection from './shared/AccountPropertySection.vue'

import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'

const { twitter } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'TwitterSection',

	components: {
		AccountPropertySection,
	},

	data() {
		return {
			twitter: { ...twitter, readable: NAME_READABLE_ENUM[twitter.name] },
		}
	},
}
</script>
