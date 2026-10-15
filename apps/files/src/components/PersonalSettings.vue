<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { NcSettingsSection } from '@nextcloud/vue'
import TransferOwnershipDialogue from './TransferOwnershipDialogue.vue'
</script>

<template>
	<NcSettingsSection :name="t('files', 'Files')">
		<TransferOwnershipDialogue />
	</NcSettingsSection>
</template>
