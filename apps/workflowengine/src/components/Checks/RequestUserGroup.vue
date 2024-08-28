<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div>
		<NcSelect :aria-label-combobox="t('workflowengine', 'Select groups')"
			:aria-label-listbox="t('workflowengine', 'Groups')"
			:clearable="false"
			:loading="status.isLoading && groups.length === 0"
			:placeholder="t('workflowengine', 'Type to search for group …')"
			:options="groups"
			:value="currentValue"
			label="displayname"
			@search="searchAsync"
			@input="(value) => $emit('input', value.id)" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'

import axios from '@nextcloud/axios'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'

const groups = []
const status = {
	isLoading: false,
}

export default {
	name: 'RequestUserGroup',
	components: {
		NcSelect,
	},
	props: {
		value: {
			type: String,
			default: '',
		},
		check: {
			type: Object,
			default: () => { return {} },
		},
	},
	data() {
		return {
			groups,
			status,
		}
	},
	computed: {
		currentValue() {
			return this.groups.find(group => group.id === this.value) || null
		},
	},
	async mounted() {
		// If empty, load first chunk of groups
		if (this.groups.length === 0) {
			await this.searchAsync('')
		}
		// If a current group is set but not in our list of groups then search for that group
		if (this.currentValue === null && this.value) {
			await this.searchAsync(this.value)
		}
	},
	methods: {
		t,

		searchAsync(searchQuery) {
			if (this.status.isLoading) {
				return
			}

			this.status.isLoading = true
			return axios.get(generateOcsUrl('cloud/groups/details?limit=20&search={searchQuery}', { searchQuery })).then((response) => {
				response.data.ocs.data.groups.forEach((group) => {
					this.addGroup({
						id: group.id,
						displayname: group.displayname,
					})
				})
				this.status.isLoading = false
			}, (error) => {
				console.error('Error while loading group list', error.response)
			})
		},
		addGroup(group) {
			const index = this.groups.findIndex((item) => item.id === group.id)
			if (index === -1) {
				this.groups.push(group)
			}
		},
	},
}
</script>
<style scoped>
.v-select {
	width: 100%;
}
</style>
