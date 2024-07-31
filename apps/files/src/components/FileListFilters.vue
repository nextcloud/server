<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="file-list-filters">
		<div class="file-list-filters__filter" data-cy-files-filters>
			<span v-for="filter of visualFilters"
				:key="filter.id"
				ref="filterElements" />
		</div>
		<ul v-if="activeChips.length > 0" class="file-list-filters__active" :aria-label="t('files', 'Active filters')">
			<li v-for="(chip, index) of activeChips" :key="index">
				<NcChip :aria-label-close="t('files', 'Remove filter')"
					:icon-svg="chip.icon"
					:text="chip.text"
					@close="chip.onclick">
					<template v-if="chip.user" #icon>
						<NcAvatar disable-menu
							:show-user-status="false"
							:size="24"
							:user="chip.user" />
					</template>
				</NcChip>
			</li>
		</ul>
	</div>
</template>

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { computed, ref, watchEffect } from 'vue'
import { useFiltersStore } from '../store/filters.ts'

import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcChip from '@nextcloud/vue/dist/Components/NcChip.js'

const filterStore = useFiltersStore()
const visualFilters = computed(() => filterStore.filtersWithUI)
const activeChips = computed(() => filterStore.activeChips)

const filterElements = ref<HTMLElement[]>([])
watchEffect(() => {
	filterElements.value
		.forEach((el, index) => visualFilters.value[index].mount(el))
})
</script>

<style scoped lang="scss">
.file-list-filters {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
	height: 100%;
	width: 100%;

	&__filter {
		display: flex;
		align-items: start;
		justify-content: start;
		gap: calc(var(--default-grid-baseline, 4px) * 2);

		> * {
			flex: 0 1 fit-content;
		}
	}

	&__active {
		display: flex;
		flex-direction: row;
		gap: calc(var(--default-grid-baseline, 4px) * 2);
	}
}
</style>
