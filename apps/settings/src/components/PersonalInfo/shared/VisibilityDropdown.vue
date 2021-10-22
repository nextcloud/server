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
	<div
		class="visibility-container"
		:class="{ disabled }">
		<label :for="inputId">
			{{ showDisplayId ? t('settings', '{displayId} visibility', { displayId }) : t('settings', 'Visibility on Profile') }}
		</label>
		<Multiselect
			:id="inputId"
			:options="visibilityOptions"
			track-by="name"
			label="label"
			:value="visibilityObject"
			@change="onVisibilityChange" />
	</div>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'

import Multiselect from '@nextcloud/vue/dist/Components/Multiselect'

import { saveProfileParameterVisibility } from '../../../service/ProfileService'
import { validateStringInput } from '../../../utils/validate'
import { VISIBILITY_PROPERTY_ENUM } from '../../../constants/ProfileConstants'

const { profileConfig } = loadState('settings', 'profileParameters', {})
const { profileEnabled } = loadState('settings', 'personalInfoParameters', false)

export default {
	name: 'VisibilityDropdown',

	components: {
		Multiselect,
	},

	props: {
		paramId: {
			type: String,
			required: true,
		},
		displayId: {
			type: String,
			required: true,
		},
		showDisplayId: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			initialVisibility: profileConfig[this.paramId].visibility,
			profileEnabled,
			visibility: profileConfig[this.paramId].visibility,
		}
	},

	computed: {
		disabled() {
			return !this.profileEnabled
		},

		inputId() {
			return `profile-visibility-${this.paramId}`
		},

		visibilityObject() {
			return VISIBILITY_PROPERTY_ENUM[this.visibility]
		},

		visibilityOptions() {
			return Object.values(VISIBILITY_PROPERTY_ENUM)
		},
	},

	mounted() {
		subscribe('settings:profile-enabled:updated', this.handleProfileEnabledUpdate)
	},

	beforeDestroy() {
		unsubscribe('settings:profile-enabled:updated', this.handleProfileEnabledUpdate)
	},

	methods: {
		async onVisibilityChange(visibilityObject) {
			// This check is needed as the argument is null when selecting the same option
			if (visibilityObject !== null) {
				const { name: visibility } = visibilityObject
				this.visibility = visibility

				if (validateStringInput(visibility)) {
					await this.updateVisibility(visibility)
				}
			}
		},

		async updateVisibility(visibility) {
			try {
				const responseData = await saveProfileParameterVisibility(this.paramId, visibility)
				this.handleResponse({
					visibility,
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update visibility of {displayId}', { displayId: this.displayId }),
					error: e,
				})
			}
		},

		handleResponse({ visibility, status, errorMessage, error }) {
			if (status === 'ok') {
				// Ensure that local state reflects server state
				this.initialVisibility = visibility
			} else {
				showError(errorMessage)
				this.logger.error(errorMessage, error)
			}
		},

		handleProfileEnabledUpdate(profileEnabled) {
			this.profileEnabled = profileEnabled
		},
	},
}
</script>

<style lang="scss" scoped>
.visibility-container {
	margin-top: 16px;
	display: grid;

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

	label {
		color: var(--color-text-lighter);
		margin-bottom: 3px;
	}
}
</style>
