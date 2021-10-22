<!--
	- @copyright 2021 Christopher Ng <chrng8@gmail.com>
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
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->

<template>
	<section>
		<HeaderBar
			:account-property="heading" />

		<VisibilityDropdown v-for="parameter in visibilityArray"
			:key="parameter.id"
			:param-id="parameter.id"
			:display-id="parameter.displayId"
			:show-display-id="true"
			:visibility.sync="parameter.visibility" />

		<em :class="{ disabled }">{{ t('settings', 'The more restrictive setting of either visibility or scope is respected on your Profile — For example, when visibility is set to "Show to everyone" and scope is set to "Private", "Private" will be respected') }}</em>
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'

import HeaderBar from '../shared/HeaderBar'
import VisibilityDropdown from '../shared/VisibilityDropdown'
import { ACCOUNT_PROPERTY_ENUM, PROFILE_READABLE_ENUM } from '../../../constants/AccountPropertyConstants'

const { profileConfig } = loadState('settings', 'profileParameters', {})
const { profileEnabled } = loadState('settings', 'personalInfoParameters', false)

export default {
	name: 'ProfileVisibilitySection',

	components: {
		HeaderBar,
		VisibilityDropdown,
	},

	data() {
		return {
			heading: PROFILE_READABLE_ENUM.PROFILE_VISIBILITY,
			profileEnabled,
			visibilityArray: Object.entries(profileConfig)
				// Filter for profile parameters registered by apps in this section as visibility controls for the rest (account properties) are handled in their respective property sections
				.filter(([paramId, { displayId, visibility }]) => !Object.values(ACCOUNT_PROPERTY_ENUM).includes(paramId))
				.map(([paramId, { displayId, visibility }]) => ({ id: paramId, displayId, visibility })),
		}
	},

	computed: {
		disabled() {
			return !this.profileEnabled
		},
	},

	mounted() {
		subscribe('settings:profile-enabled:updated', this.handleProfileEnabledUpdate)
	},

	beforeDestroy() {
		unsubscribe('settings:profile-enabled:updated', this.handleProfileEnabledUpdate)
	},

	methods: {
		handleProfileEnabledUpdate(profileEnabled) {
			this.profileEnabled = profileEnabled
		},
	},
}
</script>

<style lang="scss" scoped>
section {
	padding: 10px 10px;

	em {
		display: block;
		margin-top: 16px;

		&.disabled {
			filter: grayscale(1);
			opacity: 0.5;
			cursor: default;
			pointer-events: none;

			& *,
			&::v-deep * {
				cursor: default;
				pointer-events: none;
			}
		}
	}

	&::v-deep button:disabled {
		cursor: default;
	}
}
</style>
