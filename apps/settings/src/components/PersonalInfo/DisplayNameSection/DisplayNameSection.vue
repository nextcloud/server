<!--
	- @copyright 2021, Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
	-
	- @license GNU AGPL version 3 or any later version
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->

<template>
	<section>
		<HeaderBar :account-property="accountProperty"
			label-for="displayname"
			:is-editable="displayNameChangeSupported"
			:is-valid-section="isValidSection"
			:scope.sync="primaryDisplayName.scope" />

		<template v-if="displayNameChangeSupported">
			<DisplayName :display-name.sync="primaryDisplayName.value"
				:scope.sync="primaryDisplayName.scope" />
		</template>

		<span v-else>
			{{ primaryDisplayName.value || t('settings', 'No full name set') }}
		</span>
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import DisplayName from './DisplayName'
import HeaderBar from '../shared/HeaderBar'

import { ACCOUNT_PROPERTY_READABLE_ENUM } from '../../../constants/AccountPropertyConstants'
import { validateStringInput } from '../../../utils/validate'

const { displayNameMap: { primaryDisplayName } } = loadState('settings', 'personalInfoParameters', {})
const { displayNameChangeSupported } = loadState('settings', 'accountParameters', {})

export default {
	name: 'DisplayNameSection',

	components: {
		DisplayName,
		HeaderBar,
	},

	data() {
		return {
			accountProperty: ACCOUNT_PROPERTY_READABLE_ENUM.DISPLAYNAME,
			displayNameChangeSupported,
			primaryDisplayName,
		}
	},

	computed: {
		isValidSection() {
			return validateStringInput(this.primaryDisplayName.value)
		},
	},
}
</script>

<style lang="scss" scoped>
section {
	padding: 10px 10px;

	&::v-deep button:disabled {
		cursor: default;
	}
}
</style>
