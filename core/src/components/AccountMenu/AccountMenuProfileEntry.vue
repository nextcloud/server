<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcListItem
		:id="profileEnabled ? undefined : id"
		:anchor-id="id"
		:active="active"
		compact
		:href="profileEnabled ? href : undefined"
		:name="displayName"
		target="_self">
		<template v-if="profileEnabled" #subname>
			{{ name }}
		</template>
		<template v-if="canCreateAppToken" #extra-actions>
			<NcButton variant="secondary" @click="handleQrCodeClick">
				<template #icon>
					<IconQrcodeScan :size="20" />
				</template>
			</NcButton>
		</template>
		<template v-if="loading" #indicator>
			<NcLoadingIcon />
		</template>
	</NcListItem>
</template>

<script lang="ts">
import type { ITokenResponse } from '../../../../apps/settings/src/store/authtoken.ts'

import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { getCapabilities } from '@nextcloud/capabilities'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { addPasswordConfirmationInterceptors, PwdConfirmationMode } from '@nextcloud/password-confirmation'
import { generateUrl } from '@nextcloud/router'
import { spawnDialog } from '@nextcloud/vue/functions/dialog'
import { defineComponent } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcListItem from '@nextcloud/vue/components/NcListItem'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import IconQrcodeScan from 'vue-material-design-icons/QrcodeScan.vue'
import AccountQrLoginDialog from './AccountQRLoginDialog.vue'

addPasswordConfirmationInterceptors(axios)

const { profileEnabled } = loadState('user_status', 'profileEnabled', { profileEnabled: false })

// @ts-expect-error capabilities is missing the capability to type it...
const canCreateAppToken = getCapabilities().core?.['can-create-app-token'] ?? false

export default defineComponent({
	name: 'AccountMenuProfileEntry',

	components: {
		IconQrcodeScan,
		NcButton,
		NcListItem,
		NcLoadingIcon,
	},

	props: {
		id: {
			type: String,
			required: true,
		},

		name: {
			type: String,
			required: true,
		},

		href: {
			type: String,
			required: true,
		},

		active: {
			type: Boolean,
			required: true,
		},
	},

	setup() {
		return {
			canCreateAppToken,
			profileEnabled,
			displayName: getCurrentUser()!.displayName,
		}
	},

	data() {
		return {
			loading: false,
		}
	},

	mounted() {
		subscribe('settings:profile-enabled:updated', this.handleProfileEnabledUpdate)
		subscribe('settings:display-name:updated', this.handleDisplayNameUpdate)
	},

	beforeDestroy() {
		unsubscribe('settings:profile-enabled:updated', this.handleProfileEnabledUpdate)
		unsubscribe('settings:display-name:updated', this.handleDisplayNameUpdate)
	},

	methods: {
		handleClick() {
			if (this.profileEnabled) {
				this.loading = true
			}
		},

		async handleQrCodeClick() {
			const { data } = await axios.post<ITokenResponse>(
				generateUrl('/settings/personal/authtokens'),
				{ qrcodeLogin: true },
				{ confirmPassword: PwdConfirmationMode.Strict },
			)

			await spawnDialog(AccountQrLoginDialog, { data })
		},

		handleProfileEnabledUpdate(profileEnabled: boolean) {
			this.profileEnabled = profileEnabled
		},

		handleDisplayNameUpdate(displayName: string) {
			this.displayName = displayName
		},
	},
})
</script>
