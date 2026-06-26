<!--
 - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import { NcButton } from '@nextcloud/vue'
import IconDownload from 'vue-material-design-icons/TrayArrowDown.vue'

defineProps<{
	/**
	 * The href link for the download
	 */
	href: string
}>()
</script>

<template>
	<NcButton variant="tertiary" :href>
		<template #icon>
			<slot name="icon" />
		</template>
		<div class="download-button">
			<span class="download-button__label">
				<slot name="default" />
			</span>
			<IconDownload
				class="download-button__icon"
				:size="20" />
		</div>
	</NcButton>
</template>

<style lang="scss" scoped>
.download-button {
	display: flex;
	max-width: 200px;

	&__label {
		font-weight: initial;
		text-overflow: ellipsis;
		white-space: nowrap;
		overflow: hidden;
	}

	&__icon {
		margin-top: 2px;
		margin-inline-start: var(--default-grid-baseline);
	}
}
</style>
