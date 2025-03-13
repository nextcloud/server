<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppContent :page-heading="pageHeading">
		<UserList :external-actions="externalActions"
			:selected-group="selectedGroupDecoded"
			:view="currentView" />
	</NcAppContent>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { emit } from '@nextcloud/event-bus'
import { defineComponent } from 'vue'

import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import UserList from '../components/UserList.vue'

export default defineComponent({
	name: 'UserManagement',

	components: {
		NcAppContent,
		UserList,
	},

	data() {
		return {
			// temporary value used for multiselect change
			externalActions: [],
		}
	},

	computed: {
		currentView() {
			return this.$route.params.view ?? 'all'
		},

		pageHeading() {
			if (this.currentView === 'all') {
				return t('settings', 'All accounts')
			} else if (this.currentView === 'recent') {
				return t('settings', 'Recently active accounts')
			} else if (this.currentView === 'disabled') {
				return t('settings', 'Disabled acounts')
			} else {
				if (this.selectedGroupDecoded === 'admin') {
					return t('settings', 'Admins')
				}
				return t('settings', 'Account group: {group}', { group: this.selectedGroupDecoded })
			}
		},

		selectedGroup() {
			return this.$route.params.selectedGroup
		},

		selectedGroupDecoded() {
			return this.selectedGroup ? decodeURIComponent(this.selectedGroup) : null
		},
	},

	beforeMount() {
		this.$store.commit('initGroups', {
			groups: this.$store.getters.getServerData.groups,
			orderBy: this.$store.getters.getServerData.sortGroups,
			userCount: this.$store.getters.getServerData.userCount,
		})
		this.$store.dispatch('getPasswordPolicyMinLength')
	},

	created() {
		// init the OCA.Settings.UserList object
		window.OCA = window.OCA ?? {}
		window.OCA.Settings = window.OCA.Settings ?? {}
		window.OCA.Settings.UserList = window.OCA.Settings.UserList ?? {}
		// and add the registerAction method
		window.OCA.Settings.UserList.registerAction = this.registerAction
		emit('settings:user-management:loaded')
	},

	methods: {
		t,

		/**
		 * Register a new action for the user menu
		 *
		 * @param {string} icon the icon class
		 * @param {string} text the text to display
		 * @param {Function} action the function to run
		 * @param {(user: Record<string, unknown>) => boolean} enabled return true if the action is enabled for the user
		 * @return {Array}
		 */
		registerAction(icon, text, action, enabled) {
			this.externalActions.push({
				icon,
				text,
				action,
				enabled,
			})
			return this.externalActions
		},
	},
})
</script>

<style lang="scss" scoped>
.app-content {
	// Virtual list needs to be full height and is scrollable
	display: flex;
	overflow: hidden;
	flex-direction: column;
	max-height: 100%;
}
</style>
