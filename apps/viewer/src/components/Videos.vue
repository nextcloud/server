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
	<!-- Plyr currently replaces the parent. Wrapping to prevent this
	https://github.com/redxtech/vue-plyr/issues/259 -->
	<div v-if="davPath">
		<VuePlyr
			ref="plyr"
			:options="options"
			:style="{
				height: height + 'px',
				width: width + 'px'
			}">
			<video
				ref="video"
				:autoplay="active"
				:playsinline="true"
				:poster="livePhotoPath"
				:src="davPath"
				preload="metadata"
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

<script>
import Vue from 'vue'
import VuePlyr from '@skjnldsv/vue-plyr'
import '@skjnldsv/vue-plyr/dist/vue-plyr.css'

const liveExt = ['jpg', 'jpeg', 'png']
const liveExtRegex = new RegExp(`\\.(${liveExt.join('|')})$`, 'i')

Vue.use(VuePlyr)

export default {
	name: 'Videos',

	computed: {
		livePhoto() {
			return this.fileList.find(file => {
				// if same filename and extension is allowed
				return file.filename !== this.filename
					&& file.basename.startsWith(this.name)
					&& liveExtRegex.test(file.basename)
			})
		},
		livePhotoPath() {
			return this.livePhoto && this.getPreviewIfAny(this.livePhoto)
		},
		player() {
			return this.$refs.plyr.player
		},
		options() {
			return {
				autoplay: this.active === true,
				controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'captions', 'settings', 'fullscreen'],
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

	methods: {
		// Updates the dimensions of the modal
		updateVideoSize() {
			this.naturalHeight = this.$refs.video && this.$refs.video.videoHeight
			this.naturalWidth = this.$refs.video && this.$refs.video.videoWidth
			this.updateHeightWidth()
		},

		donePlaying() {
			// reset and show poster after play
			this.$refs.video.autoplay = false
			this.$refs.video.load()
		},

		onLoadedMetadata() {
			this.updateVideoSize()
		},
	},
}
</script>

<style scoped lang="scss">
video {
	background-color: black;
	max-width: 100%;
	max-height: 100%;
	align-self: center;
	justify-self: center;
	/* over arrows in tiny screens */
	z-index: 20050;
}

::v-deep {
	.plyr:-webkit-full-screen video,
	.plyr:fullscreen video {
		height: 100% !important;
		width: 100% !important;
	}
	.plyr__progress__container {
		flex: 1 1;
	}
	.plyr__volume {
		min-width: 80px;
	}
	// plyr buttons style
	.plyr--video .plyr__progress__buffer,
	.plyr--video .plyr__control {
		&.plyr__tab-focus,
		&:hover,
		&[aria-expanded=true] {
			background-color: var(--color-primary-element);
			color: var(--color-primary-text);
			box-shadow: none !important;
		}
	}
	.plyr__control--overlaid {
		background-color: var(--color-primary-element);
	}
	// plyr volume control
	.plyr--full-ui input[type=range] {
		color: var(--color-primary-element);
	}
}
</style>
