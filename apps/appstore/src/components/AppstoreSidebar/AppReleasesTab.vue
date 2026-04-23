<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreApp, IAppstoreAppRelease, IAppstoreExApp } from '../../apps.d.ts'

import { mdiClockFast } from '@mdi/js'
import { getLanguage, t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import MarkdownPreview from '../MarkdownPreview.vue'

const props = defineProps<{ app: IAppstoreApp | IAppstoreExApp }>()

const releases = computed(() => (props.app.releases ?? [])
	.filter((release) => {
		const values = Object.values(release.translations ?? {})
		return values.length > 0 && values.some(({ changelog }) => !!changelog)
	}))

/**
 * Create a changelog text from a release
 *
 * @param release - The release to create the changelog from
 */
function createChangelogFromRelease(release: IAppstoreAppRelease) {
	const localizedEntry = release.translations[getLanguage()]
	return localizedEntry?.changelog ?? release.translations.en?.changelog ?? ''
}
</script>

<template>
	<NcAppSidebarTab
		v-if="releases.length > 0"
		id="changelog"
		:name="t('appstore', 'Changelog')"
		:order="2">
		<template #icon>
			<NcIconSvgWrapper :path="mdiClockFast" :size="24" />
		</template>
		<div v-for="release in releases" :key="release.version" :class="$style.appReleasesTab">
			<h3 :class="$style.appReleasesTab__heading">
				{{ release.version }}
			</h3>
			<MarkdownPreview
				:class="$style.appReleasesTab__text"
				:minHeadingLevel="3"
				:text="createChangelogFromRelease(release)" />
		</div>
	</NcAppSidebarTab>
</template>

<style module>
.appReleasesTab__heading {
	border-bottom: 1px solid var(--color-border);
	font-size: 20px;
}

.appReleasesTab__text {
	/* Overwrite changelog heading styles */
	h4 {
		font-size: 19px;
	}

	h5 {
		font-size: 17px;
	}
}
</style>
