<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="image_container">
		<template v-if="data !== null">
			<img
				v-if="!livePhotoCanBePlayed"
				ref="image"
				:alt="alt"
				:class="{
					dragging,
					loaded,
					zoomed: zoomRatio > 1,
				}"
				:src="data"
				:style="imgStyle"
				@error.capture.prevent.stop.once="onFail"
				@load="onDoneLoading"
				@wheel.stop.prevent="updateZoom"
				@dblclick.prevent="onDblclick"
				@pointerdown.prevent="pointerDown"
				@pointerup.prevent="pointerUp"
				@pointermove.prevent="pointerMove">

			<template v-if="livePhoto">
				<video
					v-show="livePhotoCanBePlayed"
					ref="video"
					:class="{
						dragging,
						loaded,
						zoomed: zoomRatio > 1,
					}"
					:style="imgStyle"
					:playsinline="true"
					:poster="data"
					:src="livePhotoSrc"
					preload="metadata"
					@canplaythrough="doneLoadingLivePhoto"
					@loadedmetadata="updateImageSize"
					@wheel.stop.prevent="updateZoom"
					@error.capture.prevent.stop.once="onFail"
					@dblclick.prevent="onDblclick"
					@pointerdown.prevent="pointerDown"
					@pointerup.prevent="pointerUp"
					@pointermove.prevent="pointerMove"
					@ended="stopLivePhoto" />
				<button
					v-if="width !== 0"
					class="live-photo_play_button"
					:style="{ left: `calc(50% - ${width / 2}px)` }"
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

<script setup lang="ts">
import type { ViewerEmits, ViewerProps } from '../api_package/viewer.ts'

import axios from '@nextcloud/axios'
import { translate as t } from '@nextcloud/l10n'
import { NcLoadingIcon } from '@nextcloud/vue'
import DOMPurify from 'dompurify'
import { computed, ref, watch } from 'vue'
import PlayCircleOutline from 'vue-material-design-icons/PlayCircleOutline.vue'
import { useViewerProps } from '../composables/useViewerProps.ts'
import { logger } from '../services/logger.ts'
import { preloadMedia } from '../services/mediaPreloader.ts'
import { findLivePhotoPeerFromFileId } from '../utils/livePhotoUtils.ts'
import { getPreviewIfAny } from '../utils/previewUtils.ts'

defineOptions({
	name: 'ViewerImages',
})

const props = withDefaults(defineProps<ViewerProps>(), {
	editing: false,
})

const emit = defineEmits<ViewerEmits>()

// Use the viewer props composable
const { filename, src } = useViewerProps(props)

// Refs
const image = ref<HTMLImageElement>()
const video = ref<HTMLVideoElement>()

// Reactive state
const dragging = ref(false)
const dragX = ref(0)
const dragY = ref(0)
const pinchDistance = ref(0)
const pinchStartZoomRatio = ref(1)
const pointerCache = ref<Array<{ pointerId: number, x: number, y: number }>>([])
const shiftX = ref(0)
const shiftY = ref(0)
const zooming = ref(false)
const zoomRatio = ref(1)

const data = ref<string | null>(null)
const fallback = ref(false)
const livePhotoCanBePlayed = ref(false)
const loaded = ref(false)

const height = ref(0)
const width = ref(0)

// Computed properties
const mime = computed(() => props.file.mime)

const hasPreview = computed(() => props.file.attributes?.hasPreview ?? false)
const previewUrl = computed(() => props.file.attributes?.previewUrl)
const metadataFilesLivePhoto = computed(() => props.file.attributes?.['metadata-files-live-photo'])
const previewPath = computed(() => getPreviewIfAny(props.file))

const zoomHeight = computed(() => Math.round(height.value * zoomRatio.value))
const zoomWidth = computed(() => Math.round(width.value * zoomRatio.value))
const alt = computed(() => props.file.basename)

const imgStyle = computed(() => {
	if (zoomRatio.value === 1) {
		return {
			height: zoomHeight.value + 'px',
			width: zoomWidth.value + 'px',
		}
	}
	return {
		marginTop: Math.round(shiftY.value * 2) + 'px',
		marginLeft: Math.round(shiftX.value * 2) + 'px',
		height: zoomHeight.value + 'px',
		width: zoomWidth.value + 'px',
	}
})

const livePhoto = computed(() => {
	if (metadataFilesLivePhoto.value === undefined) {
		return undefined
	}
	return findLivePhotoPeerFromFileId(metadataFilesLivePhoto.value, props.files)
})

const livePhotoSrc = computed(() => livePhoto.value?.source ?? null)

// Load data when component mounts or file changes
watch(filename, async () => {
	await loadData()
})
// Prefer a client-side source (e.g. a freshly edited image) over any fetch.
watch(() => props.localSource, (source) => {
	if (source) {
		data.value = source
	}
})
loadData()

