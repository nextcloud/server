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
		v-if="path"
		autoplay
		preload
		:controls="visibleControls"
		:height="height"
		:width="width"
		@canplay="doneLoading"
		@mouseenter="showControls"
		@mouseleave="hideControls"
		@loadedmetadata="updateVideoSize">

		<source :src="path" :type="mime">

		{{ t('viewer', 'Your browser does not support the video tag.') }}
	</video>
</template>

<script>
import mime from 'Mixins/Mime'

export default {
	name: 'Videos',
	mixins: [
		mime
	],
	data() {
		return {
			height: null,
			width: null,
			visibleControls: false
		}
	},
	methods: {
		updateVideoSize() {
			const modalContainer = this.$parent.$el.querySelector('.modal-container')
			const parentHeight = modalContainer.clientHeight
			const parentWidth = modalContainer.clientWidth
			const videoHeight = this.$el.videoHeight
			const videoWidth = this.$el.videoWidth

			const heightRatio = parentHeight / videoHeight
			const widthRatio = parentWidth / videoWidth

			// if the video height is capped by the parent height
			// AND the video is bigger than the parent
			if (heightRatio < widthRatio && heightRatio < 1) {
				this.height = parentHeight

			// if the video width is capped by the parent width
			// AND the video is bigger than the parent
			} else if (heightRatio > widthRatio && widthRatio < 1) {
				this.width = parentWidth

			// RESET
			} else {
				this.height = null
				this.width = null
			}
		},
		showControls() {
			this.visibleControls = true
		},
		hideControls() {
			this.visibleControls = false
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
