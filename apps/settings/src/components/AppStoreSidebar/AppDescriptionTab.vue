<!--
  - @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
  -
  - @license GNU AGPL version 3 or any later version
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
