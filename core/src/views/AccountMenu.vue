<!--
 - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcHeaderMenu id="user-menu"
		class="account-menu"
		is-nav
		:aria-label="t('core', 'Settings menu')"
		:description="avatarDescription">
		<template #trigger>
			<!-- The `key` is a hack as NcAvatar does not handle updating the preloaded status on show status change -->
			<NcAvatar :key="String(showUserStatus)"
				class="account-menu__avatar"
				disable-menu
				disable-tooltip
				:show-user-status="showUserStatus"
				:user="currentUserId"
				:preloaded-user-status="userStatus" />
		</template>
		<ul class="account-menu__list">
			<AccountMenuProfileEntry :id="profileEntry.id"
				:name="profileEntry.name"
				:href="profileEntry.href"
				:active="profileEntry.active" />
			<AccountMenuEntry v-for="entry in otherEntries"
				:id="entry.id"
				:key="entry.id"
				:name="entry.name"
				:href="entry.href"
				:active="entry.active"
				:icon="entry.icon" />
		</ul>
	</NcHeaderMenu>
</template>

<script lang="ts">
import { getCurrentUser } from '@nextcloud/auth'
import { emit, subscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { getCapabilities } from '@nextcloud/capabilities'
import { defineComponent } from 'vue'
import { getAllStatusOptions } from '../../../apps/user_status/src/services/statusOptionsService.js'

import axios from '@nextcloud/axios'
import logger from '../logger.js'

import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcHeaderMenu from '@nextcloud/vue/components/NcHeaderMenu'
import AccountMenuProfileEntry from '../components/AccountMenu/AccountMenuProfileEntry.vue'
import AccountMenuEntry from '../components/AccountMenu/AccountMenuEntry.vue'

interface ISettingsNavigationEntry {
	/**
	 * id of the entry, used as HTML ID, for example, "settings"
	 */
	id: string
	/**
	 * Label of the entry, for example, "Personal Settings"
	 */
	name: string
	/**
	 * Icon of the entry, for example, "/apps/settings/img/personal.svg"
	 */
	icon: string
	/**
	 * Type of the entry
	 */
	type: 'settings'|'link'|'guest'
	/**
	 * Link of the entry, for example, "/settings/user"
	 */
	href: string
	/**
	 * Whether the entry is active
	 */
	active: boolean
	/**
	 * Order of the entry
	 */
	order: number
	/**
	 * Number of unread pf this items
	 */
	unread: number
	/**
	 * Classes for custom styling
	 */
	classes: string
}

const USER_DEFINABLE_STATUSES = getAllStatusOptions()

export default defineComponent({
	name: 'AccountMenu',

	components: {
		AccountMenuEntry,
		AccountMenuProfileEntry,
		NcAvatar,
		NcHeaderMenu,
	},

	setup() {
		const settingsNavEntries = loadState<Record<string, ISettingsNavigationEntry>>('core', 'settingsNavEntries', {})
		const { profile: profileEntry, ...otherEntries } = settingsNavEntries

		return {
			currentDisplayName: getCurrentUser()?.displayName ?? getCurrentUser()!.uid,
			currentUserId: getCurrentUser()!.uid,

			profileEntry,
			otherEntries,

			t,
		}
	},

	data() {
		return {
			showUserStatus: false,
			userStatus: {
				status: null,
				icon: null,
				message: null,
			},
		}
	},

	computed: {
		translatedUserStatus() {
			return {
				...this.userStatus,
				status: this.translateStatus(this.userStatus.status),
			}
		},

		avatarDescription() {
			const description = [
				t('core', 'Avatar of {displayName}', { displayName: this.currentDisplayName }),
				...Object.values(this.translatedUserStatus).filter(Boolean),
			].join(' — ')
			return description
		},
	},

	async created() {
		if (!getCapabilities()?.user_status?.enabled) {
			return
		}

		const url = generateOcsUrl('/apps/user_status/api/v1/user_status')
		try {
			const response = await axios.get(url)
			const { status, icon, message } = response.data.ocs.data
			this.userStatus = { status, icon, message }
		} catch (e) {
			logger.error('Failed to load user status')
		}
		this.showUserStatus = true
	},

	mounted() {
		subscribe('user_status:status.updated', this.handleUserStatusUpdated)
		emit('core:user-menu:mounted')
	},

	methods: {
		handleUserStatusUpdated(state) {
			if (this.currentUserId === state.userId) {
				this.userStatus = {
					status: state.status,
					icon: state.icon,
					message: state.message,
				}
			}
		},

		translateStatus(status) {
			const statusMap = Object.fromEntries(
				USER_DEFINABLE_STATUSES.map(({ type, label }) => [type, label]),
			)
			if (statusMap[status]) {
				return statusMap[status]
			}
			return status
		},
	},
})
</script>

<style lang="scss" scoped>
:deep(#header-menu-user-menu) {
	padding: 0 !important;
}

.account-menu {
	:deep(button) {
		// Normally header menus are slightly translucent when not active
		// this is generally ok but for the avatar this is weird so fix the opacity
		opacity: 1 !important;

		// The avatar is just the "icon" of the button
		// So we add the focus-visible manually
		&:focus-visible {
			.account-menu__avatar {
				border: var(--border-width-input-focused) solid var(--color-background-plain-text);
			}
		}
	}

	// Ensure we do not waste space, as the header menu sets a default width of 350px
	:deep(.header-menu__content) {
		width: fit-content !important;
	}

	&__avatar {
		&:hover {
			// Add hover styles similar to the focus-visible style
			border: var(--border-width-input-focused) solid var(--color-background-plain-text);
		}
	}

	&__list {
		display: inline-flex;
		flex-direction: column;
		padding-block: var(--default-grid-baseline) 0;
		padding-inline: 0 var(--default-grid-baseline);

		> :deep(li) {
			box-sizing: border-box;
			// basically "fit-content"
			flex: 0 1;
		}
	}
}
</style>
