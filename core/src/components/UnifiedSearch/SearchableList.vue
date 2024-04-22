<!--
  - @copyright 2023 Marco Ambrosini <marcoambrosini@proton.me>
  -
  - @author Marco Ambrosini <marcoambrosini@proton.me>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
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
							<NcAvatar v-if="element.isUser" :user="element.user"  :show-user-status="false" :hide-favorite="false" />
							<NcAvatar v-else :url="element.avatar"  :show-user-status="false" :hide-favorite="false" />
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
			console.debug('Users (search)', this.filteredList) // WIP, would remove
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
