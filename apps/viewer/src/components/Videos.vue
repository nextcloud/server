<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<!-- Plyr currently replaces the parent. Wrapping to prevent this
	https://github.com/redxtech/vue-plyr/issues/259 -->
	<div v-if="url">
		<VuePlyr ref="plyr"
			:options="options"
			:style="{
				height: height + 'px',
				width: width + 'px'
			}">
			<video ref="video"
				:autoplay="active ? true : null"
				:playsinline="true"
				:poster="livePhotoPath"
				:src="url"
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

<script lang='ts'>
// eslint-disable-next-line n/no-missing-import
import Vue from 'vue'
import AsyncComputed from 'vue-async-computed'
import '@skjnldsv/vue-plyr/dist/vue-plyr.css'

import { imagePath } from '@nextcloud/router'

import logger from '../services/logger.js'
import { findLivePhotoPeerFromName } from '../utils/livePhotoUtils'
import { getPreviewIfAny } from '../utils/previewUtils'
import { preloadMedia } from '../services/mediaPreloader.js'

const VuePlyr = () => import(/* webpackChunkName: 'plyr' */'@skjnldsv/vue-plyr')

const blankVideo = imagePath('viewer', 'blank.mp4')

Vue.use(AsyncComputed)

export default {
	name: 'Videos',

	components: {
		VuePlyr,
	},
	data() {
		return {
			isFullscreenButtonVisible: false,
			fallback: false,
		}
	},

	computed: {
		livePhotoPath() {
			const peerFile = findLivePhotoPeerFromName(this, this.fileList)

			if (peerFile === undefined) {
				return undefined
			}

			return getPreviewIfAny(peerFile)
		},
		player() {
			return this.$refs.plyr.player
		},
		options() {
			return {
				autoplay: this.active === true,
				// Used to reset the video streams https://github.com/sampotts/plyr#javascript-1
				blankVideo,
				controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'captions', 'settings', 'fullscreen'],
				loadSprite: false,
				fullscreen: {
					iosNative: true,
				},
			}
		},
	},

	asyncComputed: {
		async url(): Promise<string> {
			if (this.fallback) {
				return preloadMedia(this.filename)
			} else {
				return this.src
			}
		},
	},

	watch: {
		active(val, old) {
			// the item was hidden before and is now the current view
			if (val === true && old === false) {
				this.player.play()

			// the item was playing before and is now hidden
			} else if (val === false && old === true) {
				this.player.pause()
			}
		},
	},

	// for some reason the video controls don't get mounted to the dom until after the component (Videos) is mounted,
	// using the mounted() hook will leave us with an empty array
	updated() {
		// Prevent swiping to the next/previous item when scrubbing the timeline or changing volume
		const plyrControls = this.$el.querySelectorAll('.plyr__controls__item')
		if (!plyrControls || !plyrControls.length) {
			return
		}
		[...plyrControls].forEach(control => {
			if (control.getAttribute('data-plyr') === 'fullscreen') {
				control.addEventListener('click', this.hideHeaderAndFooter)
			}
			if (!control?.addEventListener) {
				return
			}
			control.addEventListener('mouseenter', this.disableSwipe)
			control.addEventListener('mouseleave', this.enableSwipe)
		})
	},

	beforeDestroy() {
		// Force stop any ongoing request
		logger.debug('Closing video stream', { filename: this.filename })
		this.$refs.video?.pause?.()
		this.player.stop()
		this.player.destroy()
	},

	methods: {
		hideHeaderAndFooter() {
			// work arround to get the state of the fullscreen button, aria-selected attribute is not reliable
			this.isFullscreenButtonVisible = !this.isFullscreenButtonVisible
			if (this.isFullscreenButtonVisible) {
				document.body.querySelector('main').classList.add('viewer__hidden-fullscreen')
				document.body.querySelector('footer').classList.add('viewer__hidden-fullscreen')
			} else {
				document.body.querySelector('main').classList.remove('viewer__hidden-fullscreen')
				document.body.querySelector('footer').classList.remove('viewer__hidden-fullscreen')
			}
		},
		// Updates the dimensions of the modal
		updateVideoSize() {
			this.naturalHeight = this.$refs.video?.videoHeight
			this.naturalWidth = this.$refs.video?.videoWidth
			this.updateHeightWidth()
		},

		donePlaying() {
			// reset and show poster after play
			this.$refs.video.autoplay = false
			this.$refs.video.load()
		},

		onLoadedMetadata() {
			this.updateVideoSize()
			// Force any further loading once we have the metadata
			if (!this.active) {
				this.player.stop()
			}
		},

		// Fallback to the original image if not already done
		onFail() {
			if (!this.fallback) {
				console.error(`Loading of file ${this.filename} failed, falling back to fetching it by hand`)
				this.fallback = true
			}
		},
	},
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
main.viewer__hidden-fullscreen {
	height: 100vh !important;
	width: 100vw !important;
	margin: 0 !important;
}

footer.viewer__hidden-fullscreen {
	display: none !important;
}
</style>
