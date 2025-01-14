<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AccountPropertySection v-bind.sync="biography"
		:placeholder="t('settings', 'Your biography. Markdown is supported.')"
		:multi-line="true" />
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import AccountPropertySection from './shared/AccountPropertySection.vue'

import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'

const { biography } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'BiographySection',

	components: {
		AccountPropertySection,
	},

	data() {
		return {
			biography: { ...biography, readable: NAME_READABLE_ENUM[biography.name] },
		}
	},
}
</script>
