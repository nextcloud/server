<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppSidebarTab id="desc"
		:name="t('settings', 'Description')"
		:order="0">
		<template #icon>
			<NcIconSvgWrapper :path="mdiTextShort" />
		</template>
		<div class="app-description">
			<Markdown :text="app.description" :min-heading="4" />
		</div>
	</NcAppSidebarTab>
</template>

<script setup lang="ts">
import type { IAppstoreApp } from '../../app-types'

import { mdiTextShort } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'

import NcAppSidebarTab from '@nextcloud/vue/dist/Components/NcAppSidebarTab.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import Markdown from '../Markdown.vue'

defineProps<{
	app: IAppstoreApp,
}>()
</script>

<style scoped lang="scss">
.app-description {
	padding: 12px;
}
</style>
