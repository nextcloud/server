<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
  -->
<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { n, t } from '@nextcloud/l10n'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import UserMountSettings from '../components/UserMountSettings.vue'
import filesExternalSvg from '../../img/app-dark.svg?raw'

const settings = loadState('files_external', 'settings', {
	docUrl: '',
	dependencyIssues: {
		messages: null as string[] | null,
		modules: null as Record<string, string[]> | null,
	},
	isAdmin: false,
})

/** List of dependency issue messages */
const dependencyIssues = settings.dependencyIssues?.messages ?? []
/** Map of missing modules -> list of dependant backends */
const missingModules = settings.dependencyIssues?.modules ?? {}
</script>

<template>
	<NcSettingsSection
		:doc-url="settings.docUrl"
		:name="t('files_external', 'External storage')"
		:description="t('files_external', 'External storage enables you to mount external storage services and devices as secondary Nextcloud storage devices. You may also allow people to mount their own external storage services.')">
		<!-- Dependency error messages -->
		<NcNoteCard
			v-for="message, index of dependencyIssues"
			:key="index"
			type="error">
			{{ message }}
		</NcNoteCard>

		<!-- Missing modules for backends -->
		<NcNoteCard
			v-for="(dependants, module) in missingModules"
			:key="module"
			type="warning">
			<p>
				<template v-if="module === 'curl'">
					{{ t('files_external', 'The cURL support in PHP is not enabled or installed.') }}
				</template>
				<template v-else-if="module === 'ftp'">
					{{ t('files_external', 'The FTP support in PHP is not enabled or installed.') }}
				</template>
				<template v-else>
					{{ t('files_external', '{module} is not installed.', { module }) }}
				</template>
				{{ n(
					'files_external',
					'Please ask your system administrator to install it as otherwise mounting the following backend is not possible:',
					'Please ask your system administrator to install it as otherwise mounting the following backends is not possible:',
					dependants.length,
				) }}
			</p>
			<ul class="files-external__dependant-list" :aria-label="t('files_external', 'Dependant backends')">
				<li v-for="backend of dependants" :key="backend">
					{{ backend }}
				</li>
			</ul>
		</NcNoteCard>

		<!-- For user settings if the user has no permission or for user and admin settings if no storage was configured -->
		<NcEmptyContent :description="t('files_external', 'No external storage configured or you do not have the permission to configure them')">
			<template #icon>
				<NcIconSvgWrapper :svg="filesExternalSvg" :size="64" />
			</template>
		</NcEmptyContent>

		<UserMountSettings v-if="settings.isAdmin" />
	</NcSettingsSection>
</template>

<style scoped lang="scss">
.files-external__dependant-list {
	list-style: disc;
	margin-inline-start: 22px;
}
</style>
