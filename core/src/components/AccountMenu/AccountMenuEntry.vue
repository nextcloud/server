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
		target="_self"
		@click="onClick">
		<template #icon>
			<NcLoadingIcon v-if="loading" :size="20" class="account-menu-entry__loading" />
			<slot v-else-if="$scopedSlots.icon" name="icon" />
			<img v-else
				class="account-menu-entry__icon"
				:class="{ 'account-menu-entry__icon--active': active }"
				:src="iconSource"
				alt="">
		</template>
	</NcListItem>
</template>

<script lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { defineComponent } from 'vue'

import NcListItem from '@nextcloud/vue/components/NcListItem'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

const versionHash = loadState('core', 'versionHash', '')

export default defineComponent({
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
			default: false,
		},
		icon: {
			type: String,
			default: '',
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
		onClick(e: MouseEvent) {
			this.$emit('click', e)

			// Allow to not show the loading indicator
			// in case the click event was already handled
			if (!e.defaultPrevented) {
				this.loading = true
			}
		},
	},
})
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

	&__loading {
		height: 20px;
		width: 20px;
		margin: calc((var(--default-clickable-area) - 20px) / 2); // 20px icon size
	}

	:deep(.list-item-content__main) {
		width: fit-content;
	}
}
</style>
