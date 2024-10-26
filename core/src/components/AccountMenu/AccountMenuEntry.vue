<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcListItem :id="href ? undefined : id"
		:anchor-id="id"
		:active="active"
		class="account-menu-entry"
		compact
		:href="href"
		:name="name"
		target="_self">
		<template #icon>
			<img class="account-menu-entry__icon"
				:class="{ 'account-menu-entry__icon--active': active }"
				:src="iconSource"
				alt="">
		</template>
		<template v-if="loading" #indicator>
			<NcLoadingIcon />
		</template>
	</NcListItem>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import NcListItem from '@nextcloud/vue/dist/Components/NcListItem.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'

const versionHash = loadState('core', 'versionHash', '')

export default {
	name: 'AccountMenuEntry',

	components: {
		NcListItem,
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
		iconSource() {
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
.account-menu-entry {
	&__icon {
		height: 16px;
		width: 16px;
		margin: calc((var(--default-clickable-area) - 16px) / 2); // 16px icon size
		filter: var(--background-invert-if-dark);

		&--active {
			filter: var(--primary-invert-if-dark);
		}
	}

	:deep(.list-item-content__main) {
		width: fit-content;
	}
}
</style>
