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
	<img ref="img" :src="path" :height="height"
		:width="width" @load="updateImgSize">
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
			width: null
		}
	},
	methods: {
		updateImgSize() {
			const modalContainer = this.$parent.$el.querySelector('.modal-container')
			const parentHeight = modalContainer.clientHeight
			const parentWidth = modalContainer.clientWidth
			const naturalHeight = this.$el.naturalHeight
			const naturalWidth = this.$el.naturalWidth

			const heightRatio = parentHeight / naturalHeight
			const widthRatio = parentWidth / naturalWidth

			// if the image height is capped by the parent height
			// AND the image is bigger than the parent
			if (heightRatio < widthRatio && widthRatio < 1) {
				this.height = parentHeight

			// if the image width is capped by the parent width
			// AND the image is bigger than the parent
			} else if (heightRatio > widthRatio && heightRatio < 1) {
				this.width = parentWidth

			// RESET
			} else {
				this.height = null
				this.width = null
			}

			this.doneLoading()
		}
	}
}
</script>

<style scoped>
img {
	max-width: 100%;
	max-height: 100%;
	align-self: center;
	justify-self: center;
}
</style>
