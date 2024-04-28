<!--
  - @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
  -
  - @author Ferdinand Thiessen <opensource@fthiessen.de>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<section ref="container"
		class="app-discover-showcase"
		:class="{
			'app-discover-showcase--small': isSmallWidth,
			'app-discover-showcase--extra-small': isExtraSmallWidth,
		}">
		<h3 v-if="translatedHeadline">
			{{ translatedHeadline }}
		</h3>
		<ul class="app-discover-showcase__list">
			<li v-for="(item, index) of content"
				:key="item.id ?? index"
				class="app-discover-showcase__item">
				<PostType v-if="item.type === 'post'"
					v-bind="item"
					inline />
				<AppType v-else-if="item.type === 'app'" :model-value="item" />
			</li>
		</ul>
	</section>
</template>

<script lang="ts">
import type { PropType } from 'vue'
import type { IAppDiscoverShowcase } from '../../constants/AppDiscoverTypes.ts'

import { translate as t } from '@nextcloud/l10n'
import { useElementSize } from '@vueuse/core'
import { computed, defineComponent, ref } from 'vue'
import { commonAppDiscoverProps } from './common.ts'
import { useLocalizedValue } from '../../composables/useGetLocalizedValue.ts'

import AppType from './AppType.vue'
import PostType from './PostType.vue'

export default defineComponent({
	name: 'ShowcaseType',

	components: {
		AppType,
		PostType,
	},

	props: {
		...commonAppDiscoverProps,

		/**
		 * The content of the carousel
		 */
		content: {
			type: Array as PropType<IAppDiscoverShowcase['content']>,
			required: true,
		},
	},

	setup(props) {
		const translatedHeadline = useLocalizedValue(computed(() => props.headline))

		/**
		 * Make the element responsive based on the container width to also handle open navigation or sidebar
		 */
		const container = ref<HTMLElement>()
		const { width: containerWidth } = useElementSize(container)
		const isSmallWidth = computed(() => containerWidth.value < 768)
		const isExtraSmallWidth = computed(() => containerWidth.value < 512)

		return {
			t,

			container,
			isSmallWidth,
			isExtraSmallWidth,
			translatedHeadline,
		}
	},
})
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
