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
	<img
		:src="path"
		:style="{
			height: height + 'px',
			width: width + 'px'
		}"
		@load="updateImgSize">
</template>

<script>
import mime from 'Mixins/Mime'

export default {
	name: 'Videos',
	mixins: [
		mime
	],
	methods: {
		updateImgSize() {
			const naturalHeight = this.$el.naturalHeight
			const naturalWidth = this.$el.naturalWidth
			this.updateHeightWidth(naturalHeight, naturalWidth)

			this.doneLoading()
		}
	}
}
</script>

<style scoped lang="scss">
$checkered-size: 20px;
$checkered-color: #999;

img {
	max-width: 100%;
	max-height: 100%;
	align-self: center;
	justify-self: center;
	background-image: linear-gradient(45deg, #{$checkered-color} 25%, transparent 25%),
		linear-gradient(45deg, transparent 75%, #{$checkered-color} 75%),
		linear-gradient(45deg, transparent 75%, #{$checkered-color} 75%),
		linear-gradient(45deg, #{$checkered-color} 25%, #fff 25%);
	background-size: 2 * $checkered-size 2 * $checkered-size;
	background-position: 0 0, 0 0, -#{$checkered-size} -#{$checkered-size}, $checkered-size $checkered-size;
}
</style>
