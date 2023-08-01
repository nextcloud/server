<!--
  - @copyright Copyright (c) 2023 Ferdinand Thiessen <opensource@fthiessen.de>
  -
  - @author Ferdinand Thiessen <opensource@fthiessen.de>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->
<template>
	<CustomSvgIconRender class="favorite-marker-icon" :svg="StarSvg" />
</template>

<script>
import StarSvg from '@mdi/svg/svg/star.svg?raw'
import CustomSvgIconRender from './CustomSvgIconRender.vue'

/**
 * A favorite icon to be used for overlaying favorite entries like the file preview / icon
 * It has a stroke around the star icon to ensure enough contrast for accessibility.
 *
 * If the background has a hover state you might want to also apply it to the stroke like this:
 * ```scss
 * .parent:hover :deep(.favorite-marker-icon svg path) {
 *      stroke: var(--color-background-hover);
 * }
 * ```
 */
export default {
	name: 'FavoriteIcon',
	components: {
		CustomSvgIconRender,
	},
	data() {
		return {
			StarSvg,
		}
	},
	mounted() {
		// MDI default viewbox is "0 0 24 24" but we add a stroke of 10px so we must adjust it
		const el = this.$el.querySelector('svg')
		el.setAttribute('viewBox', '-4 -4 30 30')
		el.setAttribute('width', '25')
		el.setAttribute('height', '25')
	},
}
</script>
<style lang="scss" scoped>
.favorite-marker-icon {
	color: #a08b00;
	width: fit-content;
	height: fit-content;

	:deep() {
		svg {
			// We added a stroke for a11y so we must increase the size to include the stroke
			width: 26px;
			height: 26px;

			// Sow a border around the icon for better contrast
			path {
				stroke: var(--color-main-background);
				stroke-width: 8px;
				stroke-linejoin: round;
				paint-order: stroke;
			}
		}
	}
}
</style>
