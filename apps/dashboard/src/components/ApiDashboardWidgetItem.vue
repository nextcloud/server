<!--
 - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<script setup lang="ts">
import { ref } from 'vue'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcDashboardWidgetItem from '@nextcloud/vue/dist/Components/NcDashboardWidgetItem.js'
import IconFile from 'vue-material-design-icons/File.vue'

defineProps({
	item: {
		type: Object,
		required: true,
	},
	iconSize: {
		type: Number,
		required: true,
	},
	roundedIcons: {
		type: Boolean,
		default: true,
	},
})

/**
 * True as soon as the image is loaded
 */
const imageLoaded = ref(false)
/**
 * True if the image failed to load and we should show a fallback
 */
const loadingImageFailed = ref(false)
</script>

<template>
	<NcDashboardWidgetItem :target-url="item.link"
		:overlay-icon-url="item.overlayIconUrl ? item.overlayIconUrl : ''"
		:main-text="item.title"
		:sub-text="item.subtitle">
		<template #avatar>
			<template v-if="item.iconUrl">
				<NcAvatar v-if="roundedIcons"
					:size="iconSize"
					:url="item.iconUrl" />
				<template v-else>
					<img v-show="!loadingImageFailed"
						alt=""
						class="api-dashboard-widget-item__icon"
						:class="{'hidden-visually': !imageLoaded }"
						:src="item.iconUrl"
						@error="loadingImageFailed = true"
						@load="imageLoaded = true">
					<!-- Placeholder while the image is loaded and also the fallback if the URL is broken -->
					<IconFile v-if="!imageLoaded"
						:size="iconSize" />
				</template>
			</template>
		</template>
	</NcDashboardWidgetItem>
</template>

<style scoped>
.api-dashboard-widget-item__icon {
	height: var(--default-clickable-area);
	width: var(--default-clickable-area);
}
</style>
