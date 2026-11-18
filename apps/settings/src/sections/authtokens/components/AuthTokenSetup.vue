<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ITokenResponse } from '../store/authtoken.ts'

import { mdiPlus } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import AuthTokenAccessDialog from './AuthTokenAccessDialog.vue'
import AuthTokenSetupDialog from './AuthTokenSetupDialog.vue'

const createOpen = ref(false)
const newToken = ref<ITokenResponse | null>(null)
</script>

<template>
	<NcFormBoxButton
		id="generate-app-token-section"
		:label="t('settings', 'New app password')"
		@click="createOpen = true">
		<template #icon>
			<NcIconSvgWrapper :path="mdiPlus" :size="20" />
		</template>
	</NcFormBoxButton>

	<AuthTokenAccessDialog
		v-if="createOpen"
		v-model:open="createOpen"
		@created="newToken = $event" />
	<AuthTokenSetupDialog :token="newToken" @close="newToken = null" />
</template>
