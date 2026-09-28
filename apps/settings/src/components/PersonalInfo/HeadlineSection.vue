<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AccountPropertySection v-bind.sync="headline"
		:placeholder="t('settings', 'Your headline')" />
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import AccountPropertySection from './shared/AccountPropertySection.vue'

import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'

const { headline } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'HeadlineSection',

	components: {
		AccountPropertySection,
	},

	data() {
		return {
			headline: { ...headline, readable: NAME_READABLE_ENUM[headline.name] },
		}
	},
}
</script>