/**
 * Load the image data to be displayed
 */
async function loadData() {
	// A client-side source (e.g. a just-edited image) is shown as-is, no fetch.
	if (props.localSource) {
		data.value = props.localSource
		return
	}

	// Avoid svg xss attack vector
	if (mime.value === 'image/svg+xml') {
		data.value = await getBase64FromImage()
		return
	}

	// Load the raw gif instead of the static preview
	if (mime.value === 'image/gif') {
		data.value = src.value
		return
	}

	// If there is no preview and we have a direct source, load it instead
	if (props.file.source && !hasPreview.value && !previewUrl.value) {
		// If loading the source failed once, let's try fetching it by hand
		if (fallback.value) {
			data.value = await preloadMedia(props.file)
		} else {
			data.value = props.file.source
		}
		return
	}

	// If loading the preview failed once, let's load the original file
	if (fallback.value) {
		data.value = src.value
		return
	}

	data.value = previewPath.value
}

/**
 * The image/video has finished loading
 */
function onDoneLoading() {
	loaded.value = true
	updateImageSize()
	emit('loaded')
}

/**
 * Fit the media element to the available space while keeping its aspect ratio.
 * The intrinsic dimensions come from the loaded element itself — the image's
 * `naturalWidth`/`naturalHeight`, or the live-photo video's `videoWidth`/
 * `videoHeight` — which is the reliable source once `load`/`loadedmetadata`
 * has fired.
 */
function updateImageSize() {
	const mediaWidth = image.value?.naturalWidth || video.value?.videoWidth
	const mediaHeight = image.value?.naturalHeight || video.value?.videoHeight
	if (!mediaWidth || !mediaHeight) {
		return
	}

	const ratio = Math.min(props.maxHeight / mediaHeight, props.maxWidth / mediaWidth)
	width.value = Math.floor(mediaWidth * ratio)
	height.value = Math.floor(mediaHeight * ratio)
}

// Refit on container resize (max height/width change) using the element's
// already-known intrinsic size. A new file refits through its load event.
watch([() => props.maxWidth, () => props.maxHeight], updateImageSize)

/**
 * @return base64 string of the image
 */
async function getBase64FromImage(): Promise<string> {
	const file = await axios.get(src.value)
	const sanitized = DOMPurify.sanitize(file.data)
	return `data:${mime.value};base64,${btoa(unescape(encodeURIComponent(sanitized)))}`
}

/**
 *
 * @param newShiftX - The desired horizontal shift in pixels (clamped to the zoomed bounds)
 * @param newShiftY - The desired vertical shift in pixels (clamped to the zoomed bounds)
 * @param newZoomRatio - The zoom ratio used to compute the maximum allowed shift
 */
function updateShift(newShiftX: number, newShiftY: number, newZoomRatio: number) {
	const maxShiftX = width.value * newZoomRatio - width.value
	const maxShiftY = height.value * newZoomRatio - height.value
	shiftX.value = Math.min(Math.max(newShiftX, -maxShiftX / 2), maxShiftX / 2)
	shiftY.value = Math.min(Math.max(newShiftY, -maxShiftY / 2), maxShiftY / 2)
}

/**
 *
 * @param stableX - The horizontal viewport coordinate to keep stable while zooming
 * @param stableY - The vertical viewport coordinate to keep stable while zooming
 * @param newZoomRatio - The new zoom ratio to apply
 */
function updateZoomAndShift(stableX: number, stableY: number, newZoomRatio: number) {
	// scrolling position relative to the image
	const element = image.value ?? video.value
	if (!element) {
		return
	}

	const scrollX = stableX - element.getBoundingClientRect().x - (width.value * zoomRatio.value / 2)
	const scrollY = stableY - element.getBoundingClientRect().y - (height.value * zoomRatio.value / 2)
	const scrollPercX = scrollX / (width.value * zoomRatio.value)
	const scrollPercY = scrollY / (height.value * zoomRatio.value)

	// calc how much the img grow from its current size and adjust the margin accordingly
	const growX = width.value * newZoomRatio - width.value * zoomRatio.value
	const growY = height.value * newZoomRatio - height.value * zoomRatio.value

	// compensate for existing margins
	const newShiftX = shiftX.value - scrollPercX * growX
	const newShiftY = shiftY.value - scrollPercY * growY
	updateShift(newShiftX, newShiftY, newZoomRatio)
	zoomRatio.value = newZoomRatio
}

/**
 *
 */
function distanceBetweenTouches(): number {
	const t0 = pointerCache.value[0]
	const t1 = pointerCache.value[1]
	if (!t0 || !t1) {
		return 0
	}
	const diffX = t1.x - t0.x
	const diffY = t1.y - t0.y
	return Math.sqrt(diffX * diffX + diffY * diffY)
}

/**
 *
 * @param event - The wheel event driving the zoom in/out
 */
