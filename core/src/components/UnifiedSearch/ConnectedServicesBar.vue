<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { computed } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import IconCogOutline from 'vue-material-design-icons/CogOutline.vue'

const props = defineProps<{
	active: boolean
}>()

const emit = defineEmits<{
	toggle: []
}>()

const label = computed(() => (props.active
	? t('core', 'Less from connected services')
	: t('core', 'More from connected services')))

const settingsUrl = generateUrl('/settings/user/connected-accounts')
const settingsLabel = t('core', 'Connected services settings')
</script>

<template>
	<div class="connected-services-bar">
		<NcButton
			variant="secondary"
			wide
			@click="emit('toggle')">
			{{ label }}
		</NcButton>
		<NcButton
			variant="secondary"
			:aria-label="settingsLabel"
			:href="settingsUrl"
			:title="settingsLabel"
			target="_blank">
			<template #icon>
				<IconCogOutline :size="20" />
			</template>
		</NcButton>
	</div>
</template>

<style lang="scss" scoped>
.connected-services-bar {
	display: flex;
	gap: var(--default-grid-baseline);
	width: 100%;
	margin-block-start: calc(var(--default-grid-baseline) * 3);
}
</style>
