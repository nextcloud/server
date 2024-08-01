<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcActions force-menu
		:type="isActive ? 'secondary' : 'tertiary'"
		:menu-name="filterName">
		<template #icon>
			<slot name="icon" />
		</template>
		<slot />

		<template v-if="isActive">
			<NcActionSeparator />
			<NcActionButton class="files-list-filter__clear-button"
				close-after-click
				@click="$emit('reset-filter')">
				{{ t('files', 'Clear filter') }}
			</NcActionButton>
		</template>
	</NcActions>
</template>

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionSeparator from '@nextcloud/vue/dist/Components/NcActionSeparator.js'

defineProps<{
	isActive: boolean
	filterName: string
}>()

defineEmits<{
	(event: 'reset-filter'): void
}>()
</script>

<style scoped>
.files-list-filter__clear-button :deep(.action-button__text) {
	color: var(--color-error-text);
}

:deep(.button-vue) {
	font-weight: normal !important;

	* {
		font-weight: normal !important;
	}
}
</style>
