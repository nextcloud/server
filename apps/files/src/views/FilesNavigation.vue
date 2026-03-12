<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcAppNavigation
		data-cy-files-navigation
		class="files-navigation"
		:aria-label="t('files', 'Files')">
		<template #search>
			<FilesNavigationSearch />
		</template>
		<template #default>
			<FilesNavigationList />

			<!-- Settings modal-->
			<FilesAppSettings
				:open.sync="settingsOpened"
				data-cy-files-navigation-settings
				@close="settingsOpened = false" />
		</template>

		<!-- Non-scrollable navigation bottom elements -->
		<template #footer>
			<ul class="app-navigation-entry__settings">
				<!-- User storage usage statistics -->
				<NavigationQuota />

				<!-- Files settings modal toggle-->
				<NcAppNavigationItem
					:name="t('files', 'Files settings')"
					data-cy-files-navigation-settings-button
					@click.prevent.stop="settingsOpened = true">
					<IconCog slot="icon" :size="20" />
				</NcAppNavigationItem>
			</ul>
		</template>
	</NcAppNavigation>
</template>

<script setup lang="ts">
import { emit } from '@nextcloud/event-bus'
import { getNavigation } from '@nextcloud/files'
import { t } from '@nextcloud/l10n'
import { computed, ref, watchEffect } from 'vue'
import { useRoute } from 'vue-router/composables'
import NcAppNavigation from '@nextcloud/vue/components/NcAppNavigation'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import IconCog from 'vue-material-design-icons/CogOutline.vue'
import FilesNavigationList from '../components/FilesNavigationList.vue'
import FilesNavigationSearch from '../components/FilesNavigationSearch.vue'
import NavigationQuota from '../components/NavigationQuota.vue'
import FilesAppSettings from './FilesAppSettings.vue'
import { useViews } from '../composables/useViews.ts'
import logger from '../logger.ts'
import { useActiveStore } from '../store/active.ts'
import { useSidebarStore } from '../store/sidebar.ts'

const sidebar = useSidebarStore()
const activeStore = useActiveStore()

const settingsOpened = ref(false)

const allViews = useViews()

const route = useRoute()
const currentViewId = computed(() => route?.params?.view || 'files')
watchEffect(() => {
	if (currentViewId.value !== activeStore.activeView?.id) {
		logger.debug(`Route view id ${currentViewId.value} is different from active view id ${activeStore.activeView?.id}, updating active view...`)
		const view = allViews.value.find(({ id }) => id === currentViewId.value)!
		if (view) {
			sidebar.close()
			getNavigation().setActive(view.id)
			emit('files:navigation:changed', view)
		}
	}
})
</script>

<style scoped lang="scss">
.app-navigation {
	:deep(.app-navigation-entry.active .button-vue.icon-collapse:not(:hover)) {
		color: var(--color-primary-element-text);
	}

	> ul.app-navigation__list {
		// Use flex gap value for more elegant spacing
		padding-bottom: var(--default-grid-baseline, 4px);
	}
}

.app-navigation-entry__settings {
	height: auto !important;
	overflow: hidden !important;
	padding-top: 0 !important;
	// Prevent shrinking or growing
	flex: 0 0 auto;
}

.files-navigation {
	:deep(.app-navigation__content > ul.app-navigation__list) {
		will-change: scroll-position;
	}
}
</style>
