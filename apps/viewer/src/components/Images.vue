<!--
 - @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 -
 - @author John Molakvoæ <skjnldsv@protonmail.com>
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
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -
 -->

<template>
	<img
		:src="data"
		:style="{
			height: height + 'px',
			width: width + 'px'
		}"
		@load="updateImgSize">
</template>

<script>
import mime from 'Mixins/Mime'
import axios from 'axios'
import Vue from 'vue'
import debounce from 'debounce'
import AsyncComputed from 'vue-async-computed'

Vue.use(AsyncComputed)

export default {
	name: 'Images',
	mixins: [
		mime
	],
	asyncComputed: {
		data() {
			if (this.mime !== 'image/svg+xml') {
				return this.path
			}
			return this.getBase64FromImage()
		}
	},
	mounted() {
		window.addEventListener('resize', debounce(() => {
			this.updateImgSize()
		}, 100))
	},
	methods: {
		// Updates the dimensions of the modal
		updateImgSize() {
			const naturalHeight = this.$el.naturalHeight
			const naturalWidth = this.$el.naturalWidth
			// displaying tiny images makes no sense,
			// let's try to an least dispay them at 100x100
			this.updateHeightWidth(
				Math.max(naturalHeight, 100),
				Math.max(naturalWidth, 100)
			)

			this.doneLoading()
		},

		/**
		 * Manually retrieve the path and return its base64
		 *
		 * @returns {String}
		 */
		async getBase64FromImage() {
			const file = await axios.get(this.path)
			return `data:${this.mime};base64,${btoa(file.data)}`
		}
	}
}
</script>

<style scoped lang="scss">
$checkered-size: 8px;
$checkered-color: #efefef;

img {
	max-width: 100%;
	max-height: 100%;
	align-self: center;
	justify-self: center;
	&:hover {
		background-image: linear-gradient(45deg, #{$checkered-color} 25%, transparent 25%),
			linear-gradient(45deg, transparent 75%, #{$checkered-color} 75%),
			linear-gradient(45deg, transparent 75%, #{$checkered-color} 75%),
			linear-gradient(45deg, #{$checkered-color} 25%, #fff 25%);
		background-size: 2 * $checkered-size 2 * $checkered-size;
		background-position: 0 0, 0 0, -#{$checkered-size} -#{$checkered-size}, $checkered-size $checkered-size;
	}
}
</style>
