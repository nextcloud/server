<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
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
