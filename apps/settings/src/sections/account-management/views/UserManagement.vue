<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { UserAction } from '../../../views/user-types.d.ts'

import { emit } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcContent from '@nextcloud/vue/components/NcContent'
import UserList from '../components/UserList.vue'
import UserManagementNavigation from './UserManagementNavigation.vue'
import { useSelectedGroup } from '../composables/useGroupsNavigation.ts'

const externalActions = ref<UserAction[]>([])

/** The group or internal view (e.g. `disabled`) that is shown, null for all accounts */
const selectedGroup = useSelectedGroup()

const pageHeading = computed(() => {
	if (selectedGroup.value === null) {
		return t('settings', 'All accounts')
	}
	const matchHeading: Record<string, string> = {
		admin: t('settings', 'Admins'),
		disabled: t('settings', 'Disabled accounts'),
	}
	// rendered as text, which Vue escapes already
	return matchHeading[selectedGroup.value] ?? t('settings', 'Account group: {group}', { group: selectedGroup.value }, { escape: false })
})

/**
 * Register a new action for the user menu
 *
 * @param icon The icon class or an SVG string
 * @param text The text to display
 * @param action The function to run
 * @param enabled Returns true if the action is enabled for the user
 */
function registerAction(icon: UserAction['icon'], text: UserAction['text'], action: UserAction['action'], enabled?: UserAction['enabled']) {
	externalActions.value.push({
		icon,
		text,
		action,
		enabled,
	})
	return externalActions.value
}

// init the OCA.Settings.UserList object
window.OCA = window.OCA ?? {}
window.OCA.Settings = window.OCA.Settings ?? {}
const settingsNamespace = window.OCA.Settings as { UserList?: { registerAction?: typeof registerAction } }
settingsNamespace.UserList = settingsNamespace.UserList ?? {}
// and add the registerAction method
settingsNamespace.UserList.registerAction = registerAction
emit('settings:user-management:loaded')
</script>

<template>
	<NcContent appName="settings">
		<UserManagementNavigation />
		<NcAppContent :class="$style.appContent" :pageHeading="pageHeading">
			<UserList
				:selectedGroup="selectedGroup"
				:externalActions="externalActions" />
		</NcAppContent>
	</NcContent>
</template>

<style module>
.appContent {
	/* Virtual list needs to be full height and is scrollable */
	display: flex;
	overflow: hidden;
	flex-direction: column;
	max-height: 100%;
}
</style>
