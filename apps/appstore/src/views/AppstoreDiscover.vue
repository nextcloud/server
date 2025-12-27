<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcEmptyContent
		v-if="hasError"
		:name="t('settings', 'Nothing to show')"
		:description="t('settings', 'Could not load section content from app store.')">
		<template #icon>
			<NcIconSvgWrapper :path="mdiEyeOffOutline" :size="64" />
		</template>
	</NcEmptyContent>
	<NcEmptyContent
		v-else-if="elements.length === 0"
		:name="t('settings', 'Loading')"
		:description="t('settings', 'Fetching the latest news…')">
		<template #icon>
			<NcLoadingIcon :size="64" />
		</template>
	</NcEmptyContent>
	<div v-else class="app-discover">
		<component
			:is="getComponent(entry.type)"
			v-for="entry, index in elements"
			:key="entry.id ?? index"
			v-bind="entry" />
	</div>
</template>

<script setup lang="ts">
import type { IAppDiscoverElements } from '../apps-discover.d.ts'

import { mdiEyeOffOutline } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { defineAsyncComponent, defineComponent, onBeforeMount, ref } from 'vue'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import { getDiscoverElements } from '../service/app-discover.ts'
import logger from '../utils/logger.ts'

const PostType = defineAsyncComponent(() => import('../components/DiscoverType/DiscoverTypePost.vue'))
const CarouselType = defineAsyncComponent(() => import('../components/DiscoverType/DiscoverTypeCarousel.vue'))
const ShowcaseType = defineAsyncComponent(() => import('../components/DiscoverType/DiscoverTypeShowcase.vue'))

const hasError = ref(false)
const elements = ref<IAppDiscoverElements[]>([])

/**
 * Load the app discover section information
 */
onBeforeMount(async () => {
	try {
		// Set the elements to the UI
		elements.value = await getDiscoverElements()
	} catch (error) {
		hasError.value = true
		logger.error(error as Error)
		showError(t('settings', 'Could not load app discover section'))
	}
})

/**
 * Get the component for the given type
 *
 * @param type - The type of the component
 */
function getComponent(type: IAppDiscoverElements['type']) {
	if (type === 'post') {
		return PostType
	} else if (type === 'carousel') {
		return CarouselType
	} else if (type === 'showcase') {
		return ShowcaseType
	}
	return defineComponent({
		mounted: () => logger.error('Unknown component requested ', type),
		render: (h) => h('div', t('settings', 'Could not render element')),
	})
}
</script>

<style scoped lang="scss">
.app-discover {
	max-width: 1008px; /* 900px + 2x 54px padding for the carousel controls */
	margin-inline: auto;
	padding-inline: 54px;
	/* Padding required to make last element not bound to the bottom */
	padding-block-end: var(--default-clickable-area, 44px);

	display: flex;
	flex-direction: column;
	gap: var(--default-clickable-area, 44px);
}
</style>
