<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<div class="user-form__item">
			<NcSelect
				v-model="formData.groups"
				class="user-form__select"
				data-test="groups"
				:input-label="groupsLabel"
				:placeholder="t('settings', 'Set account groups')"
				:disabled="creatingGroup"
				:options="availableGroups"
				label="name"
				keep-open
				:multiple="true"
				:taggable="settings.isAdmin || settings.isDelegatedAdmin"
				:required="!settings.isAdmin && !settings.isDelegatedAdmin"
				:create-option="(value) => ({ id: value, name: value, isCreating: true })"
				@search="searchGroups"
				@option:created="createGroup" />
		</div>

		<div
			v-if="settings.isAdmin || settings.isDelegatedAdmin"
			class="user-form__item">
			<NcSelect
				v-model="formData.subadminGroups"
				class="user-form__select"
				:input-label="t('settings', 'Admin of the following groups')"
				:placeholder="t('settings', 'Set account as admin for …')"
				:disabled="creatingGroup"
				:options="availableSubAdminGroups"
				keep-open
				:multiple="true"
				label="name"
				@search="searchGroups" />
		</div>
	</div>
</template>

<script>
import NcSelect from '@nextcloud/vue/components/NcSelect'
import logger from '../../logger.ts'
import { searchGroups } from '../../service/groups.ts'

export default {
	name: 'UserFormGroups',

	components: {
		NcSelect,
	},

	props: {
		formData: {
			type: Object,
			required: true,
		},
	},

	data() {
		return {
			creatingGroup: false,
			promise: null,
		}
	},

	computed: {
		settings() {
			return this.$store.getters.getServerData
		},

		availableGroups() {
			const groups = (this.settings.isAdmin || this.settings.isDelegatedAdmin)
				? this.$store.getters.getSortedGroups
				: this.$store.getters.getSubAdminGroups

			return groups.filter(({ id }) => id !== '__nc_internal_recent' && id !== 'disabled')
		},

		availableSubAdminGroups() {
			return this.availableGroups.filter(({ id }) => id !== 'admin')
		},

		groupsLabel() {
			return !this.settings.isAdmin && !this.settings.isDelegatedAdmin
				? t('settings', 'Member of the following groups (required)')
				: t('settings', 'Member of the following groups')
		},
	},

	methods: {
		async searchGroups(query, toggleLoading) {
			if (!this.settings.isAdmin && !this.settings.isDelegatedAdmin) {
				return
			}
			if (this.promise) {
				this.promise.cancel()
			}
			toggleLoading(true)
			try {
				this.promise = searchGroups({ search: query, offset: 0, limit: 25 })
				const groups = await this.promise
				for (const group of groups) {
					this.$store.commit('addGroup', group)
				}
			} catch (error) {
				logger.error(t('settings', 'Failed to search groups'), { error })
			}
			this.promise = null
			toggleLoading(false)
		},

		async createGroup({ name: gid }) {
			this.creatingGroup = true
			try {
				await this.$store.dispatch('addGroup', gid)
				this.formData.groups.push({ id: gid, name: gid })
			} catch (error) {
				logger.error(t('settings', 'Failed to create group'), { error })
			}
			this.creatingGroup = false
		},
	},
}
</script>
