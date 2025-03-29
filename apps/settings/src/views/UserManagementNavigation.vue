<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcAppNavigation class="account-management__navigation"
		:aria-label="t('settings', 'Account management')">
		<NcAppNavigationNew button-id="new-user-button"
			:text="t('settings','New account')"
			@click="showNewUserMenu"
			@keyup.enter="showNewUserMenu"
			@keyup.space="showNewUserMenu">
			<template #icon>
				<NcIconSvgWrapper :path="mdiPlus" />
			</template>
		</NcAppNavigationNew>

		<NcAppNavigationList class="account-management__system-list"
			data-cy-users-settings-navigation-groups="system">
			<NcAppNavigationItem id="everyone"
				:exact="true"
				:name="t('settings', 'All accounts')"
				:to="{ name: 'users' }">
				<template #icon>
					<NcIconSvgWrapper :path="mdiAccount" />
				</template>
				<template #counter>
					<NcCounterBubble v-if="userCount" :type="!selectedGroupDecoded ? 'highlighted' : undefined">
						{{ userCount }}
					</NcCounterBubble>
				</template>
			</NcAppNavigationItem>

			<NcAppNavigationItem v-if="settings.isAdmin"
				id="admin"
				:exact="true"
				:name="t('settings', 'Admins')"
				:to="{ name: 'group', params: { selectedGroup: 'admin' } }">
				<template #icon>
					<NcIconSvgWrapper :path="mdiShieldAccount" />
				</template>
				<template #counter>
					<NcCounterBubble v-if="adminGroup && adminGroup.count > 0"
						:type="selectedGroupDecoded === 'admin' ? 'highlighted' : undefined">
						{{ adminGroup.count }}
					</NcCounterBubble>
				</template>
			</NcAppNavigationItem>

			<NcAppNavigationItem v-if="isAdminOrDelegatedAdmin"
				id="recent"
				:exact="true"
				:name="t('settings', 'Recently active')"
				:to="{ name: 'group', params: { selectedGroup: '__nc_internal_recent' } }">
				<template #icon>
					<NcIconSvgWrapper :path="mdiHistory" />
				</template>
				<template #counter>
					<NcCounterBubble v-if="recentGroup?.usercount"
						:type="selectedGroupDecoded === '__nc_internal_recent' ? 'highlighted' : undefined">
						{{ recentGroup.usercount }}
					</NcCounterBubble>
				</template>
			</NcAppNavigationItem>

			<!-- Hide the disabled if none, if we don't have the data (-1) show it -->
			<NcAppNavigationItem v-if="disabledGroup && (disabledGroup.usercount > 0 || disabledGroup.usercount === -1)"
				id="disabled"
				:exact="true"
				:name="t('settings', 'Disabled accounts')"
				:to="{ name: 'group', params: { selectedGroup: 'disabled' } }">
				<template #icon>
					<NcIconSvgWrapper :path="mdiAccountOff" />
				</template>
				<template v-if="disabledGroup.usercount > 0" #counter>
					<NcCounterBubble :type="selectedGroupDecoded === 'disabled' ? 'highlighted' : undefined">
						{{ disabledGroup.usercount }}
					</NcCounterBubble>
				</template>
			</NcAppNavigationItem>
		</NcAppNavigationList>

		<AppNavigationGroupList />

		<template #footer>
			<NcButton class="account-management__settings-toggle"
				type="tertiary"
				@click="isDialogOpen = true">
				<template #icon>
					<NcIconSvgWrapper :path="mdiCog" />
				</template>
				{{ t('settings', 'Account management settings') }}
			</NcButton>
			<UserSettingsDialog :open.sync="isDialogOpen" />
		</template>
	</NcAppNavigation>
</template>

<script setup lang="ts">
import { mdiAccount, mdiAccountOff, mdiCog, mdiPlus, mdiShieldAccount, mdiHistory } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'

import NcAppNavigation from '@nextcloud/vue/dist/Components/NcAppNavigation.js'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcAppNavigationList from '@nextcloud/vue/dist/Components/NcAppNavigationList.js'
import NcAppNavigationNew from '@nextcloud/vue/dist/Components/NcAppNavigationNew.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCounterBubble from '@nextcloud/vue/dist/Components/NcCounterBubble.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'

import UserSettingsDialog from '../components/Users/UserSettingsDialog.vue'
import AppNavigationGroupList from '../components/AppNavigationGroupList.vue'

import { useStore } from '../store'
import { useRoute } from 'vue-router/composables'
import { useFormatGroups } from '../composables/useGroupsNavigation'

const route = useRoute()
const store = useStore()

/** State of the 'new-account' dialog */
const isDialogOpen = ref(false)

/** Current active group in the view - this is URL encoded */
const selectedGroup = computed(() => route.params?.selectedGroup)
/** Current active group - URL decoded  */
const selectedGroupDecoded = computed(() => selectedGroup.value ? decodeURIComponent(selectedGroup.value) : null)

/** Overall user count */
const userCount = computed(() => store.getters.getUserCount)
/** All available groups */
const groups = computed(() => store.getters.getSortedGroups)
const { adminGroup, recentGroup, disabledGroup } = useFormatGroups(groups)

/** Server settings for current user */
const settings = computed(() => store.getters.getServerData)
/** True if the current user is a (delegated) admin */
const isAdminOrDelegatedAdmin = computed(() => settings.value.isAdmin || settings.value.isDelegatedAdmin)

/**
 * Open the new-user form dialog
 */
function showNewUserMenu() {
	store.commit('setShowConfig', {
		key: 'showNewUserForm',
		value: true,
	})
}
</script>

<style scoped lang="scss">
.account-management {
	&__navigation {
		:deep(.app-navigation__body) {
			will-change: scroll-position;
		}
	}
	&__system-list {
		height: auto !important;
		overflow: visible !important;
	}

	&__group-list {
		height: 100% !important;
	}

	&__settings-toggle {
		margin-bottom: 12px;
	}
}
</style>
