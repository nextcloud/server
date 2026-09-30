<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { computed, ref } from 'vue'
import NcListItem from '@nextcloud/vue/components/NcListItem'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'

const props = withDefaults(defineProps<{
	id: string
	name: string
	href: string
	active?: boolean
	icon?: string
}>(), {
	active: false,
	icon: '',
})

const emit = defineEmits<{
	click: [event: MouseEvent]
}>()

defineSlots<{
	icon?: () => unknown
}>()

const versionHash = loadState('core', 'versionHash', '')

const loading = ref(false)
const iconSource = computed(() => `${props.icon}?v=${versionHash}`)

/**
 * Show the loading indicator while navigating, unless a listener of the
 * parent already handled the click.
 *
 * @param event - The click event
 */
function onClick(event: MouseEvent) {
	emit('click', event)
	if (!event.defaultPrevented) {
		loading.value = true
	}
}
</script>

<template>
	<NcListItem
		:id="href ? undefined : `${id}-account-menu-entry`"
		:anchorId="id"
		:active="active"
		class="account-menu-entry"
		compact
		:href="href"
		:name="name"
		target="_self"
		@click="onClick">
		<template #icon>
			<NcLoadingIcon v-if="loading" :size="20" class="account-menu-entry__loading" />
			<slot v-else-if="$slots.icon" name="icon" />
			<img
				v-else
				class="account-menu-entry__icon"
				:class="{ 'account-menu-entry__icon--active': active }"
				:src="iconSource"
				alt="">
		</template>
	</NcListItem>
</template>

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
