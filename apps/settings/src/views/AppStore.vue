<!--
  - @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
  - @author Ferdinand Thiessen <opensource@fthiessen.de>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<!-- Apps list -->
	<NcAppContent class="app-settings-content"
		:page-heading="pageHeading">
		<AppList :category="currentCategory" />
	</NcAppContent>
</template>

<script setup lang="ts">
import { translate as t } from '@nextcloud/l10n'
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router/composables'
import { APPS_SECTION_ENUM } from '../constants/AppsConstants.js'
import { useAppsStore } from '../store/apps-store'

import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import AppList from '../components/AppList.vue'

const route = useRoute()
const store = useAppsStore()

/**
 * ID of the current active category, default is `installed`
 */
const currentCategory = computed(() => route.params?.category ?? 'installed')

/**
 * The H1 to be used on the website
 */
const pageHeading = computed(() => {
	if (currentCategory.value in APPS_SECTION_ENUM) {
		return APPS_SECTION_ENUM[currentCategory.value]
	}
	const category = store.getCategoryById(currentCategory.value)
	return category?.displayName ?? t('settings', 'Apps')
})
watch([pageHeading], () => {
	window.document.title = `${pageHeading.value} - Apps - Nextcloud`
})
</script>
