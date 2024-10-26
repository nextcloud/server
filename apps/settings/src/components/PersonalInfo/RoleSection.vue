<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AccountPropertySection v-bind.sync="role"
		autocomplete="organization-title"
		:placeholder="t('settings', 'Your role')" />
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import AccountPropertySection from './shared/AccountPropertySection.vue'

import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'

const { role } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'RoleSection',

	components: {
		AccountPropertySection,
	},

	data() {
		return {
			role: { ...role, readable: NAME_READABLE_ENUM[role.name] },
		}
	},
}
</script>
