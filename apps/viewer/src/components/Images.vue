<!--
 - @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 -
 - @author John Molakvoæ <skjnldsv@protonmail.com>
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
 - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 - GNU Affero General Public License for more details.
 -
 - You should have received a copy of the GNU Affero General Public License
 - along with this program. If not, see <http://www.gnu.org/licenses/>.
 -
 -->

<template>
	<ImageEditor v-if="editing"
		:mime="mime"
		:src="src"
		:fileid="fileid"
		@close="onClose" />

	<img v-else-if="data !== null"
		:alt="alt"
		:class="{
			dragging,
			loaded,
			zoomed: zoomRatio !== 1
		}"
		:src="data"
		:style="{
			marginTop: (shiftY * 2) + 'px',
			marginLeft: (shiftX * 2) + 'px',
			maxHeight: zoomRatio * 100 + '%',
			maxWidth: zoomRatio * 100 + '%',
		}"
		@error.capture.prevent.stop.once="onFail"
		@load="updateImgSize"
		@wheel="updateZoom"
		@dblclick.prevent="onDblclick"
		@mousedown.prevent="dragStart">
</template>

<script>
import axios from '@nextcloud/axios'
import Vue from 'vue'
import AsyncComputed from 'vue-async-computed'
import ImageEditor from './ImageEditor.vue'
import { basename } from '@nextcloud/paths'

Vue.use(AsyncComputed)

