<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<!-- Plyr currently replaces the parent. Wrapping to prevent this
	https://github.com/redxtech/vue-plyr/issues/259 -->
	<div v-if="url">
		<VuePlyr ref="plyr"
			:options="options">
			<audio ref="audio"
				:autoplay="active"
				:src="url"
				preload="metadata"
				@error.capture.prevent.stop.once="onFail"
				@ended="donePlaying"
				@canplay="doneLoading">

				<!-- Omitting `type` on purpose because most of the
					browsers auto detect the appropriate codec.
					Having it set force the browser to comply to
					the provided mime instead of detecting a potential
					compatibility. -->

				{{ t('viewer', 'Your browser does not support audio.') }}
			</audio>
		</VuePlyr>
	</div>
</template>

<script lang='ts'>
import Vue from 'vue'
import AsyncComputed from 'vue-async-computed'
// eslint-disable-next-line n/no-missing-import
import '@skjnldsv/vue-plyr/dist/vue-plyr.css'

import logger from '../services/logger.js'
import { preloadMedia } from '../services/mediaPreloader'

const VuePlyr = () => import(/* webpackChunkName: 'plyr' */'@skjnldsv/vue-plyr')

Vue.use(AsyncComputed)

export default {
	name: 'Audios',

	components: {
		VuePlyr,
	},

	data() {
		return {
			fallback: false,
		}
	},

	computed: {
		player() {
			return this.$refs.plyr.player
		},
		options() {
			return {
				autoplay: this.active === true,
				// Used to reset the audio streams https://github.com/sampotts/plyr#javascript-1
				blankVideo: '/blank.aac',
				controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'settings'],
				loadSprite: false,
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
			if (!control?.addEventListener) {
				return
			}
			control.addEventListener('mouseenter', this.disableSwipe)
			control.addEventListener('mouseleave', this.enableSwipe)
		})
	},

	beforeDestroy() {
		// Force stop any ongoing request
		logger.debug('Closing audio stream', { filename: this.filename })
		this.$refs.audio.pause()
		this.player.stop()
		this.player.destroy()
	},

	methods: {
		donePlaying() {
			this.$refs.audio.autoplay = false
			this.$refs.audio.load()
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
audio {
	/* over arrows in tiny screens */
	z-index: 20050;
	align-self: center;
	max-width: 100%;
	max-height: 100%;
	background-color: black;

	justify-self: center;
}

:deep() {
	.plyr__progress__container {
		flex: 1 1;
	}

	.plyr {
		@import '../mixins/Plyr';
	}

	// make it a bit off-center in order to fix mobile controls
	@media only screen and (max-width: 500px) {
		.plyr--audio {
			top: calc(35vw / 2 + 60px / 2);
		}
	}
}

</style>
