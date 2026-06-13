<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import LoginFlowContainer from '../components/LoginFlow/LoginFlowContainer.vue'
</script>

<template>
	<LoginFlowContainer :heading="t('core', 'Account connected')">
		<NcNoteCard type="info">
			{{ t('core', 'Your client should now be connected!') }}
			<br>
			{{ t('core', 'You can close this window.') }}
		</NcNoteCard>
	</LoginFlowContainer>
</template>
