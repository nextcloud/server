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
	<video
		v-if="path && canPlay"
		:autoplay="active"
		:controls="visibleControls"
		:poster="livePhotoPath"
		:preload="true"
		:style="{
			height: height + 'px',
			width: width + 'px'
		}"
		@canplay="doneLoading"
		@click.prevent="playPause"
		@dblclick.prevent="toggleFullScreen"
		@ended="donePlaying"
		@loadedmetadata="updateVideoSize"
		@mouseenter="showControls"
		@mouseleave="hideControls">

		<source :src="davPath" :type="mime">

		{{ t('viewer', 'Your browser does not support the video tag.') }}
	</video>

	<!-- Browser cannot play this file -->
	<Error v-else>
		{{ t('viewer', 'This video is not playable in your browser') }}
	</Error>
</template>

<script>
import Error from 'Components/Error'
import Mime from 'Mixins/Mime'
import PreviewUrl from 'Mixins/PreviewUrl'

const liveExt = ['jpg', 'jpeg', 'png']

export default {
	name: 'Videos',

	components: {
		Error
	},

	mixins: [Mime, PreviewUrl],

	data() {
		return {
			canPlay: true,
			visibleControls: false
		}
	},

	computed: {
		livePhoto() {
			return this.fileList.find(file => {
				// if same filename and extension is allowed
				return file.name.startsWith(this.name)
					&& liveExt.indexOf(file.name.split('.')[1] > -1)
			})
		},
		livePhotoPath() {
			return this.getPreviewIfAny(this.livePhoto)
		}
	},

	watch: {
		active: function(val, old) {
			// the item was hidden before and is now the current view
			if (val === true && old === false) {
				// if we cannot play the file, we announce we're done loading
				this.canPlayCheck()
				if (this.canPlay) {
					this.$el.play()
				}

			// the item was playing before and is now hidden
			} else if (val === false && old === true) {
				if (this.canPlay) {
					this.$el.pause()
				}
			}
		}
	},

	mounted() {
		this.canPlayCheck()
	},

	methods: {
		// Updates the dimensions of the modal
		updateVideoSize() {
			this.naturalHeight = this.$el.videoHeight
			this.naturalWidth = this.$el.videoWidth
			this.updateHeightWidth()
		},

		// Show/hide video controls
		showControls() {
			this.visibleControls = true
		},
		hideControls() {
			this.visibleControls = false
		},

		// Toggle play/pause
		playPause() {
			if (this.$el.paused) {
				this.$el.play()
			} else {
				this.$el.pause()
			}
		},

		donePlaying() {
			// reset and show poster after play
			this.$el.autoplay = false
			this.$el.load()
		},

		canPlayCheck() {
			if (this.$el.canPlayType
				&& this.$el.canPlayType(this.mime) !== '') {
				this.canPlay = true
			} else {
				this.canPlay = false
				this.doneLoading()
			}
		}
	}
}
</script>

<style scoped>
video {
	background-color: black;
	max-width: 100%;
	max-height: 100%;
	align-self: center;
	justify-self: center;
}
</style>
