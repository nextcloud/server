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
			:model-value="currentValue"
			label="displayname"
			@search="searchAsync"
			@input="update" />
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'

import axios from '@nextcloud/axios'
import NcSelect from '@nextcloud/vue/components/NcSelect'

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
		modelValue: {
			type: String,
			default: '',
		},
		check: {
			type: Object,
			default: () => { return {} },
		},
	},
	emits: ['update:model-value'],
	data() {
		return {
			groups,
			status,
			newValue: '',
		}
	},
	computed: {
		currentValue: {
			get() {
				return this.groups.find(group => group.id === this.newValue) || null
			},
			set(value) {
				this.newValue = value
			},
		},
	},
	watch: {
		modelValue() {
			this.updateInternalValue()
		},
	},
	async mounted() {
		// If empty, load first chunk of groups
		if (this.groups.length === 0) {
			await this.searchAsync('')
		}
		// If a current group is set but not in our list of groups then search for that group
		if (this.currentValue === null && this.newValue) {
			await this.searchAsync(this.newValue)
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
		updateInternalValue() {
			this.newValue = this.modelValue
		},
		addGroup(group) {
			const index = this.groups.findIndex((item) => item.id === group.id)
			if (index === -1) {
				this.groups.push(group)
			}
		},
		update(value) {
			this.newValue = value.id
			this.$emit('update:model-value', this.newValue)
		},
	},
}
</script>
<style scoped>
.v-select {
	width: 100%;
}
</style>
