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
	<div class="image_container">
		<ImageEditor v-if="editing"
			:mime="mime"
			:src="src"
			:fileid="fileid"
			@close="onClose" />

		<template v-else-if="data !== null">
			<img v-if="!livePhotoCanBePlayed"
				ref="image"
				:alt="alt"
				:class="{
					dragging,
					loaded,
					zoomed: zoomRatio > 1
				}"
				:src="data"
				:style="imgStyle"
				@error.capture.prevent.stop.once="onFail"
				@load="updateImgSize"
				@wheel.stop.prevent="updateZoom"
				@dblclick.prevent="onDblclick"
				@pointerdown.prevent="pointerDown"
				@pointerup.prevent="pointerUp"
				@pointermove.prevent="pointerMove">

			<template v-if="livePhoto">
				<video v-show="livePhotoCanBePlayed"
					ref="video"
					:class="{
						dragging,
						loaded,
						zoomed: zoomRatio > 1
					}"
					:style="imgStyle"
					:playsinline="true"
					:poster="data"
					:src="livePhotoSrc"
					preload="metadata"
					@canplaythrough="doneLoadingLivePhoto"
					@loadedmetadata="updateImgSize"
					@wheel.stop.prevent="updateZoom"
					@error.capture.prevent.stop.once="onFail"
					@dblclick.prevent="onDblclick"
					@pointerdown.prevent="pointerDown"
					@pointerup.prevent="pointerUp"
					@pointermove.prevent="pointerMove"
					@ended="stopLivePhoto" />
				<button v-if="width !== 0"
					class="live-photo_play_button"
					:style="{left: `calc(50% - ${width/2}px)`}"
					:disabled="!livePhotoCanBePlayed"
					:aria-description="t('viewer', 'Play the live photo')"
					@click="playLivePhoto"
					@pointerenter="playLivePhoto"
					@focus="playLivePhoto"
					@pointerleave="stopLivePhoto"
					@blur="stopLivePhoto">
					<PlayCircleOutline v-if="livePhotoCanBePlayed" />
					<NcLoadingIcon v-else />
					<!-- TRANSLATORS Label of the button used at the top left corner of live photos to play them -->
					{{ t('viewer', 'LIVE') }}
				</button>
			</template>
		</template>
	</div>
</template>

<script>
import Vue from 'vue'
import AsyncComputed from 'vue-async-computed'
import PlayCircleOutline from 'vue-material-design-icons/PlayCircleOutline.vue'

import axios from '@nextcloud/axios'
import { basename } from '@nextcloud/paths'
import { translate } from '@nextcloud/l10n'
import { NcLoadingIcon } from '@nextcloud/vue'

import ImageEditor from './ImageEditor.vue'
import { findLivePhotoPeerFromFileId } from '../utils/livePhotoUtils'
import { getDavPath } from '../utils/fileUtils'

Vue.use(AsyncComputed)

