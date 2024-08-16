<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<AccountPropertySection v-bind.sync="displayName"
		:placeholder="t('settings', 'Your full name')"
		autocomplete="username"
		:is-editable="displayNameChangeSupported"
		:on-validate="onValidate"
		:on-save="onSave" />
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { emit } from '@nextcloud/event-bus'

import AccountPropertySection from './shared/AccountPropertySection.vue'

import { NAME_READABLE_ENUM } from '../../constants/AccountPropertyConstants.js'

const { displayName } = loadState('settings', 'personalInfoParameters', {})
const { displayNameChangeSupported } = loadState('settings', 'accountParameters', {})

export default {
	name: 'DisplayNameSection',

	components: {
		AccountPropertySection,
	},

	data() {
		return {
			displayName: { ...displayName, readable: NAME_READABLE_ENUM[displayName.name] },
			displayNameChangeSupported,
		}
	},

	methods: {
		onValidate(value) {
			return value !== ''
		},

		onSave(value) {
			if (oc_userconfig.avatar.generated) {
				// Update the avatar version so that avatar update handlers refresh correctly
				oc_userconfig.avatar.version = Date.now()
			}
			emit('settings:display-name:updated', value)
		},
	},
}
</script>
