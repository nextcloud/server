<!--
 - @copyright Copyright (c) 2020 Daniel Kesselberg <mail@danielkesselberg.de>
 -
 - @author Daniel Kesselberg <mail@danielkesselberg.de>
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
	<!-- Plyr currently replaces the parent. Wrapping to prevent this
	https://github.com/redxtech/vue-plyr/issues/259 -->
	<div v-if="src">
		<VuePlyr ref="plyr"
			:options="options">
			<audio ref="audio"
				:autoplay="active"
				:src="src"
				preload="metadata"
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

<script>
// eslint-disable-next-line n/no-missing-import
import '@skjnldsv/vue-plyr/dist/vue-plyr.css'
import logger from '../services/logger.js'

const VuePlyr = () => import(/* webpackChunkName: 'plyr' */'@skjnldsv/vue-plyr')

export default {
	name: 'Audios',

	components: {
		VuePlyr,
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

	mounted() {
		// Prevent swiping to the next/previous item when scrubbing the timeline or changing volume
		[...this.$el.querySelectorAll('.plyr__controls__item')].forEach(control => {
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