export default {
	name: 'Images',

	components: {
		ImageEditor,
		PlayCircleOutline,
		NcLoadingIcon,
	},

	props: {
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
			livePhotoCanBePlayed: false,
			zooming: false,
			pinchDistance: 0,
			pinchStartZoomRatio: 1,
			pointerCache: [],
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
			return this.basename
		},
		imgStyle() {
			if (this.zoomRatio === 1) {
				return {
					height: this.zoomHeight + 'px',
					width: this.zoomWidth + 'px',
				}
			}
			return {
				marginTop: Math.round(this.shiftY * 2) + 'px',
				marginLeft: Math.round(this.shiftX * 2) + 'px',
				height: this.zoomHeight + 'px',
				width: this.zoomWidth + 'px',
			}
		},
		livePhoto() {
			if (this.metadataFilesLivePhoto === undefined) {
				return undefined
			}

			return findLivePhotoPeerFromFileId(this.metadataFilesLivePhoto, this.fileList)
		},
		livePhotoSrc() {
			return this.livePhoto?.source ?? this.livePhotoDavPath
		},
		/** @return {string|null} */
		livePhotoDavPath() {
			return this.livePhoto
				? getDavPath({
					filename: this.livePhoto.filename,
					basename: this.livePhoto.basename,
				})
				: null
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
				// end the dragging if your pointer (mouse or touch) go out of the content
				// Not sure why ???
				window.addEventListener('pointerout', this.pointerUp)
			// the item is not displayed
			} else if (val === false) {
				// Not sure why ???
				window.removeEventListener('pointerout', this.pointerUp)
			}
		},
	},
	methods: {
		// Updates the dimensions of the modal
		updateImgSize() {
			if (this.$refs.image) {
				this.naturalHeight = this.$refs.image.naturalHeight
				this.naturalWidth = this.$refs.image.naturalWidth
			} else if (this.$refs.video) {
				this.naturalHeight = this.$refs.video.videoHeight
				this.naturalWidth = this.$refs.video.videoWidth
			}

			this.updateHeightWidth()
			this.doneLoading()
		},

		/**
		 * Manually retrieve the path and return its base64
		 *
		 * @return {Promise<string>}
		 */
		async getBase64FromImage() {
			const file = await axios.get(this.src)
			return `data:${this.mime};base64,${btoa(unescape(encodeURIComponent(file.data)))}`
		},

		// Helper methods for zoom/pan operations
		updateShift(newShiftX, newShiftY, newZoomRatio) {
			const maxShiftX = this.width * newZoomRatio - this.width
			const maxShiftY = this.height * newZoomRatio - this.height
			this.shiftX = Math.min(Math.max(newShiftX, -maxShiftX / 2), maxShiftX / 2)
			this.shiftY = Math.min(Math.max(newShiftY, -maxShiftY / 2), maxShiftY / 2)
		},

		// Change zoom ratio of the image to newZoomRatio.
		// Try to make sure that image position at stableX, stableY
		// in client coordinates stays in the same place on the screen.
		updateZoomAndShift(stableX, stableY, newZoomRatio) {
			if (!this.canZoom) {
				return
			}

			// scrolling position relative to the image
			const element = this.$refs.image ?? this.$refs.video
			const scrollX = stableX - element.getBoundingClientRect().x - (this.width * this.zoomRatio / 2)
			const scrollY = stableY - element.getBoundingClientRect().y - (this.height * this.zoomRatio / 2)
			const scrollPercX = scrollX / (this.width * this.zoomRatio)
			const scrollPercY = scrollY / (this.height * this.zoomRatio)

			// calc how much the img grow from its current size
			// and adjust the margin accordingly
			const growX = this.width * newZoomRatio - this.width * this.zoomRatio
			const growY = this.height * newZoomRatio - this.height * this.zoomRatio

			// compensate for existing margins
			const newShiftX = this.shiftX - scrollPercX * growX
			const newShiftY = this.shiftY - scrollPercY * growY
			this.updateShift(newShiftX, newShiftY, newZoomRatio)
			this.zoomRatio = newZoomRatio
		},

		distanceBetweenTouches() {
			const t0 = this.pointerCache[0]
			const t1 = this.pointerCache[1]
			const diffX = (t1.x - t0.x)
			const diffY = (t1.y - t0.y)
			return Math.sqrt(diffX * diffX + diffY * diffY)
		},

		/**
		 * Handle zooming
		 *
		 * @param {WheelEvent} event the scroll event
		 * @return {void}
		 */
		updateZoom(event) {
			if (!this.canZoom) {
				return
			}

			const isZoomIn = event.deltaY < 0
			const newZoomRatio = isZoomIn
				? Math.min(this.zoomRatio * 1.1, 5) // prevent too big zoom
				: Math.max(this.zoomRatio / 1.1, 1) // prevent too small zoom

			// do not continue, img is back to its original state
			if (newZoomRatio === 1) {
				return this.resetZoom()
			}

			this.disableSwipe()
			this.updateZoomAndShift(event.clientX, event.clientY, newZoomRatio)
		},

		resetZoom() {
			this.enableSwipe()
			this.zoomRatio = 1
			this.shiftX = 0
			this.shiftY = 0
		},

		// Pinch-zoom implementation based on:
		// https://developer.mozilla.org/en-US/docs/Web/API/Pointer_events/Pinch_zoom_gestures

		/**
		 * Dragging and (pinch) zooming handlers
		 *
		 * @param {DragEvent} event the event
		 */
		pointerDown(event) {
			// New pointer - mouse down or additional touch --> store client coordinates in the pointer cache
			this.pointerCache.push({ pointerId: event.pointerId, x: event.clientX, y: event.clientY })

			// Single touch or mouse down --> start dragging
			if (this.pointerCache.length === 1) {
				this.dragX = event.clientX
				this.dragY = event.clientY
				this.dragging = true
			}

			// Two touches --> start (pinch) zooming
			if (this.pointerCache.length === 2) {
				// Calculate base (reference) distance between touches
				this.pinchDistance = this.distanceBetweenTouches()
				this.pinchStartZoomRatio = this.zoomRatio
				this.zooming = true
				this.disableSwipe()
			}
		},
		/**
		 * @param {DragEvent} event the event
		 */
		 pointerUp(event) {
			// Remove pointer from the pointer cache
			const index = this.pointerCache.findIndex(
				(cachedEv) => cachedEv.pointerId === event.pointerId,
			)
			this.pointerCache.splice(index, 1)
			this.dragging = false
			this.zooming = false
		},
		/**
		 * @param {DragEvent} event the event
		 */
		 pointerMove(event) {
			if (!this.canZoom) {
				return
			}

			if (this.pointerCache.length > 0) {
				// Update pointer position in the pointer cache
				const index = this.pointerCache.findIndex(
					(cachedEv) => cachedEv.pointerId === event.pointerId,
				)
				if (index >= 0) {
					this.pointerCache[index].x = event.clientX
					this.pointerCache[index].y = event.clientY
				}
			}

			// Single touch or mouse down --> dragging
			if (this.pointerCache.length === 1 && this.dragging && !this.zooming && this.zoomRatio > 1) {
				const { clientX, clientY } = event
				const newShiftX = this.shiftX + (clientX - this.dragX)
				const newShiftY = this.shiftY + (clientY - this.dragY)

				this.updateShift(newShiftX, newShiftY, this.zoomRatio)

				this.dragX = clientX
				this.dragY = clientY
			}

			// Two touches --> (pinch) zooming
			if (this.pointerCache.length === 2 && this.zooming) {
				// Calculate current distance between touches
				const newDistance = this.distanceBetweenTouches()

				// Calculate new zoom ratio - keep it between 1 and 5
				const newZoomRatio = Math.min(Math.max(this.pinchStartZoomRatio * (newDistance / this.pinchDistance), 1), 5)

				// Calculate "stable" point - in the middle between touches
				const t0 = this.pointerCache[0]
				const t1 = this.pointerCache[1]
				const stableX = (t0.x + t1.x) / 2
				const stableY = (t0.y + t1.y) / 2

				this.updateZoomAndShift(stableX, stableY, newZoomRatio)
			}

		},
		onDblclick() {
			if (!this.canZoom) {
				return
			}

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
		onFail() {
			if (!this.fallback) {
				console.error(`Loading of file preview ${basename(this.src)} failed, falling back to original file`)
				this.fallback = true
			}
		},
		doneLoadingLivePhoto() {
			this.livePhotoCanBePlayed = true
			this.doneLoading()
		},
		playLivePhoto() {
			if (!this.livePhotoCanBePlayed) {
				return
			}

			/** @type {HTMLVideoElement} */
			const video = this.$refs.video
			video.play()
		},
		stopLivePhoto() {
			/** @type {HTMLVideoElement} */
			const video = this.$refs.video
			video.load()
		},

		t: translate,
	},
}
</script>

<style scoped lang="scss">
$checkered-size: 8px;
$checkered-color: #efefef;

.image_container {
	display: flex;
	align-items: center;
	height: 100%;
	justify-content: center;
}

img, video {
	align-self: center;
	justify-self: center;
	// black while loading
	background-color: #000;
	// disable animations during zooming/resize
	transition: none !important;
	touch-action: none;
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
		z-index: 10010;
		cursor: move;
	}

	&.dragging {
		transition: none !important;
		cursor: move;
	}
}

.live-photo_play_button {
	position: absolute;
	top: 0;
	// left: is set dynamically on the element itself
	margin: 16px !important;
	display: flex;
	align-items: center;
	border: none;
	gap: 4px;
	border-radius: var(--border-radius);
	padding: 4px 8px;
	background-color: var(--color-main-background-blur);
}
</style>
