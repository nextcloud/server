<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcPopover :shown="opened"
		@show="opened = true"
		@hide="opened = false">
		<template #trigger>
			<slot ref="popoverTrigger" name="trigger" />
		</template>
		<div class="searchable-list__wrapper">
			<NcTextField :value.sync="searchTerm"
				:label="labelText"
				trailing-button-icon="close"
				:show-trailing-button="searchTerm !== ''"
				@update:value="searchTermChanged"
				@trailing-button-click="clearSearch">
				<Magnify :size="20" />
			</NcTextField>
			<ul v-if="filteredList.length > 0" class="searchable-list__list">
				<li v-for="element in filteredList"
					:key="element.id"
					:title="element.displayName"
					role="button">
					<NcButton alignment="start"
						type="tertiary"
						:wide="true"
						@click="itemSelected(element)">
						<template #icon>
							<NcAvatar v-if="element.isUser" :user="element.user" :show-user-status="false" />
							<NcAvatar v-else
								:is-no-user="true"
								:display-name="element.displayName"
								:show-user-status="false" />
						</template>
						{{ element.displayName }}
					</NcButton>
				</li>
			</ul>
			<div v-else class="searchable-list__empty-content">
				<NcEmptyContent :name="emptyContentText">
					<template #icon>
						<AlertCircleOutline />
					</template>
				</NcEmptyContent>
			</div>
		</div>
	</NcPopover>
</template>

<script>
import { NcPopover, NcTextField, NcAvatar, NcEmptyContent, NcButton } from '@nextcloud/vue'

import AlertCircleOutline from 'vue-material-design-icons/AlertCircleOutline.vue'
import Magnify from 'vue-material-design-icons/Magnify.vue'

export default {
	name: 'SearchableList',

	components: {
		NcPopover,
		NcTextField,
		Magnify,
		AlertCircleOutline,
		NcAvatar,
		NcEmptyContent,
		NcButton,
	},

	props: {
		labelText: {
			type: String,
			default: 'this is a label',
		},

		searchList: {
			type: Array,
			required: true,
		},

		emptyContentText: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			opened: false,
			error: false,
			searchTerm: '',
		}
	},

	computed: {
		filteredList() {
			return this.searchList.filter((element) => {
				if (!this.searchTerm.toLowerCase().length) {
					return true
				}
				return ['displayName'].some(prop => element[prop].toLowerCase().includes(this.searchTerm.toLowerCase()))
			})
		},
	},

	methods: {
		clearSearch() {
			this.searchTerm = ''
		},
		itemSelected(element) {
			this.$emit('item-selected', element)
			this.clearSearch()
			this.opened = false
		},
		searchTermChanged(term) {
			this.$emit('search-term-change', term)
		},
	},
}
</script>

<style lang="scss" scoped>
.searchable-list {
	&__wrapper {
		padding: calc(var(--default-grid-baseline) * 3);
		display: flex;
		flex-direction: column;
		align-items: center;
		width: 250px;
	}

	&__list {
		width: 100%;
		max-height: 284px;
		overflow-y: auto;
		margin-top: var(--default-grid-baseline);
		padding: var(--default-grid-baseline);

		:deep(.button-vue) {
			border-radius: var(--border-radius-large) !important;
			span {
				font-weight: initial;
			}
		}
	}

	&__empty-content {
		margin-top: calc(var(--default-grid-baseline) * 3);
	}
}
</style>
