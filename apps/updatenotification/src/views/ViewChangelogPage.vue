<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import NcAppContent from '@nextcloud/vue/components/NcAppContent'
import NcContent from '@nextcloud/vue/components/NcContent'
import MarkdownPreview from '../../../appstore/src/components/MarkdownPreview.vue'

const {
	appName,
	appVersion,
	text: markdown,
} = loadState<{ appName: string, appVersion: string, text: string }>('updatenotification', 'changelog')
</script>

<template>
	<NcContent appName="updatenotification">
		<NcAppContent :pageHeading="t('updatenotification', 'Changelog for app {app}', { app: appName })">
			<div :class="$style.changelogPage">
				<h2 :class="$style.changelogPage__heading">
					{{ t('updatenotification', 'What\'s new in {app} version {version}', { app: appName, version: appVersion }) }}
				</h2>
				<MarkdownPreview :text="markdown" :minHeadingLevel="3" />
			</div>
		</NcAppContent>
	</NcContent>
</template>

<style module>
.changelogPage {
	max-width: max(50vw,700px);
	margin-inline: auto;
}

.changelogPage__heading {
	font-size: calc(var(--default-clickable-area) / 1.5);
	line-height: 1.5;
	margin-block: var(--app-navigation-padding, 8px) 1em;
}
</style>
