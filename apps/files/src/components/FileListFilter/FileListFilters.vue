<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IFileListFilterWithUi } from '@nextcloud/files'

import { mdiArrowLeft, mdiFilterVariant } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import { useFileListWidth } from '../../composables/useFileListWidth.ts'
import { useFiltersStore } from '../../store/filters.ts'

const filterStore = useFiltersStore()
const visualFilters = computed(() => filterStore.filtersWithUI)
const hasActiveFilters = computed(() => filterStore.activeChips.length > 0)

const selectedFilter = ref<IFileListFilterWithUi>()

const { isWide } = useFileListWidth()
const menuTriggerId = 'file-list-filters-menu-trigger'

const boundary = document.getElementById('app-content-vue')!
</script>

<template>
	<div :class="$style.fileListFilters" data-test-id="files-list-filters">
		<template v-if="isWide">
			<NcPopover v-for="filter of visualFilters" :key="filter.id" :boundary="boundary">
				<template #trigger>
					<NcButton variant="tertiary">
						<template #icon>
							<NcIconSvgWrapper :svg="filter.iconSvgInline" />
						</template>
						{{ filter.displayName }}
					</NcButton>
				</template>
				<template #default>
					<div :class="$style.fileListFilters__popoverContainer">
						<component :is="filter.tagName" :filter.prop="filter" />
					</div>
				</template>
			</NcPopover>
		</template>

		<NcPopover
			v-else
			:boundary="boundary"
			:popup-role="selectedFilter ? 'dialog' : 'menu'"
			@update:shown="selectedFilter = undefined">
			<template #trigger>
				<NcButton
					:id="menuTriggerId"
					:aria-label="t('files', 'Filters')"
					:pressed="hasActiveFilters"
					variant="tertiary">
					<template #icon>
						<NcIconSvgWrapper :path="mdiFilterVariant" />
					</template>
				</NcButton>
			</template>
			<template #default>
				<div v-if="selectedFilter" :class="$style.fileListFilters__popoverFilterView">
					<NcButton wide variant="tertiary" @click="selectedFilter = undefined">
						<template #icon>
							<NcIconSvgWrapper directional :path="mdiArrowLeft" />
						</template>
						{{ t('files', 'Back to filters') }}
					</NcButton>
					<component :is="selectedFilter.tagName" :filter.prop="selectedFilter" />
				</div>
				<template v-else>
					<ul :class="$style.fileListFilters__popoverContainer" :aria-labelledby="menuTriggerId" role="menu">
						<li v-for="filter of visualFilters" :key="filter.id" role="presentation">
							<NcButton
								role="menuitem"
								alignment="start"
								variant="tertiary"
								wide
								@click="selectedFilter = filter">
								<template #icon>
									<NcIconSvgWrapper :svg="filter.iconSvgInline" />
								</template>
								{{ filter.displayName }}
							</NcButton>
						</li>
					</ul>
				</template>
			</template>
		</NcPopover>
	</div>
</template>

<style module>
.fileListFilters {
	display: flex;
	flex-direction: row;
	gap: var(--default-grid-baseline);
	margin-inline-end: var(--default-grid-baseline);
	height: 100%;
	width: 100%;
}

.fileListFilters__popoverFilterView {
	box-sizing: border-box;
	display: flex;
	flex-direction: column;
	gap: calc(2 * var(--default-grid-baseline));
	padding: calc(var(--default-grid-baseline) / 2);
	min-width: calc(7 * var(--default-clickable-area));
}

.fileListFilters__popoverContainer {
	box-sizing: border-box;
	padding: calc(var(--default-grid-baseline) / 2);
	min-width: calc(7 * var(--default-clickable-area));
}

.fileListFilters__filter {
	display: flex;
	align-items: start;
	justify-content: start;
	gap: calc(var(--default-grid-baseline, 4px) * 2);

	> * {
		flex: 0 1 fit-content;
	}
}

.fileListFilters__active {
	display: flex;
	flex-direction: row;
	gap: calc(var(--default-grid-baseline, 4px) * 2);
}
</style>
