<!--
  - SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IOauthClient } from '../views/AdminSettings.vue'

import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import IconTrashCanOutline from 'vue-material-design-icons/TrashCanOutline.vue'

defineProps<{
	/**
	 * The OAuth client to display
	 */
	client: IOauthClient
}>()

defineEmits<{
	delete: []
}>()
</script>

<template>
	<tr>
		<td>{{ client.name }}</td>
		<td>
			<code :class="$style.oAuthItem__code">{{ client.redirectUri }}</code>
		</td>
		<td>
			<code :class="$style.oAuthItem__code">{{ client.clientId }}</code>
		</td>
		<td>
			<NcPasswordField
				v-if="client.clientSecret"
				:class="$style.oAuthItem__clientSecret"
				:aria-label="t('oauth2', 'Secret key')"
				as-text
				:model-value="client.clientSecret"
				show-trailing-button />
			<span v-else>*****</span>
		</td>
		<td>
			<NcButton
				:aria-label="t('oauth2', 'Delete')"
				:title="t('oauth2', 'Delete')"
				variant="error"
				@click="$emit('delete')">
				<template #icon>
					<IconTrashCanOutline :size="20" />
				</template>
			</NcButton>
		</td>
	</tr>
</template>

<style module>
.oAuthItem__code {
	display: inline-block;
	overflow-x: scroll;
	padding-block: var(--default-grid-baseline);
	text-wrap: nowrap;
	vertical-align: middle;
	width: 100%;
}

.oAuthItem__clientSecret {
	min-width: 200px;
}
</style>
