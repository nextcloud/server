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
		:key="path"
		autoplay
		autobuffer
		preload
		:controls="visibleControls"
		@canplay="doneLoading"
		@mouseenter="showControls"
		@mouseleave="hideControls">

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
			visibleControls: false
		}
	},
	methods: {
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
	width: 100% !important;
	height: auto !important;
	object-fit: contain;
}
</style>
