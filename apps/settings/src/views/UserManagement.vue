<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppContent :page-heading="pageHeading">
		<UserList :selected-group="selectedGroupDecoded"
			:selected-section="selectedSection"
			:external-actions="externalActions" />
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

	props: {
		group: {
			type: String,
			default: null,
		},
		sectionGroup: {
			type: String,
			default: null,
		},
	},

	data() {
		return {
			// temporary value used for multiselect change
			externalActions: [],
		}
	},

	computed: {
		pageHeading() {
			if (this.selectedGroupDecoded === null) {
				return t('settings', 'All accounts')
			}
			const matchHeading = {
				admin: t('settings', 'Admins'),
				disabled: t('settings', 'Disabled accounts'),
			}
			return matchHeading[this.selectedGroupDecoded] ?? t('settings', 'Account group: {group}', { group: this.selectedGroupDecoded })
		},

		selectedGroup() {
			if (this.sectionGroup === 'admin') {
				return this.sectionGroup
			}
			return this.group
		},

		selectedGroupDecoded() {
			return this.selectedGroup ? decodeURIComponent(this.selectedGroup) : null
		},

		selectedSection() {
			if (this.sectionGroup === 'admin') {
				return null
			}
			return this.sectionGroup
		},
	},

	beforeMount() {
		this.$store.commit('initSectionGroups', {
			sectionGroups: this.$store.getters.getServerData.sectionGroups,
		})
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
