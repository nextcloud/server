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
	<!-- TODO remove this inline margin placeholder once the settings layout is updated -->
	<section id="profile-visibility"
		:style="{ marginLeft }">
		<HeaderBar :is-heading="true" :readable="heading" />

		<em :class="{ disabled }">
			{{ t('settings', 'The more restrictive setting of either visibility or scope is respected on your Profile. For example, if visibility is set to "Show to everyone" and scope is set to "Private", "Private" is respected.') }}
		</em>

		<div class="visibility-dropdowns"
			:style="{
				gridTemplateRows: `repeat(${rows}, 44px)`,
			}">
			<VisibilityDropdown v-for="param in visibilityParams"
				:key="param.id"
				:param-id="param.id"
				:display-id="param.displayId"
				:visibility.sync="param.visibility" />
		</div>
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'

import HeaderBar from '../shared/HeaderBar.vue'
import VisibilityDropdown from './VisibilityDropdown.vue'
import { PROFILE_READABLE_ENUM } from '../../../constants/AccountPropertyConstants.js'

const { profileConfig } = loadState('settings', 'profileParameters', {})
const { profileEnabled } = loadState('settings', 'personalInfoParameters', false)

const compareParams = (a, b) => {
	if (a.appId === b.appId || (a.appId !== 'core' && b.appId !== 'core')) {
		return a.displayId.localeCompare(b.displayId)
	} else if (a.appId === 'core') {
		return 1
	} else {
		return -1
	}
}

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
			visibilityParams: Object.entries(profileConfig)
				.map(([paramId, { appId, displayId, visibility }]) => ({ id: paramId, appId, displayId, visibility }))
				.sort(compareParams),
			// TODO remove this when not used once the settings layout is updated
			marginLeft: window.matchMedia('(min-width: 1600px)').matches
				? window.getComputedStyle(document.getElementById('vue-avatar-section')).getPropertyValue('width').trim()
				: '0px',
		}
	},

	computed: {
		disabled() {
			return !this.profileEnabled
		},

		rows() {
			return Math.ceil(this.visibilityParams.length / 2)
		},
	},

	mounted() {
		subscribe('settings:profile-enabled:updated', this.handleProfileEnabledUpdate)
		// TODO remove this when not used once the settings layout is updated
		window.onresize = () => {
			this.marginLeft = window.matchMedia('(min-width: 1600px)').matches
				? window.getComputedStyle(document.getElementById('vue-avatar-section')).getPropertyValue('width').trim()
				: '0px'
		}
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
	padding: 30px;
	max-width: 900px;
	width: 100%;

	em {
		display: block;
		margin: 16px 0;

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

	.visibility-dropdowns {
		display: grid;
		gap: 10px 40px;
	}

	@media (min-width: 1200px) {
		width: 940px;

		.visibility-dropdowns {
			grid-auto-flow: column;
		}
	}

	@media (max-width: 1200px) {
		width: 470px;
	}
}
</style>
