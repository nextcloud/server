<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<span
		role="img"
		:aria-label="title"
		:title="title"
		class="app-score__wrapper">
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

<script lang="ts">
import { mdiStar, mdiStarHalfFull, mdiStarOutline } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

export default defineComponent({
	name: 'AppScore',
	components: {
		NcIconSvgWrapper,
	},

	props: {
		score: {
			type: Number,
			required: true,
		},
	},

	setup() {
		return {
			mdiStar,
			mdiStarHalfFull,
			mdiStarOutline,
		}
	},

	computed: {
		title() {
			const appScore = (this.score * 5).toFixed(1)
			return t('settings', 'Community rating: {score}/5', { score: appScore })
		},

		fullStars() {
			return Math.floor(this.score * 5 + 0.25)
		},

		emptyStars() {
			return Math.min(Math.floor((1 - this.score) * 5 + 0.25), 5 - this.fullStars)
		},

		hasHalfStar() {
			return (this.fullStars + this.emptyStars) < 5
		},
	},
})
</script>

<style scoped>
.app-score__wrapper {
	display: inline-flex;
	color: var(--color-favorite, #a08b00);

	> * {
		vertical-align: text-bottom;
	}
}
</style>
