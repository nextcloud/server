<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { ITokenResponse } from '../store/authtoken.ts'

import { t } from '@nextcloud/l10n'
import { ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import AuthTokenAccessDialog from './AuthTokenAccessDialog.vue'
import AuthTokenSetupDialog from './AuthTokenSetupDialog.vue'

const createOpen = ref(false)
const newToken = ref<ITokenResponse | null>(null)
</script>

<template>
	<div id="generate-app-token-section">
		<NcButton variant="primary" @click="createOpen = true">
			{{ t('settings', 'Create new app password') }}
		</NcButton>

		<AuthTokenAccessDialog
			v-if="createOpen"
			v-model:open="createOpen"
			@created="newToken = $event" />
		<AuthTokenSetupDialog :token="newToken" @close="newToken = null" />
	</div>
</template>

<style lang="scss" scoped>
	#generate-app-token-section {
		padding-top: 16px;
	}
</style>
