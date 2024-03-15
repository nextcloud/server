<template>
	<div class="app-discover">
		<NcEmptyContent v-if="hasError"
			:name="t('settings', 'Nothing to show')"
			:description="t('settings', 'Could not load section content from app store.')">
			<template #icon>
				<NcIconSvgWrapper :path="mdiEyeOff" :size="64" />
			</template>
		</NcEmptyContent>
		<NcEmptyContent v-else-if="elements.length === 0"
			:name="t('settings', 'Loading')"
			:description="t('settings', 'Fetching the latest news…')">
			<template #icon>
				<NcLoadingIcon :size="64" />
			</template>
		</NcEmptyContent>
		<template v-else>
			<component :is="getComponent(entry.type)"
				v-for="entry, index in elements"
				:key="entry.id ?? index"
				v-bind="entry" />
		</template>
	</div>
</template>

<script setup lang="ts">
import type { IAppDiscoverElements } from '../../constants/AppDiscoverTypes.ts'

import { mdiEyeOff } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { defineAsyncComponent, defineComponent, onBeforeMount, ref } from 'vue'

import axios from '@nextcloud/axios'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

import logger from '../../logger'
import { apiTypeParser } from '../../utils/appDiscoverTypeParser.ts'

const PostType = defineAsyncComponent(() => import('./PostType.vue'))
const CarouselType = defineAsyncComponent(() => import('./CarouselType.vue'))

const hasError = ref(false)
const elements = ref<IAppDiscoverElements[]>([])

/**
 * Shuffle using the Fisher-Yates algorithm
 * @param array The array to shuffle (in place)
 */
const shuffleArray = (array) => {
	for (let i = array.length - 1; i > 0; i--) {
		const j = Math.floor(Math.random() * (i + 1));
		[array[i], array[j]] = [array[j], array[i]]
	}
	return array
}

/**
 * Load the app discover section information
 */
onBeforeMount(async () => {
	try {
		const { data } = await axios.get<Record<string, unknown>[]>(generateUrl('/settings/api/apps/discover'))
		const parsedData = data.map(apiTypeParser)
		elements.value = shuffleArray(parsedData)
	} catch (error) {
		hasError.value = true
		logger.error(error as Error)
		showError(t('settings', 'Could not load app discover section'))
	}
})

const getComponent = (type) => {
	if (type === 'post') {
		return PostType
	} else if (type === 'carousel') {
		return CarouselType
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
