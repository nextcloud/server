<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreApp, IAppstoreExApp } from '../../apps.d.ts'

import { mdiTextShort } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import NcAppSidebarTab from '@nextcloud/vue/components/NcAppSidebarTab'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import MarkdownPreview from '../MarkdownPreview.vue'

defineProps<{
	app: IAppstoreApp | IAppstoreExApp
}>()
</script>

<template>
	<NcAppSidebarTab
		id="desc"
		:name="t('appstore', 'Description')"
		:order="0">
		<template #icon>
			<NcIconSvgWrapper :path="mdiTextShort" />
		</template>
		<div :class="$style.appDescriptionTab">
			<MarkdownPreview :text="app.description" :minHeadingLevel="3" />
		</div>
	</NcAppSidebarTab>
</template>

<style module>
.appDescriptionTab {
	padding: 12px;
}
</style>
