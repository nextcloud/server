<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div id="files-personal-settings" class="section">
		<h2>{{ t('files', 'Files') }}</h2>
		<TransferOwnershipDialogue />
	</div>
</template>

<script>
import TransferOwnershipDialogue from './TransferOwnershipDialogue.vue'

export default {
	name: 'PersonalSettings',
	components: {
		TransferOwnershipDialogue,
	},
}
</script>
