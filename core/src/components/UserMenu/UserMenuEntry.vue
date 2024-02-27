<!--
  - @copyright 2023 Christopher Ng <chrng8@gmail.com>
  -
  - @author Christopher Ng <chrng8@gmail.com>
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
	<li :id="id"
		class="menu-entry">
		<a v-if="href"
			:href="href"
			:class="{ active }"
			@click.exact="handleClick">
			<NcLoadingIcon v-if="loading"
				class="menu-entry__loading-icon"
				:size="18" />
			<img v-else :src="cachedIcon" alt="">
			{{ name }}
		</a>
		<button v-else>
			<img :src="cachedIcon" alt="">
			{{ name }}
		</button>
	</li>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

const versionHash = loadState('core', 'versionHash', '')

export default {
	name: 'UserMenuEntry',

	components: {
		NcLoadingIcon,
	},

	props: {
		id: {
			type: String,
			required: true,
		},
		name: {
			type: String,
			required: true,
		},
		href: {
			type: String,
			required: true,
		},
		active: {
			type: Boolean,
			required: true,
		},
		icon: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			loading: false,
		}
	},

	computed: {
		cachedIcon() {
			return `${this.icon}?v=${versionHash}`
		},
	},

	methods: {
		handleClick() {
			this.loading = true
		},
	},
}
</script>

<style lang="scss" scoped>
.menu-entry {
	&__loading-icon {
		margin-right: 8px;
	}
}
</style>
