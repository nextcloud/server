<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AccountPropertySection v-bind.sync="location"
		autocomplete="address-level1"
		:placeholder="t('settings', 'Your city')" />
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import AccountPropertySection from './shared/AccountPropertySection.vue'

import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'

const { location } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'LocationSection',

	components: {
		AccountPropertySection,
	},

	data() {
		return {
			location: { ...location, readable: NAME_READABLE_ENUM[location.name] },
		}
	},
}
</script>
