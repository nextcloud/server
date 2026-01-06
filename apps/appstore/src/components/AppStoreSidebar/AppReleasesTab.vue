<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcAppSidebarTab
		v-if="hasChangelog"
		id="changelog"
		:name="t('settings', 'Changelog')"
		:order="2">
		<template #icon>
			<NcIconSvgWrapper :path="mdiClockFast" :size="24" />
		</template>
		<div v-for="release in app.releases" :key="release.version" class="app-sidebar-tabs__release">
			<h2>{{ release.version }}</h2>
			<MarkdownPreview
				class="app-sidebar-tabs__release-text"
				:text="createChangelogFromRelease(release)" />
		</div>
	</NcAppSidebarTab>
</template>

<script setup lang="ts">
import type { IAppstoreApp, IAppstoreAppRelease } from '../../app-types.ts'

import { mdiClockFast } from '@mdi/js'
import { getLanguage, translate as t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import MarkdownPreview from '../MarkdownPreview.vue'

const props = defineProps<{ app: IAppstoreApp }>()

const hasChangelog = computed(() => Object.values(props.app.releases?.[0]?.translations ?? {}).some(({ changelog }) => !!changelog))

const createChangelogFromRelease = (release: IAppstoreAppRelease) => release.translations?.[getLanguage()]?.changelog ?? release.translations?.en?.changelog ?? ''
</script>

<style scoped lang="scss">
.app-sidebar-tabs__release {
	h2 {
		border-bottom: 1px solid var(--color-border);
		font-size: 24px;
	}

	&-text {
		// Overwrite changelog heading styles
		:deep(h3) {
			font-size: 20px;
		}
		:deep(h4) {
			font-size: 17px;
		}
	}
}
</style>
