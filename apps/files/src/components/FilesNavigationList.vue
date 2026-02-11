<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IView } from '@nextcloud/files'

import { getCanonicalLocale, getLanguage, t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcAppNavigationList from '@nextcloud/vue/components/NcAppNavigationList'
import FilesNavigationListItem from './FilesNavigationListItem.vue'
import { useVisibleViews } from '../composables/useViews.ts'

const views = useVisibleViews()
const rootViews = computed(() => views.value
	.filter((view) => !view.parent)
	.sort(sortViews))

const collator = Intl.Collator(
	[getLanguage(), getCanonicalLocale()],
	{ numeric: true, usage: 'sort' },
)

/**
 * Sort views by their order property if available, otherwise sort alphabetically by name.
 *
 * @param a - first view
 * @param b - second view
 */
function sortViews(a: IView, b: IView): number {
	if (a.order !== undefined && b.order === undefined) {
		return -1
	} else if (a.order === undefined && b.order !== undefined) {
		return 1
	}
	return collator.compare(a.name, b.name)
}
</script>

<template>
	<NcAppNavigationList
		:class="$style.filesNavigationList"
		:aria-label="t('files', 'Views')">
		<FilesNavigationListItem
			v-for="view in rootViews"
			:key="view.id"
			:view="view" />
	</NcAppNavigationList>
</template>

<style module>
.filesNavigationList {
	height: 100%; /* Fill all available space for sticky views */
}
</style>
