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
					zoomed: zoomRatio !== 1
				}"
				:src="data"
				:style="imgStyle"
				@error.capture.prevent.stop.once="onFail"
				@load="updateImgSize"
				@wheel="updateZoom"
				@dblclick.prevent="onDblclick"
				@mousedown.prevent="dragStart">

			<template v-if="livePhoto">
				<video v-show="livePhotoCanBePlayed"
					ref="video"
					:class="{
						dragging,
						loaded,
						zoomed: zoomRatio !== 1
					}"
					:style="imgStyle"
					:playsinline="true"
					:poster="data"
					:src="livePhotoSrc"
					preload="metadata"
					@canplaythrough="doneLoadingLivePhoto"
					@loadedmetadata="updateImgSize"
					@wheel="updateZoom"
					@error.capture.prevent.stop.once="onFail"
					@dblclick.prevent="onDblclick"
					@mousedown.prevent="dragStart"
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
			livePhotoCanBePlayed: false,
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
				return {}
			}
			return {
				marginTop: Math.round(this.shiftY * 2) + 'px',
				marginLeft: Math.round(this.shiftX * 2) + 'px',
				height: this.zoomHeight + 'px',
				width: this.zoomWidth + 'px',
			}
		},
		livePhoto() {
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
			return `data:${this.mime};base64,${btoa(file.data)}`
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

			event.stopPropagation()
			event.preventDefault()

			// scrolling position relative to the image
			const element = this.$refs.image ?? this.$refs.video
			const scrollX = event.clientX - element.x - (this.width * this.zoomRatio / 2)
			const scrollY = event.clientY - element.y - (this.height * this.zoomRatio / 2)
			const scrollPercX = scrollX / (this.width * this.zoomRatio)
			const scrollPercY = scrollY / (this.height * this.zoomRatio)
			const isZoomIn = event.deltaY < 0

			const newZoomRatio = isZoomIn
				? Math.min(this.zoomRatio * 1.1, 5) // prevent too big zoom
				: Math.max(this.zoomRatio / 1.1, 1) // prevent too small zoom

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
			this.shiftX = this.shiftX - scrollPercX * growX
			this.shiftY = this.shiftY - scrollPercY * growY
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
		 * @param {DragEvent} event the event
		 */
		dragStart(event) {
			const { pageX, pageY } = event

			this.dragX = pageX
			this.dragY = pageY
			this.dragging = true
			const element = this.$refs.image ?? this.$refs.video
			element.onmouseup = this.dragEnd
			element.onmousemove = this.dragHandler
		},
		/**
		 * @param {DragEvent} event the event
		 */
		dragEnd(event) {
			event.preventDefault()

			this.dragging = false
			const element = this.$refs.image ?? this.$refs.video
			if (element) {
				element.onmouseup = null
				element.onmousemove = null
			}
		},
		/**
		 * @param {DragEvent} event the event
		 */
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
	max-width: 100%;
	max-height: 100%;
	align-self: center;
	justify-self: center;
	// black while loading
	background-color: #000;
	// disable animations during zooming/resize
	transition: none !important;
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
