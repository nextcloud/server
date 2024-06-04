<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcContent app-name="updatenotification">
		<NcAppContent :page-heading="t('updatenotification', 'Changelog for app {app}', { app: appName })">
			<div class="changelog__wrapper">
				<h2 class="changelog__heading">
					{{ t('updatenotification', 'What\'s new in {app} version {version}', { app: appName, version: appVersion }) }}
				</h2>
				<Markdown :markdown="markdown" :min-heading-level="3" />
			</div>
		</NcAppContent>
	</NcContent>
</template>

<script setup lang="ts">
import { translate as t } from '@nextcloud/l10n'
import { loadState } from '@nextcloud/initial-state'

import NcAppContent from '@nextcloud/vue/dist/Components/NcAppContent.js'
import NcContent from '@nextcloud/vue/dist/Components/NcContent.js'
import Markdown from '../components/Markdown.vue'

const {
	appName,
	appVersion,
	text: markdown,
} = loadState<{ appName: string, appVersion: string, text: string }>('updatenotification', 'changelog')
</script>

<style scoped>
.changelog__wrapper {
	max-width: max(50vw,700px);
	margin-inline: auto;
}

.changelog__heading {
	font-size: 30px;
	margin-block: var(--app-navigation-padding, 8px) 1em;
}
</style>