function updateZoom(event: WheelEvent) {
	const isZoomIn = event.deltaY < 0
	const newZoomRatio = isZoomIn
		? Math.min(zoomRatio.value * 1.1, 5) // prevent too big zoom
		: Math.max(zoomRatio.value / 1.1, 1) // prevent too small zoom

	// do not continue, img is back to its original state
	if (newZoomRatio === 1) {
		return resetZoom()
	}

	emit('update:canSwipe', false)
	updateZoomAndShift(event.clientX, event.clientY, newZoomRatio)
}

/**
 * Reset zoom to original state
 */
function resetZoom() {
	emit('update:canSwipe', true)
	zoomRatio.value = 1
	shiftX.value = 0
	shiftY.value = 0
}

/**
 * Used for pinch zoom and drag
 *
 * @param event The pointer down event
 */
function pointerDown(event: PointerEvent) {
	// New pointer - mouse down or additional touch --> store client coordinates in the pointer cache
	pointerCache.value.push({ pointerId: event.pointerId, x: event.clientX, y: event.clientY })

	// Single touch or mouse down --> start dragging
	if (pointerCache.value.length === 1) {
		dragX.value = event.clientX
		dragY.value = event.clientY
		dragging.value = true
	}

	// Two touches --> start (pinch) zooming
	if (pointerCache.value.length === 2) {
		// Calculate base (reference) distance between touches
		pinchDistance.value = distanceBetweenTouches()
		pinchStartZoomRatio.value = zoomRatio.value
		zooming.value = true
		emit('update:canSwipe', false)
	}
}

/**
 * Used for pinch zoom and drag
 *
 * @param event The pointer up event
 */
function pointerUp(event: PointerEvent) {
	// Remove pointer from the pointer cache
	const index = pointerCache.value.findIndex((cachedEv) => cachedEv.pointerId === event.pointerId)
	pointerCache.value.splice(index, 1)
	dragging.value = false
	zooming.value = false
}

/**
 * Used for pinch zoom and drag
 *
 * @param event The pointer move event
 */
function pointerMove(event: PointerEvent) {
	if (pointerCache.value.length > 0) {
		// Update pointer position in the pointer cache
		const index = pointerCache.value.findIndex((cachedEv) => cachedEv.pointerId === event.pointerId)
		const cached = index >= 0 ? pointerCache.value[index] : undefined
		if (cached) {
			cached.x = event.clientX
			cached.y = event.clientY
		}
	}

	// Single touch or mouse down --> dragging
	if (pointerCache.value.length === 1 && dragging.value && !zooming.value && zoomRatio.value > 1) {
		const { clientX, clientY } = event
		const newShiftX = shiftX.value + (clientX - dragX.value)
		const newShiftY = shiftY.value + (clientY - dragY.value)

		updateShift(newShiftX, newShiftY, zoomRatio.value)

		dragX.value = clientX
		dragY.value = clientY
	}

	// Two touches --> (pinch) zooming
	if (pointerCache.value.length === 2 && zooming.value) {
		// Calculate current distance between touches
		const newDistance = distanceBetweenTouches()

		// Calculate new zoom ratio - keep it between 1 and 5
		const newZoomRatio = Math.min(Math.max(pinchStartZoomRatio.value * (newDistance / pinchDistance.value), 1), 5)

		// Calculate "stable" point - in the middle between touches
		const t0 = pointerCache.value[0]
		const t1 = pointerCache.value[1]
		if (!t0 || !t1) {
			return
		}
		const stableX = (t0.x + t1.x) / 2
		const stableY = (t0.y + t1.y) / 2

		updateZoomAndShift(stableX, stableY, newZoomRatio)
	}
}

/**
 * Start zooming in or reset zoom on double click
 */
function onDblclick() {
	if (zoomRatio.value > 1) {
		resetZoom()
	} else {
		zoomRatio.value = 1.3
	}
}

/**
 *
 */
async function onFail() {
	if (fallback.value) {
		logger.error(`Loading of file ${filename.value} failed even after fallback`)
		emit('errored', new Error(t('viewer', 'Failed to load image.')))
		return
	}

	// Try to load E2EE file as a fallback
	logger.error(`Loading of file ${filename.value} failed, falling back to fetching it by hand`)
	fallback.value = true
	src.value = await preloadMedia(props.file)
}

/**
 * The live photo has finished loading
 */
function doneLoadingLivePhoto() {
	livePhotoCanBePlayed.value = true
	onDoneLoading()
}

/**
 * If possible, play the live photo
 */
function playLivePhoto() {
	if (!livePhotoCanBePlayed.value || !video.value) {
		return
	}
	video.value.play()
}

/**
 * Stop the live photo playback if any
 */
function stopLivePhoto() {
	if (!video.value) {
		return
	}
	video.value.load()
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
		background-size: #{2 * $checkered-size} #{2 * $checkered-size};
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
