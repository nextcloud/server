<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="visibility-container"
		:class="{ disabled }">
		<label :for="inputId">
			{{ displayId }}
		</label>
		<NcSelect :input-id="inputId"
			class="visibility-container__select"
			:clearable="false"
			:options="visibilityOptions"
			:value="visibilityObject"
			label-outside
			@option:selected="onVisibilityChange" />
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'

import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'

import { saveProfileParameterVisibility } from '../../../service/ProfileService.js'
import { VISIBILITY_PROPERTY_ENUM } from '../../../constants/ProfileConstants.js'
import { handleError } from '../../../utils/handlers.ts'

const { profileEnabled } = loadState('settings', 'personalInfoParameters', false)

export default {
	name: 'VisibilityDropdown',

	components: {
		NcSelect,
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
		visibility: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			initialVisibility: this.visibility,
			profileEnabled,
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
				this.$emit('update:visibility', visibility)

				if (visibility !== '') {
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
				handleError(error, errorMessage)
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
	display: flex;
	flex-wrap: wrap;

	&.disabled {
		filter: grayscale(1);
		opacity: 0.5;
		cursor: default;
		pointer-events: none;

		& *,
		&:deep(*) {
			cursor: default;
			pointer-events: none;
		}
	}

	label {
		color: var(--color-text-lighter);
		width: 150px;
		line-height: 50px;
	}

	&__select {
		width: 270px;
		max-width: 40vw;
	}
}
</style>
