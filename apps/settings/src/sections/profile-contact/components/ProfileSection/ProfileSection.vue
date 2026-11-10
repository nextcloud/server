<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiOpenInNew } from '@mdi/js'
import { emit, subscribe, unsubscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { onBeforeUnmount, onMounted, ref } from 'vue'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcFormBoxSwitch from '@nextcloud/vue/components/NcFormBoxSwitch'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import ProfilePreviewCard from './ProfilePreviewCard.vue'
import { ACCOUNT_PROPERTY_ENUM } from '../../../../constants/AccountPropertyConstants.ts'
import { savePrimaryAccountProperty } from '../../../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../../../utils/handlers.ts'

const {
	statusMessage,
	displayName: { value: initialDisplayName },
	profileEnabled: initialProfileEnabled,
	userId,
} = loadState<{
	statusMessage: string
	displayName: { value: string }
	profileEnabled: boolean
	userId: string
}>('settings', 'personalInfoParameters')

const profilePageLink = generateUrl('/u/{userId}', { userId })

const displayName = ref(initialDisplayName)
const profileEnabled = ref(initialProfileEnabled)
const loading = ref(false)

/**
 * @param newDisplayName The saved display name
 */
function handleDisplayNameUpdate(newDisplayName: string) {
	displayName.value = newDisplayName
}

onMounted(() => subscribe('settings:display-name:updated', handleDisplayNameUpdate))
onBeforeUnmount(() => unsubscribe('settings:display-name:updated', handleDisplayNameUpdate))

/**
 * @param enabled Whether the profile should be enabled
 */
async function saveEnableProfile(enabled: boolean) {
	loading.value = true
	try {
		await savePrimaryAccountProperty(ACCOUNT_PROPERTY_ENUM.PROFILE_ENABLED, enabled)
		emit('settings:profile-enabled:updated', enabled)
	} catch (error) {
		profileEnabled.value = !enabled
		handleError(error, t('settings', 'Unable to update profile enabled state'))
	} finally {
		loading.value = false
	}
}
</script>

<template>
	<section :class="$style.profileSection">
		<ProfilePreviewCard
			:statusMessage="statusMessage"
			:displayName="displayName"
			:userId="userId" />

		<NcFormBox>
			<NcFormBoxSwitch
				v-model="profileEnabled"
				:label="t('settings', 'Nextcloud profile')"
				:disabled="loading"
				@update:modelValue="saveEnableProfile" />
			<NcFormBoxButton
				v-if="profileEnabled"
				:label="t('settings', 'View full profile')"
				:href="profilePageLink"
				target="_blank">
				<template #icon>
					<NcIconSvgWrapper :path="mdiOpenInNew" :size="20" />
				</template>
			</NcFormBoxButton>
		</NcFormBox>
	</section>
</template>

<style module>
.profileSection {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 2);
	padding-block: 6px;
}
</style>