export default {
	name: 'Images',

	components: {
		ImageEditor,
	},

	props: {
		canZoom: {
			type: Boolean,
			default: false,
		},
		editing: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			dragging: false,
			shiftX: 0,
			shiftY: 0,
			zoomRatio: 1,
			fallback: false,
		}
	},

	computed: {
		src() {
			return this.source ?? this.davPath
		},
		zoomHeight() {
			return Math.round(this.height * this.zoomRatio)
		},
		zoomWidth() {
			return Math.round(this.width * this.zoomRatio)
		},
		alt() {
			const fileName = this.basename
			return t('viewer', '"{fileName}"', { fileName })
		},
	},

	asyncComputed: {
		data() {
			// Avoid svg xss attack vector
			if (this.mime === 'image/svg+xml') {
				return this.getBase64FromImage()
			}

			// Load the raw gif instead of the static preview
			if (this.mime === 'image/gif') {
				return this.src
			}

			// If there is no preview and we have a direct source
			// load it instead
			if (this.source && !this.hasPreview && !this.previewUrl) {
				return this.source
			}

			// If loading the preview failed once, let's load the original file
			if (this.fallback) {
				return this.src
			}

			return this.previewPath
		},
	},
	watch: {
		active(val, old) {
			// the item was hidden before and is now the current view
			if (val === true && old === false) {
				this.resetZoom()
				// end the dragging if your mouse go out of the content
				window.addEventListener('mouseout', this.dragEnd)
			// the item is not displayed
			} else if (val === false) {
				window.removeEventListener('mouseout', this.dragEnd)
			}
		},
	},
	methods: {
		// Updates the dimensions of the modal
		updateImgSize() {
			this.naturalHeight = this.$el.naturalHeight
			this.naturalWidth = this.$el.naturalWidth

			this.updateHeightWidth()
			this.doneLoading()
		},

		/**
		 * Manually retrieve the path and return its base64
		 *
		 * @return {string}
		 */
		async getBase64FromImage() {
			const file = await axios.get(this.src)
			return `data:${this.mime};base64,${btoa(file.data)}`
		},

		/**
		 * Handle zooming
		 *
		 * @param {Event} event the scroll event
		 * @return {null}
		 */
		updateZoom(event) {
			if (!this.canZoom) {
				return
			}

			event.stopPropagation()
			event.preventDefault()

			// scrolling position relative to the image
			const scrollX = event.clientX - this.$el.x - (this.width * this.zoomRatio / 2)
			const scrollY = event.clientY - this.$el.y - (this.height * this.zoomRatio / 2)
			const scrollPercX = Math.round(scrollX / (this.width * this.zoomRatio) * 100) / 100
			const scrollPercY = Math.round(scrollY / (this.height * this.zoomRatio) * 100) / 100
			const isZoomIn = event.deltaY < 0

			const newZoomRatio = isZoomIn
				? Math.min(this.zoomRatio + 0.1, 5) // prevent too big zoom
				: Math.max(this.zoomRatio - 0.1, 1) // prevent too small zoom

			// do not continue, img is back to its original state
			if (newZoomRatio === 1) {
				return this.resetZoom()
			}

			// calc how much the img grow from its current size
			// and adjust the margin accordingly
			const growX = this.width * newZoomRatio - this.width * this.zoomRatio
			const growY = this.height * newZoomRatio - this.height * this.zoomRatio

			// compensate for existing margins
			this.disableSwipe()
			this.shiftX = this.shiftX + Math.round(-scrollPercX * growX)
			this.shiftY = this.shiftY + Math.round(-scrollPercY * growY)
			this.zoomRatio = newZoomRatio
		},

		resetZoom() {
			this.enableSwipe()
			this.zoomRatio = 1
			this.shiftX = 0
			this.shiftY = 0
		},

		/**
		 * Dragging handlers
		 *
		 * @param {Event} event the event
		 */
		dragStart(event) {
			const { pageX, pageY } = event

			this.dragX = pageX
			this.dragY = pageY
			this.dragging = true
			this.$el.onmouseup = this.dragEnd
			this.$el.onmousemove = this.dragHandler
		},
		dragEnd(event) {
			event.preventDefault()

			this.dragging = false
			this.$el.onmouseup = null
			this.$el.onmousemove = null
		},
		dragHandler(event) {
			event.preventDefault()
			const { pageX, pageY } = event

			if (this.dragging && this.zoomRatio > 1 && pageX > 0 && pageY > 0) {
				const moveX = this.shiftX + (pageX - this.dragX)
				const moveY = this.shiftY + (pageY - this.dragY)
				const growX = this.zoomWidth - this.width
				const growY = this.zoomHeight - this.height

				this.shiftX = Math.min(Math.max(moveX, -growX / 2), growX / 2)
				this.shiftY = Math.min(Math.max(moveY, -growY / 2), growY / 2)
				this.dragX = pageX
				this.dragY = pageY
			}
		},
		onDblclick() {
			if (this.zoomRatio > 1) {
				this.resetZoom()
			} else {
				this.zoomRatio = 1.3
			}
		},

		onClose() {
			this.$emit('update:editing', false)
		},

		// Fallback to the original image if not already done
		onFail(event) {
			if (!this.fallback) {
				console.error(`Loading of file preview ${basename(this.src)} failed, falling back to original file`)
				this.fallback = true
			}
		},
	},
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
	// black while loading
	background-color: #000;
	// animate zooming/resize
	transition: height 100ms ease,
		width 100ms ease,
		margin-top 100ms ease,
		margin-left 100ms ease;
	// show checkered bg on hover if not currently zooming (but ok if zoomed)
	&:hover {
		background-image: linear-gradient(45deg, #{$checkered-color} 25%, transparent 25%),
			linear-gradient(45deg, transparent 75%, #{$checkered-color} 75%),
			linear-gradient(45deg, transparent 75%, #{$checkered-color} 75%),
			linear-gradient(45deg, #{$checkered-color} 25%, #fff 25%);
		background-size: 2 * $checkered-size 2 * $checkered-size;
		background-position: 0 0, 0 0, -#{$checkered-size} -#{$checkered-size}, $checkered-size $checkered-size;
	}
	&.loaded {
		// white once done loading
		background-color: #fff;
	}
	&.zoomed {
		position: absolute;
		max-height: none;
		max-width: none;
		z-index: 10010;
		cursor: move;
	}

	&.dragging {
		transition: none !important;
		cursor: move;
	}
}
</style>
