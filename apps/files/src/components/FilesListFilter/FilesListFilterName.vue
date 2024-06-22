<template>
	<NcTextField :value.sync="query"
		:label="t('files', 'Filename')"
		show-trailing-button
		:trailing-button-label="t('files', 'Clear filter')"
		@trailing-button-click="resetFilter" />
</template>

<script lang="ts">
import type { Node } from '@nextcloud/files'

import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import debounce from 'debounce'
import useFilesFilter from '../../composables/useFilesFilter.ts'

import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

export default defineComponent({
	name: 'FilesListFilterName',

	components: {
		NcTextField,
	},

	props: {
	},

	setup() {
		return {
			...useFilesFilter(),
		}
	},

	data() {
		return {
			query: '',
		}
	},

	computed: {
		debouncedSetFilter() {
			return debounce(this.setFilter, 200)
		},
	},

	watch: {
		query(newValue: string) {
			if (!newValue) {
				// Remove filter if no query is set
				this.deleteFilter('files-filter-name')
			} else {
				this.debouncedSetFilter()
			}
		},
	},

	mounted() {
		subscribe('nextcloud:unified-search.search', this.onSearch)
		subscribe('nextcloud:unified-search.reset', this.resetFilter)
	},

	beforeDestroy() {
		this.deleteFilter('files-filter-name')

		unsubscribe('nextcloud:unified-search.search', this.onSearch)
		unsubscribe('nextcloud:unified-search.reset', this.resetFilter)
	},

	methods: {
		t,

		setFilter() {
			this.addFilter({
				id: 'files-filter-name',
				filter: (node: Node) => {
					if (!this.query) {
						return true
					}

					const query = this.query.toLowerCase()
					return (node.attributes.displayName ?? node.basename).toLowerCase().includes(query)
				},
			})
		},

		onSearch({ query }: { query: string }) {
			this.query = query ?? ''
		},

		resetFilter() {
			this.query = ''
		},
	},
})
</script>
