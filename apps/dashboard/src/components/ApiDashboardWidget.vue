<!--
  - @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @author Richard Steinmetz <richard@steinmetz.cloud>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation, either version 3 of the License, or
  - (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<NcDashboardWidget :items="items"
		:show-more-label="showMoreLabel"
		:show-more-url="showMoreUrl"
		:loading="loading"
		:show-items-and-empty-content="!!halfEmptyContentMessage"
		:half-empty-content-message="halfEmptyContentMessage">
		<template #default="{ item }">
			<NcDashboardWidgetItem :target-url="item.link"
				:overlay-icon-url="item.overlayIconUrl ? item.overlayIconUrl : ''"
				:main-text="item.title"
				:sub-text="item.subtitle">
				<template #avatar>
					<template v-if="item.iconUrl">
						<NcAvatar :size="44" :url="item.iconUrl" />
					</template>
				</template>
			</NcDashboardWidgetItem>
		</template>
		<template #empty-content>
			<NcEmptyContent v-if="items.length === 0"
				:description="emptyContentMessage">
				<template #icon>
					<CheckIcon v-if="emptyContentMessage" :size="65" />
				</template>
				<template #action>
					<NcButton v-if="setupButton" :href="setupButton.link">
						{{ setupButton.text }}
					</NcButton>
				</template>
			</NcEmptyContent>
		</template>
	</NcDashboardWidget>
</template>

<script>
import {
	NcAvatar,
	NcDashboardWidget,
	NcDashboardWidgetItem,
	NcEmptyContent,
	NcButton,
} from '@nextcloud/vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'

export default {
	name: 'ApiDashboardWidget',
	components: {
		NcAvatar,
		NcDashboardWidget,
		NcDashboardWidgetItem,
		NcEmptyContent,
		NcButton,
		CheckIcon,
	},
	props: {
		widget: {
			type: [Object, undefined],
			default: undefined,
		},
		data: {
			type: [Object, undefined],
			default: undefined,
		},
		loading: {
			type: Boolean,
			required: true,
		},
	},
	computed: {
		/** @return {object[]} */
		items() {
			return this.data?.items ?? []
		},

		/** @return {string} */
		emptyContentMessage() {
			return this.data?.emptyContentMessage ?? ''
		},

		/** @return {string} */
		halfEmptyContentMessage() {
			return this.data?.halfEmptyContentMessage ?? ''
		},

		/** @return {object|undefined} */
		newButton() {
			// TODO: Render new button in the template
			// I couldn't find a widget that makes use of the button. Furthermore, there is no convenient
			// way to render such a button using the official widget component.
			return this.widget?.buttons?.find(button => button.type === 'new')
		},

		/** @return {object|undefined} */
		moreButton() {
			return this.widget?.buttons?.find(button => button.type === 'more')
		},

		/** @return {object|undefined} */
		setupButton() {
			return this.widget?.buttons?.find(button => button.type === 'setup')
		},

		/** @return {string|undefined} */
		showMoreLabel() {
			return this.moreButton?.text
		},

		/** @return {string|undefined} */
		showMoreUrl() {
			return this.moreButton?.link
		},
	},
}
</script>

<style lang="scss" scoped>
</style>
