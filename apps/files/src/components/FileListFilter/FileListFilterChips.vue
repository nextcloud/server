<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcChip from '@nextcloud/vue/components/NcChip'
import { useFiltersStore } from '../../store/filters.ts'

const filterStore = useFiltersStore()
const activeChips = computed(() => filterStore.activeChips)
</script>

<template>
	<ul
		v-if="activeChips.length > 0"
		:class="$style.fileListFilterChips"
		:aria-label="t('files', 'Active filters')">
		<li v-for="(chip, index) of activeChips" :key="index">
			<NcChip
				:aria-label-close="t('files', 'Remove filter')"
				:icon-svg="chip.icon"
				:text="chip.text"
				@close="chip.onclick">
				<template v-if="chip.user" #icon>
					<NcAvatar
						disable-menu
						hide-status
						:size="24"
						:user="chip.user" />
				</template>
			</NcChip>
		</li>
	</ul>
</template>

<style module>
.fileListFilterChips {
	display: flex;
	gap: var(--default-grid-baseline);
}
</style>
