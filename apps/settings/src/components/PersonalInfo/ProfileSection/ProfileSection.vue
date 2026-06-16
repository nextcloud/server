<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section class="profile-section">
		<ProfilePreviewCard
			:status-message="statusMessage"
			:display-name="displayName"
			:user-id="userId" />

		<NcFormBox>
			<NcFormBoxSwitch
				:model-value="profileEnabled"
				:label="t('settings', 'Nextcloud profile')"
				:disabled="loading"
				@update:model-value="saveEnableProfile" />
			<NcFormBoxButton
				v-if="profileEnabled"
				:label="t('settings', 'View full profile')"
				:href="profilePageLink"
				target="_blank">
				<template #icon>
					<OpenInNew :size="20" />
				</template>
			</NcFormBoxButton>
		</NcFormBox>
	</section>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcFormBoxSwitch from '@nextcloud/vue/components/NcFormBoxSwitch'
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue'
import ProfilePreviewCard from './ProfilePreviewCard.vue'
import { ACCOUNT_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants.js'
import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../../utils/handlers.ts'

const {
	statusMessage,
	displayName: { value: displayName },
	profileEnabled,
	userId,
} = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'ProfileSection',

	components: {
		NcFormBox,
		NcFormBoxButton,
		NcFormBoxSwitch,
		OpenInNew,
		ProfilePreviewCard,
	},

	data() {
		return {
			statusMessage,
			displayName,
			profileEnabled,
			userId,
			loading: false,
		}
	},

	computed: {
		profilePageLink() {
			return generateUrl('/u/{userId}', { userId: getCurrentUser().uid })
		},
	},

	mounted() {
		subscribe('settings:display-name:updated', this.handleDisplayNameUpdate)
	},

	beforeDestroy() {
		unsubscribe('settings:display-name:updated', this.handleDisplayNameUpdate)
	},

	methods: {
		async saveEnableProfile(profileEnabled) {
			this.loading = true
			try {
				const responseData = await savePrimaryAccountProperty(ACCOUNT_PROPERTY_ENUM.PROFILE_ENABLED, profileEnabled)
				if (responseData.ocs?.meta?.status === 'ok') {
					this.profileEnabled = profileEnabled
					emit('settings:profile-enabled:updated', profileEnabled)
				} else {
					handleError(null, t('settings', 'Unable to update profile enabled state'))
				}
			} catch (e) {
				handleError(e, t('settings', 'Unable to update profile enabled state'))
			} finally {
				this.loading = false
			}
		},

		handleDisplayNameUpdate(displayName) {
			this.displayName = displayName
		},
	},
}
</script>

<style lang="scss" scoped>
.profile-section {
	display: flex;
	flex-direction: column;
	gap: 8px;
	padding-block: 6px;
}
</style>
