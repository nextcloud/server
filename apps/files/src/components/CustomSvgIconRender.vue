<!--
  - @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<span class="custom-svg-icon" />
</template>

<script>
// eslint-disable-next-line import/named
import { sanitize } from 'dompurify'

export default {
	name: 'CustomSvgIconRender',
	props: {
		svg: {
			type: String,
			required: true,
		},
	},
	watch: {
		svg() {
			this.$el.innerHTML = sanitize(this.svg)
		},
	},
	mounted() {
		this.$el.innerHTML = sanitize(this.svg)
	},
}
</script>
<style lang="scss" scoped>
.custom-svg-icon {
	display: flex;
	align-items: center;
	align-self: center;
	justify-content: center;
	justify-self: center;
	width: 44px;
	height: 44px;
	opacity: 1;

	::v-deep svg {
		// mdi icons have a size of 24px
		// 22px results in roughly 16px inner size
		height: 22px;
		width: 22px;
		fill: currentColor;
	}
}

</style>
