<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'

import { mdiStar, mdiStarHalfFull, mdiStarOutline } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

const { app } = defineProps<{
	app: IAppstoreApp | IAppstoreExApp
}>()

const isShown = computed(() => app.ratingNumOverall && app.ratingNumOverall > 5)
const score = computed(() => app.ratingOverall ?? 4)

const title = computed(() => {
	const appScore = (score.value * 5).toFixed(1)
	return t('appstore', 'Community rating: {score}/5', { score: appScore })
})

const fullStars = computed(() => Math.floor(score.value * 5 + 0.25))
const emptyStars = computed(() => Math.min(Math.floor((1 - score.value) * 5 + 0.25), 5 - fullStars.value))
const hasHalfStar = computed(() => (fullStars.value + emptyStars.value) < 5)
</script>

<template>
	<span
		v-if="isShown"
		role="img"
		:aria-label="title"
		:title="title"
		:class="$style.badgeAppScore">
		<NcIconSvgWrapper
			v-for="index in fullStars"
			:key="`full-star-${index}`"
			:path="mdiStar"
			inline />
		<NcIconSvgWrapper v-if="hasHalfStar" :path="mdiStarHalfFull" inline />
		<NcIconSvgWrapper
			v-for="index in emptyStars"
			:key="`empty-star-${index}`"
			:path="mdiStarOutline"
			inline />
	</span>
</template>

<style module>
.badgeAppScore {
	display: inline-flex;
	color: var(--color-favorite, #a08b00);

	> * {
		vertical-align: text-bottom;
	}
}
</style>
