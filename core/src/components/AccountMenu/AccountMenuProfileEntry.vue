<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcListItem :id="profileEnabled ? undefined : id"
		:anchor-id="id"
		:active="active"
		compact
		:href="profileEnabled ? href : undefined"
		:name="displayName"
		target="_self">
		<template v-if="profileEnabled" #subname>
			{{ name }}
		</template>
		<template v-if="loading" #indicator>
			<NcLoadingIcon />
		</template>
	</NcListItem>
</template>

<script lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { getCurrentUser } from '@nextcloud/auth'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { defineComponent } from 'vue'

import NcListItem from '@nextcloud/vue/dist/Components/NcListItem.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

const { profileEnabled } = loadState('user_status', 'profileEnabled', { profileEnabled: false })

export default defineComponent({
	name: 'AccountMenuProfileEntry',

	components: {
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

		handleProfileEnabledUpdate(profileEnabled: boolean) {
			this.profileEnabled = profileEnabled
		},

		handleDisplayNameUpdate(displayName: string) {
			this.displayName = displayName
		},
	},
})
</script>
