<!--
	- @copyright 2022 Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
	-
	- @license AGPL-3.0-or-later
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
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
