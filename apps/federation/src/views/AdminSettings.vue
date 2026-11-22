<!--
  * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  * SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ITrustedServer } from '../services/api.ts'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import AddTrustedServerForm from '../components/AddTrustedServerForm.vue'
import TrustedServer from '../components/TrustedServer.vue'
import { TrustedServerStatus } from '../services/api.ts'

const adminSettings = loadState<{ docUrl: string, trustedServers: ITrustedServer[] }>('federation', 'adminSettings')
const trustedServers = ref(adminSettings.trustedServers)
const showPendingServerInfo = computed(() => trustedServers.value.some((server) => server.status === TrustedServerStatus.STATUS_PENDING))

/**
 * Handle add trusted server form submission
 *
 * @param server - The server to add
 */
async function onAdd(server: ITrustedServer) {
	trustedServers.value.unshift(server)
}

/**
 * Handle delete trusted server event
 *
 * @param server - The server to delete
 */
function onDelete(server: ITrustedServer) {
	trustedServers.value = trustedServers.value.filter((s) => s.id !== server.id)
}
</script>

<template>
	<NcSettingsSection
		:name="t('federation', 'Trusted servers')"
		:docUrl="adminSettings.docUrl"
		:description="t('federation', 'Federation allows you to connect with other trusted servers to exchange the account directory. For example this will be used to auto-complete external accounts for federated sharing. It is not necessary to add a server as trusted server in order to create a federated share.')">
		<NcNoteCard
			v-if="showPendingServerInfo"
			type="info"
			:text="t('federation', 'Each server must validate the other. This process may require a few cron cycles.')" />

		<TransitionGroup
			:class="$style.federationAdminSettings__trustedServersList"
			:aria-label="t('federation', 'Trusted servers')"
			tag="ul"
			:enterFromClass="$style.transition_hidden"
			:enterActiveClass="$style.transition_active"
			:leaveActiveClass="$style.transition_active"
			:leaveToClass="$style.transition_hidden">
			<TrustedServer
				v-for="server in trustedServers"
				:key="server.id"
				:class="$style.federationAdminSettings__trustedServersListItem"
				:server="server"
				@delete="onDelete" />
		</TransitionGroup>

		<AddTrustedServerForm @add="onAdd" />
	</NcSettingsSection>
</template>

<style module>
.federationAdminSettings__trustedServersList {
	display: flex;
	flex-direction: column;
	gap: var(--default-grid-baseline);
	width: fit-content;
}

.federationAdminSettings__trustedServersListItem {
	width: 100%;
}

.transition_active {
	transition: all 0.5s ease;
}

.transition_hidden {
	opacity: 0;
	transform: translateX(30px);
}
</style>
