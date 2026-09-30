<!--
 - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import { getCurrentUser } from '@nextcloud/auth'
import { getCapabilities } from '@nextcloud/capabilities'
import { emit, subscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { computed, onMounted, ref } from 'vue'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcHeaderMenu from '@nextcloud/vue/components/NcHeaderMenu'
import AccountMenuEntry from '../components/AccountMenu/AccountMenuEntry.vue'
import AccountMenuProfileEntry from '../components/AccountMenu/AccountMenuProfileEntry.vue'
import { getAllStatusOptions } from '../../../apps/user_status/src/services/statusOptionsService.js'

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
	type: 'settings' | 'link' | 'guest'
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

interface IPreloadedUserStatus {
	status: string | null
	icon: string | null
	message: string | null
}

/**
 * NcAvatar fetches the status itself when `preloadedUserStatus` is falsy, so
 * always hand it a shape, even when there is nothing to show.
 */
function loadInitialUserStatus(): { showUserStatus: boolean, userStatus: IPreloadedUserStatus } {
	const userStatus: IPreloadedUserStatus = { status: null, icon: null, message: null }

	if (!getCapabilities()?.user_status?.enabled) {
		return { showUserStatus: false, userStatus }
	}

	// JSDataService emits `[]`, not an object, when there is no session user
	const state = loadState<Partial<IPreloadedUserStatus> | unknown[] | null>('user_status', 'status', null)
	if (state === null || typeof state !== 'object' || Array.isArray(state)) {
		return { showUserStatus: false, userStatus }
	}

	// `avatarDescription` joins everything truthy in here, so drop the payload's other fields
	const { status = null, icon = null, message = null } = state
	return { showUserStatus: true, userStatus: { status, icon, message } }
}

const statusLabels: Record<string, string> = Object.fromEntries(getAllStatusOptions().map(({ type, label }) => [type, label]))

const currentUser = getCurrentUser()!
const currentDisplayName = currentUser.displayName ?? currentUser.uid
const currentUserId = currentUser.uid

const settingsNavEntries = loadState<Record<string, ISettingsNavigationEntry>>('core', 'settingsNavEntries', {})
const { profile: profileEntry, ...otherEntries } = settingsNavEntries

const initialUserStatus = loadInitialUserStatus()
const showUserStatus = initialUserStatus.showUserStatus
const userStatus = ref(initialUserStatus.userStatus)

const avatarDescription = computed(() => {
	const translatedUserStatus = {
		...userStatus.value,
		status: userStatus.value.status && (statusLabels[userStatus.value.status] ?? userStatus.value.status),
	}
	return [
		t('core', 'Avatar of {displayName}', { displayName: currentDisplayName }),
		...Object.values(translatedUserStatus).filter(Boolean),
	].join(' — ')
})

onMounted(() => {
	subscribe('user_status:status.updated', handleUserStatusUpdated)
	emit('core:user-menu:mounted')
})

/**
 * Show the new status of the current user.
 *
 * @param state - The updated status
 */
function handleUserStatusUpdated(state: IPreloadedUserStatus & { userId: string }) {
	if (currentUserId === state.userId) {
		userStatus.value = {
			status: state.status,
			icon: state.icon,
			message: state.message,
		}
	}
}
</script>

<template>
	<NcHeaderMenu
		id="user-menu"
		class="account-menu"
		:aria-label="t('core', 'Settings menu')"
		:description="avatarDescription">
		<template #trigger>
			<NcAvatar
				class="account-menu__avatar"
				disableMenu
				disableTooltip
				:hideStatus="!showUserStatus"
				:user="currentUserId"
				:preloadedUserStatus="userStatus" />
		</template>
		<ul class="account-menu__list">
			<AccountMenuProfileEntry
				:id="profileEntry.id"
				:name="profileEntry.name"
				:href="profileEntry.href"
				:active="profileEntry.active" />
			<AccountMenuEntry
				v-for="entry in otherEntries"
				:id="entry.id"
				:key="entry.id"
				:name="entry.name"
				:href="entry.href"
				:active="entry.active"
				:icon="entry.icon" />
		</ul>
	</NcHeaderMenu>
</template>

<style lang="scss" scoped>
:deep(#header-menu-user-menu) {
	padding: 0 !important;
}

.account-menu {
	:deep(*) {
		// do not apply the alpha mask on the avatar div
		mask: none !important;
	}

	&__avatar {
		--account-menu-outline: var(--border-width-input) solid color-mix(in srgb, var(--color-background-plain-text), transparent 75%);
		outline: var(--account-menu-outline);
		position: fixed;

		&:hover {
			--account-menu-outline: none;
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

	// Ensure we do not waste space, as the header menu sets a default width of 350px
	:deep(.header-menu__content) {
		width: fit-content !important;
	}

	:deep(button) {
		// Normally header menus are slightly translucent when not active
		// this is generally ok but for the avatar this is weird so fix the opacity
		opacity: 1 !important;

		// The avatar is just the "icon" of the button
		// So we add the focus-visible manually
		&:focus-visible {
			.account-menu__avatar {
				--account-menu-outline: none;
				border: var(--border-width-input-focused) solid var(--color-background-plain-text);
			}
		}
	}
}
</style>
