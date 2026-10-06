<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ITokenResponse } from '../../../../apps/settings/src/store/authtoken.ts'

import { mdiQrcodeScan } from '@mdi/js'
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { getCapabilities } from '@nextcloud/capabilities'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { addPasswordConfirmationInterceptors, PwdConfirmationMode } from '@nextcloud/password-confirmation'
import { generateUrl } from '@nextcloud/router'
import { spawnDialog } from '@nextcloud/vue/functions/dialog'
import { onBeforeUnmount, onMounted, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcListItem from '@nextcloud/vue/components/NcListItem'
import AccountQrLoginDialog from './AccountQRLoginDialog.vue'

defineProps<{
	id: string
	name: string
	href: string
	active: boolean
}>()

addPasswordConfirmationInterceptors(axios)

// @ts-expect-error capabilities is missing the capability to type it...
const canCreateAppToken = getCapabilities().core?.['can-create-app-token'] ?? false

const profileEnabled = ref(loadState('user_status', 'profileEnabled', { profileEnabled: false }).profileEnabled)
const displayName = ref(getCurrentUser()!.displayName ?? getCurrentUser()!.uid)

onMounted(() => {
	subscribe('settings:profile-enabled:updated', handleProfileEnabledUpdate)
	subscribe('settings:display-name:updated', handleDisplayNameUpdate)
})

onBeforeUnmount(() => {
	unsubscribe('settings:profile-enabled:updated', handleProfileEnabledUpdate)
	unsubscribe('settings:display-name:updated', handleDisplayNameUpdate)
})

/**
 * Create a one-time app password and show it as QR code for the mobile apps.
 */
async function handleQrCodeClick() {
	const { data } = await axios.post<ITokenResponse>(
		generateUrl('/settings/personal/authtokens'),
		{ qrcodeLogin: true },
		{ confirmPassword: PwdConfirmationMode.Strict },
	)

	await spawnDialog(AccountQrLoginDialog, { data })
}

/**
 * @param enabled - Whether the profile is enabled now
 */
function handleProfileEnabledUpdate(enabled: boolean) {
	profileEnabled.value = enabled
}

/**
 * @param name - The new display name
 */
function handleDisplayNameUpdate(name: string) {
	displayName.value = name
}
</script>

<template>
	<NcListItem
		:id="profileEnabled ? undefined : id"
		:anchorId="id"
		:active="active"
		compact
		:href="profileEnabled ? href : undefined"
		:name="displayName"
		target="_self">
		<template v-if="profileEnabled" #subname>
			{{ name }}
		</template>
		<template v-if="canCreateAppToken" #extra-actions>
			<NcButton
				:aria-label="t('core', 'Show QR code for mobile app login')"
				variant="secondary"
				@click="handleQrCodeClick">
				<template #icon>
					<NcIconSvgWrapper :path="mdiQrcodeScan" :size="20" />
				</template>
			</NcButton>
		</template>
	</NcListItem>
</template>
