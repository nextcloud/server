<!--
 - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<NcDashboardWidget :items="items"
		:show-more-label="showMoreLabel"
		:show-more-url="showMoreUrl"
		:loading="loading"
		:show-items-and-empty-content="!!halfEmptyContentMessage"
		:half-empty-content-message="halfEmptyContentMessage">
		<template #default="{ item }">
			<ApiDashboardWidgetItem :item="item" :icon-size="iconSize" :rounded-icons="widget.item_icons_round" />
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
	NcDashboardWidget,
	NcEmptyContent,
	NcButton,
} from '@nextcloud/vue'
import CheckIcon from 'vue-material-design-icons/Check.vue'
import ApiDashboardWidgetItem from './ApiDashboardWidgetItem.vue'

export default {
	name: 'ApiDashboardWidget',
	components: {
		ApiDashboardWidgetItem,
		CheckIcon,
		NcDashboardWidget,
		NcEmptyContent,
		NcButton,
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
	data() {
		return {
			iconSize: 44,
		}
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
	mounted() {
		const size = window.getComputedStyle(document.body).getPropertyValue('--default-clickable-area')
		const numeric = Number.parseFloat(size)
		this.iconSize = Number.isNaN(numeric) ? 44 : numeric
	},
}
</script>
