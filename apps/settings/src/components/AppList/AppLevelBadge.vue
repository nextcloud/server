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
	<span v-if="isSupported || isFeatured"
		class="app-level-badge"
		:class="{ 'app-level-badge--supported': isSupported }"
		:title="badgeTitle">
		<NcIconSvgWrapper :path="badgeIcon" :size="20" />
		{{ badgeText }}
	</span>
</template>

<script setup lang="ts">
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'

import { mdiCheck, mdiStarShooting } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { computed } from 'vue'

const props = defineProps<{
	/**
	 * The app level
	 */
	level?: number
}>()

const isSupported = computed(() => props.level === 300)
const isFeatured = computed(() => props.level === 200)
const badgeIcon = computed(() => isSupported.value ? mdiStarShooting : mdiCheck)
const badgeText = computed(() => isSupported.value ? t('settings', 'Supported') : t('settings', 'Featured'))
const badgeTitle = computed(() => isSupported.value
	? t('settings', 'This app is supported via your current Nextcloud subscription.')
	: t('settings', 'Featured apps are developed by and within the community. They offer central functionality and are ready for production use.'))
</script>

<style scoped lang="scss">
.app-level-badge {
	color: var(--color-text-maxcontrast);
	background-color: transparent;
	border: 1px solid var(--color-text-maxcontrast);
	border-radius: var(--border-radius);

	display: flex;
	flex-direction: row;
	gap: 6px;
	padding: 3px 6px;

	&--supported {
		border-color: var(--color-success);
		color: var(--color-success);
	}

	// Fix the svg wrapper
	:deep(.icon-vue) {
		min-width: unset;
		min-height: unset;
	}
}
</style>
