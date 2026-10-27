<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="unified-share-list">
		<UnifiedShareEntry
			v-for="share in sortedShares"
			:key="share.id"
			:share="share"
			:fileInfo="fileInfo"
			@refresh="$emit('refresh')" />
	</div>
</template>

<script setup lang="ts">
import type { SharingShare } from '../types/unifiedSharing.ts'

import { computed } from 'vue'
import UnifiedShareEntry from './UnifiedShareEntry.vue'
import { sortSharesByPermission } from '../lib/unifiedSharing.ts'

const props = defineProps<{
	shares: SharingShare[]
	fileInfo: object
}>()

defineEmits<{
	(e: 'refresh'): void
}>()

const sortedShares = computed(() => sortSharesByPermission(props.shares))
</script>
