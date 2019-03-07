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
		:class="{zoomed: zoomRatio !== 1}"
		:src="data"
		:style="{
			height: zoomHeight + 'px',
			width: zoomWidth + 'px',
			marginTop: shiftY + 'px',
			marginLeft: shiftX + 'px'
		}"
		@load="updateImgSize"
		@wheel="updateZoom"
		@dblclick="resetZoom">
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
	data() {
		return {
			shiftX: 0,
			shiftY: 0,
			zoomRatio: 1
		}
	},
	computed: {
		zoomHeight() {
			return Math.round(this.height * this.zoomRatio)
		},
		zoomWidth() {
			return Math.round(this.width * this.zoomRatio)
		}
	},
	asyncComputed: {
		data() {
			if (this.mime !== 'image/svg+xml') {
				return this.path
			}
			return this.getBase64FromImage()
		}
	},
	watch: {
		active: function(val, old) {
			// the item was hidden before and is now the current view
			if (val === true && old === false) {
				this.resetZoom()
			}
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
		},

		/**
		 * Handle zooming
		 *
		 * @param {Event} event the scroll event
		 */
		updateZoom(event) {
			event.stopPropagation()
			event.preventDefault()

			// scrolling position relative to the image
			const scrollX = event.clientX - this.$el.x - (this.width * this.zoomRatio / 2)
			const scrollY = event.clientY - this.$el.y - (this.height * this.zoomRatio / 2)
			const scrollPercX = Math.round(scrollX / (this.width * this.zoomRatio) * 100) / 100
			const scrollPercY = Math.round(scrollY / (this.height * this.zoomRatio) * 100) / 100
			const isZoomIn = event.deltaY < 0

			const newZoomRatio = isZoomIn
				? Math.min(this.zoomRatio + 0.2, 5)		// prevent too big zoom
				: Math.max(this.zoomRatio - 0.2, 1)		// prevent too small zoom

			// calc how much the img grow from its current size
			// and adjust the margin accordingly
			const growX = this.width * newZoomRatio - this.width * this.zoomRatio
			const growY = this.height * newZoomRatio - this.height * this.zoomRatio

			// compensate for existing margins
			this.shiftX = this.shiftX + Math.round(-scrollPercX * growX)
			this.shiftY = this.shiftY + Math.round(-scrollPercY * growY)
			this.zoomRatio = newZoomRatio
		},
		resetZoom() {
			this.zoomRatio = 1
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
	// animate zooming/resize
	transition: height 100ms ease,
		width 100ms ease,
		margin-top 100ms ease,
		margin-left 100ms ease;
	&:hover {
		background-image: linear-gradient(45deg, #{$checkered-color} 25%, transparent 25%),
			linear-gradient(45deg, transparent 75%, #{$checkered-color} 75%),
			linear-gradient(45deg, transparent 75%, #{$checkered-color} 75%),
			linear-gradient(45deg, #{$checkered-color} 25%, #fff 25%);
		background-size: 2 * $checkered-size 2 * $checkered-size;
		background-position: 0 0, 0 0, -#{$checkered-size} -#{$checkered-size}, $checkered-size $checkered-size;
	}
	&.zoomed {
		position: absolute;
		max-height: none;
		max-width: none;
		z-index: 10000;
	}
}
</style>
