<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="daemon-selection-list">
		<ul
			v-if="dockerDaemons.length > 0"
			:aria-label="t('settings', 'Registered Deploy daemons list')">
			<DaemonSelectionEntry
				v-for="daemon in dockerDaemons"
				:key="daemon.id"
				:daemon="daemon"
				:is-default="defaultDaemon.name === daemon.name"
				:app="app"
				:deploy-options="deployOptions"
				@close="closeModal" />
		</ul>
		<NcEmptyContent
			v-else
			class="daemon-selection-list__empty-content"
			:name="t('settings', 'No Deploy daemons configured')"
			:description="t('settings', 'Register a custom one or setup from available templates')">
			<template #icon>
				<FormatListBullet :size="20" />
			</template>
			<template #action>
				<NcButton :href="appApiAdminPage">
					{{ t('settings', 'Manage Deploy daemons') }}
				</NcButton>
			</template>
		</NcEmptyContent>
	</div>
</template>

<script setup>
import { generateUrl } from '@nextcloud/router'
import { computed, defineProps } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import FormatListBullet from 'vue-material-design-icons/FormatListBulleted.vue'
import DaemonSelectionEntry from './DaemonSelectionEntry.vue'
import { useAppApiStore } from '../../store/app-api-store.ts'

defineProps({
	app: {
		type: Object,
		required: true,
	},
	deployOptions: {
		type: Object,
		required: false,
		default: () => ({}),
	},
})

const emit = defineEmits(['close'])

const appApiStore = useAppApiStore()

const dockerDaemons = computed(() => appApiStore.dockerDaemons)
const defaultDaemon = computed(() => appApiStore.defaultDaemon)
const appApiAdminPage = computed(() => generateUrl('/settings/admin/app_api'))
/**
 *
 */
function closeModal() {
	emit('close')
}
</script>

<style scoped lang="scss">
.daemon-selection-list {
	max-height: 350px;
	overflow-y: scroll;
	padding: 2rem;

	&__empty-content {
		margin-top: 0;
		text-align: center;
	}
}
</style>
