<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcContent from '@nextcloud/vue/components/NcContent'
import AppstoreNavigation from './views/AppstoreNavigation.vue'
import AppstoreSidebar from './views/AppstoreSidebar.vue'
import { APPSTORE_CATEGORY_NAMES } from './constants.ts'

const route = useRoute()

const currentCategory = computed(() => [route.params.category].flat()[0] ?? 'discover')
const heading = computed(() => APPSTORE_CATEGORY_NAMES[currentCategory.value] ?? currentCategory.value)
const pageTitle = computed(() => `${heading.value} - ${t('appstore', 'App store')}`)

const showSidebar = computed(() => !!route.params.id)
</script>

<template>
	<NcContent app-name="appstore">
		<AppstoreNavigation />
		<NcAppContent
			:class="$style.appstoreApp__content"
			:page-heading="t('appstore', 'App store')"
			:page-title>
			<h2 v-if="heading" :class="$style.appstoreApp__heading">
				{{ heading }}
			</h2>
			<router-view />
		</NcAppContent>
		<AppstoreSidebar v-if="showSidebar" />
	</NcContent>
</template>

<style module>
.appstoreApp__content {
	padding-inline-end: var(--body-container-margin);
}

.appstoreApp__heading {
	margin-block-start: var(--app-navigation-padding);
	margin-inline-start: calc(var(--default-clickable-area) + var(--app-navigation-padding) * 2);
	min-height: var(--default-clickable-area);
	line-height: var(--default-clickable-area);
	vertical-align: center;
}
</style>
