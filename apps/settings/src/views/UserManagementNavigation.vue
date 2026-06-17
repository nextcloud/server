<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcAppNavigation
		class="account-management__navigation"
		:aria-label="t('settings', 'Account management')">
		<NcAppNavigationNew
			button-id="new-user-button"
			:text="t('settings', 'New account')"
			@click="showNewUserMenu"
			@keyup.enter="showNewUserMenu"
			@keyup.space="showNewUserMenu">
			<template #icon>
				<NcIconSvgWrapper :path="mdiPlus" />
			</template>
		</NcAppNavigationNew>

		<NcAppNavigationSearch
			ref="searchField"
			v-model="searchInput"
			:label="t('settings', 'Search accounts and groups…')" />

		<NcAppNavigationList
			class="account-management__system-list"
			data-cy-users-settings-navigation-groups="system">
			<NcAppNavigationItem
				id="everyone"
				:exact="true"
				:name="t('settings', 'All accounts')"
				:to="{ name: 'users' }">
				<template #icon>
					<NcIconSvgWrapper :path="mdiAccountOutline" />
				</template>
				<template #counter>
					<NcCounterBubble v-if="userCount" :type="!selectedGroupDecoded ? 'highlighted' : undefined">
						{{ userCount }}
					</NcCounterBubble>
				</template>
			</NcAppNavigationItem>

			<NcAppNavigationItem
				v-if="settings.isAdmin"
				id="admin"
				:exact="true"
				:name="t('settings', 'Admins')"
				:to="{ name: 'group', params: { selectedGroup: 'admin' } }">
				<template #icon>
					<NcIconSvgWrapper :path="mdiShieldAccountOutline" />
				</template>
				<template #counter>
					<NcCounterBubble
						v-if="adminGroup && adminGroup.count > 0"
						:type="selectedGroupDecoded === 'admin' ? 'highlighted' : undefined">
						{{ adminGroup.count }}
					</NcCounterBubble>
				</template>
			</NcAppNavigationItem>

			<NcAppNavigationItem
				v-if="isAdminOrDelegatedAdmin"
				id="recent"
				:exact="true"
				:name="t('settings', 'Recently active')"
				:to="{ name: 'group', params: { selectedGroup: '__nc_internal_recent' } }">
				<template #icon>
					<NcIconSvgWrapper :path="mdiHistory" />
				</template>
				<template #counter>
					<NcCounterBubble
						v-if="recentGroup?.usercount"
						:type="selectedGroupDecoded === '__nc_internal_recent' ? 'highlighted' : undefined">
						{{ recentGroup.usercount }}
					</NcCounterBubble>
				</template>
			</NcAppNavigationItem>

			<!-- Hide the disabled if none, if we don't have the data (-1) show it -->
			<NcAppNavigationItem
				v-if="disabledGroup && (disabledGroup.usercount > 0 || disabledGroup.usercount === -1)"
				id="disabled"
				:exact="true"
				:name="t('settings', 'Disabled accounts')"
				:to="{ name: 'group', params: { selectedGroup: 'disabled' } }">
				<template #icon>
					<NcIconSvgWrapper :path="mdiAccountOffOutline" />
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
			<NcButton
				class="account-management__settings-toggle"
				variant="tertiary"
				wide
				@click="isDialogOpen = true">
				<template #icon>
					<NcIconSvgWrapper :path="mdiCogOutline" />
				</template>
				<span class="account-management__settings-toggle-text">
					{{ t('settings', 'Account management settings') }}
				</span>
			</NcButton>
			<UserSettingsDialog :open.sync="isDialogOpen" />
		</template>
	</NcAppNavigation>
</template>

<script setup lang="ts">
import { mdiAccountOffOutline, mdiAccountOutline, mdiCogOutline, mdiHistory, mdiPlus, mdiShieldAccountOutline } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { useHotKey } from '@nextcloud/vue/composables/useHotKey'
import debounce from 'debounce'
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { useRoute } from 'vue-router/composables'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import NcAppNavigationNew from '@nextcloud/vue/components/NcAppNavigationNew'
import NcAppNavigationSearch from '@nextcloud/vue/components/NcAppNavigationSearch'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCounterBubble from '@nextcloud/vue/components/NcCounterBubble'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import AppNavigationGroupList from '../components/AppNavigationGroupList.vue'
import UserSettingsDialog from '../components/Users/UserSettingsDialog.vue'
import { useFormatGroups } from '../composables/useGroupsNavigation.js'
import { useStore } from '../store/index.js'

const route = useRoute()
const store = useStore()

const searchField = ref<InstanceType<typeof NcAppNavigationSearch>>()
const searchInput = ref('')
const commitSearch = debounce((query: string) => {
	store.commit('setSearchQuery', query)
}, 300)
watch(searchInput, (value) => commitSearch(value))

onBeforeUnmount(() => commitSearch.clear())

// Intercept Ctrl/Cmd+F to focus the local search. useHotKey ignores the
// event when an input/textarea is already focused, so a second press falls
// through to the browser's native find-in-page.
useHotKey('f', () => searchField.value?.$refs.inputElement?.focus(), { ctrl: true, stop: true, prevent: true })

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
	store.dispatch('setShowConfig', {
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
	&__search {
		padding-block: var(--default-grid-baseline, 4px);
		padding-inline: var(--app-navigation-padding, 8px);
	}

	&__system-list {
		height: auto !important;
		overflow: visible !important;
	}

	&__group-list {
		height: 100% !important;
	}

	&__settings-toggle {
		margin-bottom: var(--body-container-margin);

		&-text {
			font-weight: 500;
		}
	}
}
</style>
