<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<!-- eslint-disable vue/no-unused-refs -- plyr/audio refs are consumed by usePlyrPlayer via useTemplateRef -->
	<!-- Plyr currently replaces the parent. Wrapping to prevent this
	https://github.com/redxtech/vue-plyr/issues/259 -->
	<div>
		<VuePlyr
			ref="plyr"
			:options="options"
			:style="{
				height: height + 'px',
				width: width + 'px',
			}">
			<video
				ref="video"
				:autoplay="true"
				:playsinline="true"
				:poster="livePhotoPath"
				:src="src"
				:style="{
					height: height + 'px',
					width: width + 'px',
				}"
				preload="metadata"
				@error.capture.prevent.stop.once="onFail"
				@ended="donePlaying"
				@canplay="doneLoading"
				@loadedmetadata="onLoadedMetadata">

				<!-- Omitting `type` on purpose because most of the
					browsers auto detect the appropriate codec.
					Having it set force the browser to comply to
					the provided mime instead of detecting a potential
					compatibility. -->

				{{ t('viewer', 'Your browser does not support videos.') }}
			</video>
		</VuePlyr>
	</div>
</template>

<script setup lang="ts">
import type { ViewerEmits, ViewerProps } from '../api_package/viewer.ts'

import { translate as t } from '@nextcloud/l10n'
import VuePlyr from '@skjnldsv/vue-plyr'
import { computed, ref, watch } from 'vue'
import { usePlyrPlayer } from '../composables/usePlyrPlayer.ts'
import { useViewerProps } from '../composables/useViewerProps.ts'
import { logger } from '../services/logger.ts'
import { findLivePhotoPeerFromName } from '../utils/livePhotoUtils.ts'
import { getPreviewIfAny } from '../utils/previewUtils.ts'

defineOptions({
	name: 'ViewerVideos',
})

const props = defineProps<ViewerProps>()
const emit = defineEmits<ViewerEmits>()

const {
	video,
	onFail,
	donePlaying,
	doneLoading,
	options,
} = usePlyrPlayer(false, props, emit)

const {
	src,
} = useViewerProps(props)

const height = ref(0)
const width = ref(0)

// Update video size when max height or width props change
watch(() => props.maxHeight, updateVideoSize)
watch(() => props.maxWidth, updateVideoSize)

const livePhotoPath = computed(() => {
	const peerFile = findLivePhotoPeerFromName(props.file, props.files)
	if (peerFile === undefined) {
		return undefined
	}
	return getPreviewIfAny(peerFile)
})

/**
 * Update the video size based on the max height and width props
 * We need to keep the aspect ratio of the video
 * and fit it within the max height and width.
 */
function updateVideoSize() {
	const videoHeight = video?.value?.videoHeight
	const videoWidth = video?.value?.videoWidth
	if (!videoHeight || !videoWidth) {
		return
	}

	const heightRatio = props.maxHeight / videoHeight
	const widthRatio = props.maxWidth / videoWidth

	const ratio = Math.min(heightRatio, widthRatio)
	height.value = Math.floor(videoHeight * ratio)
	width.value = Math.floor(videoWidth * ratio)
}

/**
 * Update video size when metadata is loaded
 */
function onLoadedMetadata() {
	logger.debug('Video metadata loaded, updating size', { filename: props.file.basename })
	updateVideoSize()
}
</script>

<style scoped lang="scss">
video {
	/* over arrows in tiny screens */
	z-index: 20050;
	align-self: center;
	max-width: 100%;
	max-height: 100% !important;
	background-color: black;

	justify-self: center;
}

:deep() {
	.plyr:-webkit-full-screen video {
		width: 100% !important;
		height: 100% !important;
	}
	.plyr:fullscreen video {
		width: 100% !important;
		height: 100% !important;
	}
	.plyr__progress__container {
		flex: 1 1;
	}

	.plyr {
		// stylelint-disable-next-line no-invalid-position-at-import-rule -- scoped scss partial
		@import '../mixins/Plyr';

		// Override server font style
		button {
			color: white;

			&:hover,
			&:focus {
				color: var(--color-primary-element-text);
				background-color: var(--color-primary-element);
			}
		}
	}
}
</style>

<style lang="scss">
@import '@skjnldsv/vue-plyr/dist/vue-plyr.css';

// Fullscreen styles to hide header and footer
// when in fullscreen mode
main.viewer__hidden-fullscreen {
	height: 100vh !important;
	width: 100vw !important;
	margin: 0 !important;
}

footer.viewer__hidden-fullscreen {
	display: none !important;
}
</style>
