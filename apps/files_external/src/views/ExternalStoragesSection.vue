<!--
  - @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
  -
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
	<NcSettingsSection :doc-url="settings.docUrl"
		:name="t('files_external', 'External storage')"
		:description="t('files_external', 'External storage enables you to mount external storage services and devices as secondary Nextcloud storage devices. You may also allow people to mount their own external storage services.')">
		<!-- Dependency error messages -->
		<NcNoteCard v-for="message, index of dependencyIssues"
			:key="index"
			type="error">
			{{ message }}
		</NcNoteCard>

		<!-- Missing modules for backends -->
		<NcNoteCard v-for="(dependants, module) in missingModules"
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
					dependants.length
				) }}
			</p>
			<ul class="files-external__dependant-list" :aria-label="t('files_external', 'Dependant backends')">
				<li v-for="backend of dependants" :key="backend">{{ backend }}</li>
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

<script lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'

import UserMountSettings from '../components/UserMountSettings.vue'
import filesExternalSvg from '../../img/app-dark.svg?raw'

const settings = loadState('files_external', 'settings', {
	docUrl: '',
	dependencyIssues: {
		messages: null as string[]|null,
		modules: null as Record<string, string[]>|null,
	},
	isAdmin: false,
})

export default defineComponent({
	name: 'ExternalStoragesSection',

	components: {
		UserMountSettings,

		NcEmptyContent,
		NcIconSvgWrapper,
		NcNoteCard,
		NcSettingsSection,
	},

	setup() {
		// non reactive props
		return {
			/** List of dependency issue messages */
			dependencyIssues: settings.dependencyIssues?.messages ?? [],
			/** Map of missing modules -> list of dependant backends */
			missingModules: settings.dependencyIssues?.modules ?? {},

			settings,
			filesExternalSvg,
		}
	},

	methods: {
		t,
		n,
	},
})
</script>

<style scoped lang="scss">
.files-external__dependant-list {
	list-style: disc;
	margin-inline-start: 22px;
}
</style>
