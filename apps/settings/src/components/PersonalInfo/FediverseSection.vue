<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AccountPropertySection v-bind.sync="fediverse"
		:placeholder="t('settings', 'Your handle')" />
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import AccountPropertySection from './shared/AccountPropertySection.vue'

import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'

const { fediverse } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'FediverseSection',

	components: {
		AccountPropertySection,
	},

	data() {
		return {
			fediverse: { ...fediverse, readable: NAME_READABLE_ENUM[fediverse.name] },
		}
	},
}
</script>
