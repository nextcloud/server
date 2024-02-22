<!--
  - @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
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
	<NcAppSidebarTab v-if="hasChangelog"
		id="changelog"
		:name="t('settings', 'Changelog')"
		:order="2">
		<template #icon>
			<NcIconSvgWrapper :path="mdiClockFast" :size="24" />
		</template>
		<div v-for="release in app.releases" :key="release.version" class="app-sidebar-tabs__release">
			<h2>{{ release.version }}</h2>
			<Markdown class="app-sidebar-tabs__release-text"
				:text="createChangelogFromRelease(release)" />
		</div>
	</NcAppSidebarTab>
</template>

<script setup lang="ts">
import type { IAppstoreApp, IAppstoreAppRelease } from '../../app-types.ts'

import { mdiClockFast } from '@mdi/js'
import { getLanguage, translate as t } from '@nextcloud/l10n'
import { computed } from 'vue'

import NcAppSidebarTab from '@nextcloud/vue/dist/Components/NcAppSidebarTab.js'
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'
import Markdown from '../Markdown.vue'

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const props = defineProps<{ app: IAppstoreApp }>()

const hasChangelog = computed(() => Object.values(props.app.releases[0]?.translations ?? {}).some(({ changelog }) => !!changelog))

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
