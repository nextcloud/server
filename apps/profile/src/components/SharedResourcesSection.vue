<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { SharedResource } from '../services/sharedResources.ts'

import { translate as t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcListItem from '@nextcloud/vue/components/NcListItem'

const props = defineProps<{
	displayName: string
	resources: SharedResource[]
}>()

const title = computed(() => t('profile', 'You & {user}', { user: props.displayName }))
</script>

<template>
	<section class="shared-resources" :aria-label="title">
		<h3 class="shared-resources__title">
			{{ title }}
		</h3>
		<ul>
			<NcListItem
				v-for="resource in resources"
				:key="`${resource.href}-${resource.label}`"
				class="shared-resources__item"
				compact
				:name="resource.label"
				:href="resource.href"
				target="_self">
				<template #icon>
					<img
						class="shared-resources__icon"
						:src="resource.img"
						alt=""
						decoding="async"
						loading="lazy">
				</template>
				<template #subname>
					<span>{{ resource.text }}</span>
				</template>
			</NcListItem>
		</ul>
	</section>
</template>

<style scoped lang="scss">
.shared-resources {
	margin-top: 24px;
	width: 100%;
	max-width: 300px;

	&__title {
		font-size: var(--default-font-size);
		font-weight: bold;
		margin: 0 0 8px;
		overflow-wrap: anywhere;
	}

	&__list {
		display: flex;
		flex-direction: column;
		margin: 0;
		padding: 0;
		list-style: none;
	}

	&__icon {
		width: 32px;
		height: 32px;
		object-fit: contain;
		border-radius: var(--border-radius);
	}
}

@media only screen and (max-width: 1024px) {
	.shared-resources {
		max-width: none;
	}
}
</style>
