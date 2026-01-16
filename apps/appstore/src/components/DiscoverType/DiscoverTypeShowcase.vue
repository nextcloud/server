<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<section
		ref="container"
		class="app-discover-showcase"
		:class="{
			'app-discover-showcase--small': isSmallWidth,
			'app-discover-showcase--extra-small': isExtraSmallWidth,
		}">
		<h3 v-if="translatedHeadline">
			{{ translatedHeadline }}
		</h3>
		<ul class="app-discover-showcase__list">
			<li
				v-for="(item, index) of content"
				:key="'id' in item ? item.id : index"
				class="app-discover-showcase__item">
				<DiscoverTypePost
					v-if="item.type === 'post'"
					v-bind="item"
					inline />
				<DiscoverTypeApp v-else-if="item.type === 'app'" :model-value="item" />
			</li>
		</ul>
	</section>
</template>

<script setup lang="ts">
import type { PropType } from 'vue'
import type { IAppDiscoverShowcase } from '../../apps-discover.d.ts'

import { useElementSize } from '@vueuse/core'
import { computed, ref } from 'vue'
import DiscoverTypeApp from './DiscoverTypeApp.vue'
import DiscoverTypePost from './DiscoverTypePost.vue'
import { useLocalizedValue } from '../../composables/useGetLocalizedValue.ts'
import { commonAppDiscoverProps } from './common.ts'

const props = defineProps({
	...commonAppDiscoverProps,

	/**
	 * The content of the carousel
	 */
	content: {
		type: Array as PropType<IAppDiscoverShowcase['content']>,
		required: true,
	},
})

const translatedHeadline = useLocalizedValue(computed(() => props.headline))

/**
 * Make the element responsive based on the container width to also handle open navigation or sidebar
 */
const container = ref<HTMLElement>()
const { width: containerWidth } = useElementSize(container)
const isSmallWidth = computed(() => containerWidth.value < 768)
const isExtraSmallWidth = computed(() => containerWidth.value < 512)
</script>

<style scoped lang="scss">
$item-gap: calc(var(--default-clickable-area, 44px) / 2);

h3 {
	font-size: 24px;
	font-weight: 600;
	margin-block: 0 1em;
}

.app-discover-showcase {
	&__list {
		list-style: none;

		display: flex;
		flex-wrap: wrap;
		gap: $item-gap;
	}

	&__item {
		display: flex;
		align-items: stretch;

		position: relative;
		width: calc(33% - $item-gap);
	}
}

.app-discover-showcase--small {
	.app-discover-showcase__item {
		width: calc(50% - $item-gap);
	}
}

.app-discover-showcase--extra-small {
	.app-discover-showcase__item {
		width: 100%;
	}
}
</style>
