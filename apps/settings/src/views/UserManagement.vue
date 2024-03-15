<!--
  - @copyright Copyright (c) 2018 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<NcAppContent :page-heading="pageHeading">
		<UserList :selected-group="selectedGroupDecoded"
			:external-actions="externalActions" />
	</NcAppContent>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
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
		pageHeading() {
			if (this.selectedGroupDecoded === null) {
				return t('settings', 'Active accounts')
			}
			const matchHeading = {
				admin: t('settings', 'Admins'),
				disabled: t('settings', 'Disabled accounts'),
			}
			return matchHeading[this.selectedGroupDecoded] ?? t('settings', 'Account group: {group}', { group: this.selectedGroupDecoded })
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
	},

	methods: {
		t,

		/**
		 * Register a new action for the user menu
		 *
		 * @param {string} icon the icon class
		 * @param {string} text the text to display
		 * @param {Function} action the function to run
		 * @return {Array}
		 */
		registerAction(icon, text, action) {
			this.externalActions.push({
				icon,
				text,
				action,
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
