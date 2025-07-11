<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section>
		<HeaderBar :is-heading="true" :readable="propertyReadable" />

		<ProfileCheckbox :profile-enabled.sync="profileEnabled" />

		<ProfilePreviewCard :organisation="organisation"
			:display-name="displayName"
			:profile-enabled="profileEnabled"
			:user-id="userId" />

		<EditProfileAnchorLink :profile-enabled="profileEnabled" />
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'

import EditProfileAnchorLink from './EditProfileAnchorLink.vue'
import HeaderBar from '../shared/HeaderBar.vue'
import ProfileCheckbox from './ProfileCheckbox.vue'
import ProfilePreviewCard from './ProfilePreviewCard.vue'

import { ACCOUNT_PROPERTY_READABLE_ENUM } from '../../../constants/AccountPropertyConstants.js'

const {
	organisation: { value: organisation },
	displayName: { value: displayName },
	profileEnabled,
	userId,
} = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'ProfileSection',

	components: {
		EditProfileAnchorLink,
		HeaderBar,
		ProfileCheckbox,
		ProfilePreviewCard,
	},

	data() {
		return {
			propertyReadable: ACCOUNT_PROPERTY_READABLE_ENUM.PROFILE_ENABLED,
			organisation,
			displayName,
			profileEnabled,
			userId,
		}
	},

	mounted() {
		subscribe('settings:display-name:updated', this.handleDisplayNameUpdate)
		subscribe('settings:organisation:updated', this.handleOrganisationUpdate)
	},

	beforeDestroy() {
		unsubscribe('settings:display-name:updated', this.handleDisplayNameUpdate)
		unsubscribe('settings:organisation:updated', this.handleOrganisationUpdate)
	},

	methods: {
		handleDisplayNameUpdate(displayName) {
			this.displayName = displayName
		},

		handleOrganisationUpdate(organisation) {
			this.organisation = organisation
		},
	},
}
</script>

<style lang="scss" scoped>
section {
	padding: 10px 10px;

	&:deep(button:disabled) {
		cursor: default;
	}
}
</style>
