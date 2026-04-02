<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user-form__item user-form__managers">
		<NcSelectUsers
			:modelValue="managerModel"
			class="user-form__select"
			:input-label="t('settings', 'Manager')"
			:placeholder="t('settings', 'Search for a manager…')"
			:options="managerOptions"
			:loading="loading"
			@update:modelValue="onManagerChange"
			@search="searchUserManager" />
	</div>
</template>

<script>
import NcSelectUsers from '@nextcloud/vue/components/NcSelectUsers'
import logger from '../../logger.ts'

export default {
	name: 'UserFormManager',

	components: {
		NcSelectUsers,
	},

	inject: ['formData'],

	data() {
		return {
			possibleManagers: [],
			loading: false,
		}
	},

	computed: {
		/** Map internal formData.manager to NcSelectUsersModel shape */
		managerModel() {
			const m = this.formData.manager
			if (!m) {
				return null
			}
			return {
				id: typeof m === 'object' ? m.id : m,
				displayName: typeof m === 'object' ? (m.displayname ?? m.id) : m,
			}
		},

		/** Map API users to NcSelectUsersModel shape */
		managerOptions() {
			return this.possibleManagers.map((u) => ({
				id: u.id,
				displayName: u.displayname ?? u.id,
				subname: u.email ?? '',
			}))
		},
	},

	mounted() {
		this.searchUserManager('')
	},

	methods: {
		/** Map NcSelectUsersModel back to internal formData shape */
		onManagerChange(value) {
			this.formData.manager = value
				? { id: value.id, displayname: value.displayName }
				: ''
		},

		async searchUserManager(query) {
			this.loading = true
			try {
				const response = await this.$store.dispatch('searchUsers', {
					offset: 0,
					limit: 10,
					search: query,
				})
				const users = response?.data ? Object.values(response.data.ocs.data.users) : []
				this.possibleManagers = users
			} catch (error) {
				logger.error('Failed to search user managers', { error })
			} finally {
				this.loading = false
			}
		},
	},
}
</script>
