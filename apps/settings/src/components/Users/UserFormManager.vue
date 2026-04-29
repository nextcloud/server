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
			searchTimeout: null,
		}
	},

	computed: {
		/**
		 * Map internal formData.manager to NcSelectUsersModel shape.
		 * Cached to keep object identity stable across reads, so NcSelectUsers
		 * doesn't see a fresh :modelValue on every parent re-render.
		 */
		managerModel() {
			const m = this.formData.manager
			if (!m) {
				return null
			}
			const id = typeof m === 'object' ? m.id : m
			const displayName = typeof m === 'object' ? (m.displayname ?? m.id) : m
			if (this._managerModelCache?.id === id && this._managerModelCache?.displayName === displayName) {
				return this._managerModelCache
			}
			this._managerModelCache = { id, displayName }
			return this._managerModelCache
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

	beforeDestroy() {
		clearTimeout(this.searchTimeout)
	},

	methods: {
		/** Map NcSelectUsersModel back to internal formData shape */
		onManagerChange(value) {
			this.formData.manager = value
				? { id: value.id, displayname: value.displayName }
				: ''
		},

		/** Debounce keystrokes so a 10-char query produces 1-2 requests, not 10. */
		searchUserManager(query) {
			clearTimeout(this.searchTimeout)
			this.searchTimeout = setTimeout(() => this.fetchManagers(query), 200)
		},

		async fetchManagers(query) {
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
