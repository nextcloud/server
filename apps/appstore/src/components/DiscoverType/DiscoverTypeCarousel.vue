<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<section :aria-roledescription="t('settings', 'Carousel')" :aria-labelledby="headingId ? `${headingId}` : undefined">
		<h3 v-if="headline" :id="headingId">
			{{ translatedHeadline }}
		</h3>
		<div class="app-discover-carousel__wrapper">
			<div class="app-discover-carousel__button-wrapper">
				<NcButton
					class="app-discover-carousel__button app-discover-carousel__button--previous"
					variant="tertiary-no-background"
					:aria-label="t('settings', 'Previous slide')"
					:disabled="!hasPrevious"
					@click="currentIndex -= 1">
					<template #icon>
						<NcIconSvgWrapper :path="mdiChevronLeft" />
					</template>
				</NcButton>
			</div>

			<Transition :name="transitionName" mode="out-in">
				<DiscoverTypePost
					v-bind="shownElement"
					:key="shownElement.id ?? currentIndex"
					:aria-labelledby="`${internalId}-tab-${currentIndex}`"
					:dom-id="`${internalId}-tabpanel-${currentIndex}`"
					inline
					role="tabpanel" />
			</Transition>

			<div class="app-discover-carousel__button-wrapper">
				<NcButton
					class="app-discover-carousel__button app-discover-carousel__button--next"
					variant="tertiary-no-background"
					:aria-label="t('settings', 'Next slide')"
					:disabled="!hasNext"
					@click="currentIndex += 1">
					<template #icon>
						<NcIconSvgWrapper :path="mdiChevronRight" />
					</template>
				</NcButton>
			</div>
		</div>
		<div class="app-discover-carousel__tabs" role="tablist" :aria-label="t('settings', 'Choose slide to display')">
			<NcButton
				v-for="index of content.length"
				:id="`${internalId}-tab-${index}`"
				:key="index"
				:aria-label="t('settings', '{index} of {total}', { index, total: content.length })"
				:aria-controls="`${internalId}-tabpanel-${index}`"
				:aria-selected="`${currentIndex === (index - 1)}`"
				role="tab"
				variant="tertiary-no-background"
				@click="currentIndex = index - 1">
				<template #icon>
					<NcIconSvgWrapper :path="currentIndex === (index - 1) ? mdiCircleSlice8 : mdiCircleOutline" />
				</template>
			</NcButton>
		</div>
	</section>
</template>

<script setup lang="ts">
import type { PropType } from 'vue'
import type { IAppDiscoverCarousel } from '../../apps-discover.d.ts'

import { mdiChevronLeft, mdiChevronRight, mdiCircleOutline, mdiCircleSlice8 } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed, nextTick, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import DiscoverTypePost from './DiscoverTypePost.vue'
import { useLocalizedValue } from '../../composables/useGetLocalizedValue.ts'
import { commonAppDiscoverProps } from './common.ts'

const props = defineProps({
	...commonAppDiscoverProps,

	/**
	 * The content of the carousel
	 */
	content: {
		type: Array as PropType<IAppDiscoverCarousel['content']>,
		required: true,
	},
})

const translatedHeadline = useLocalizedValue(computed(() => props.headline))

const currentIndex = ref(Math.min(1, props.content.length - 1))
const shownElement = ref(props.content[currentIndex.value]!)
const hasNext = computed(() => currentIndex.value < (props.content.length - 1))
const hasPrevious = computed(() => currentIndex.value > 0)

const internalId = computed(() => props.id ?? (Math.random() + 1).toString(36).substring(7))
const headingId = computed(() => `${internalId.value}-h`)

const transitionName = ref('slide-in')
watch(() => currentIndex.value, (o, n) => {
	if (o < n) {
		transitionName.value = 'slide-in'
	} else {
		transitionName.value = 'slide-out'
	}

	// Wait next tick
	nextTick(() => {
		shownElement.value = props.content[currentIndex.value]!
	})
})
</script>

<style scoped lang="scss">
h3 {
	font-size: 24px;
	font-weight: 600;
	margin-block: 0 1em;
}

.app-discover-carousel {
	&__wrapper {
		display: flex;
	}

	&__button {
		color: var(--color-text-maxcontrast);
		position: absolute;
		top: calc(50% - 22px); // 50% minus half of button height

		&-wrapper {
			position: relative;
		}

		// See padding of discover section
		&--next {
			inset-inline-end: -54px;
		}
		&--previous {
			inset-inline-start: -54px;
		}
	}

	&__tabs {
		display: flex;
		flex-direction: row;
		justify-content: center;

		> * {
			color: var(--color-text-maxcontrast);
		}
	}
}
</style>

<style>
.slide-in-enter-active,
.slide-in-leave-active,
.slide-out-enter-active,
.slide-out-leave-active {
  transition: all .4s ease-out;
}

.slide-in-leave-to,
.slide-out-enter {
  opacity: 0;
  transform: translateX(50%);
}

.slide-in-enter,
.slide-out-leave-to {
  opacity: 0;
  transform: translateX(-50%);
}
</style>
