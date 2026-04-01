<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="user-form__item user-form__managers">
		<NcSelect
			v-model="formData.manager"
			class="user-form__select"
			:input-label="t('settings', 'Manager')"
			:placeholder="t('settings', 'Set line manager')"
			:options="possibleManagers"
			:user-select="true"
			label="displayname"
			:clearable="true"
			:loading="loading"
			@open="onOpen"
			@search="searchUserManager" />
	</div>
</template>

<script>
import NcSelect from '@nextcloud/vue/components/NcSelect'

export default {
	name: 'UserFormManager',

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
			possibleManagers: [],
			loading: false,
			fetched: false,
		}
	},

	methods: {
		async onOpen() {
			if (!this.fetched) {
				this.loading = true
				await this.searchUserManager()
				this.loading = false
				this.fetched = true
			}
		},

		async searchUserManager(query) {
			const response = await this.$store.dispatch('searchUsers', {
				offset: 0,
				limit: 10,
				search: query,
			})
			const users = response?.data ? Object.values(response.data.ocs.data.users) : []
			if (users.length > 0) {
				this.possibleManagers = users
			}
		},
	},
}
</script>
