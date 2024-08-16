<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div v-if="!enforceAcceptShares || allowCustomDirectory" id="files-sharing-personal-settings" class="section">
		<h2>{{ t('files_sharing', 'Sharing') }}</h2>
		<p v-if="!enforceAcceptShares">
			<input id="files-sharing-personal-settings-accept"
				v-model="accepting"
				class="checkbox"
				type="checkbox"
				@change="toggleEnabled">
			<label for="files-sharing-personal-settings-accept">{{ t('files_sharing', 'Accept shares from other accounts and groups by default') }}</label>
		</p>
		<p v-if="allowCustomDirectory">
			<SelectShareFolderDialogue />
		</p>
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import { showError } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'

import SelectShareFolderDialogue from './SelectShareFolderDialogue.vue'

export default {
	name: 'PersonalSettings',
	components: {
		SelectShareFolderDialogue,
	},

	data() {
		return {
			// Share acceptance config
			accepting: loadState('files_sharing', 'accept_default'),
			enforceAcceptShares: loadState('files_sharing', 'enforce_accept'),

			// Receiving share folder config
			allowCustomDirectory: loadState('files_sharing', 'allow_custom_share_folder'),
		}
	},

	methods: {
		async toggleEnabled() {
			try {
				await axios.put(generateUrl('/apps/files_sharing/settings/defaultAccept'), {
					accept: this.accepting,
				})
			} catch (error) {
				showError(t('files_sharing', 'Error while toggling options'))
				console.error(error)
			}
		},
	},
}
</script>

<style scoped lang="scss">
p {
	margin-top: 12px;
	margin-bottom: 12px;
}
</style>
