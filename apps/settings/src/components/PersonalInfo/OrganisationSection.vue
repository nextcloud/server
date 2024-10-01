<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AccountPropertySection v-bind.sync="organisation"
		autocomplete="organization"
		:placeholder="t('settings', 'Your organisation')" />
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import AccountPropertySection from './shared/AccountPropertySection.vue'

import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'

const { organisation } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'OrganisationSection',

	components: {
		AccountPropertySection,
	},

	data() {
		return {
			organisation: { ...organisation, readable: NAME_READABLE_ENUM[organisation.name] },
		}
	},
}
</script>
