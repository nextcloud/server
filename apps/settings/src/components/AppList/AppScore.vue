<!--
	- @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
	-
	- @author Julius Härtl <jus@bitgrid.net>
	-
	- @license GNU AGPL version 3 or any later version
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
	<span role="img"
		:aria-label="title"
		:title="title"
		class="app-score__wrapper">
		<NcIconSvgWrapper v-for="index in fullStars"
			:key="`full-star-${index}`"
			:path="mdiStar" />
		<NcIconSvgWrapper v-if="hasHalfStar" :path="mdiStarHalfFull" />
		<NcIconSvgWrapper v-for="index in emptyStars"
			:key="`empty-star-${index}`"
			:path="mdiStarOutline" />
	</span>
</template>
<script lang="ts">
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import { mdiStar, mdiStarHalfFull, mdiStarOutline } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

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
	color: #a08b00;
	> * {
		min-width: fit-content;
		min-height: fit-content;
		vertical-align: text-bottom;
	}
}
</style>
