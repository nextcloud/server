<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="share-folder">
		<!-- Folder picking form -->
		<form class="share-folder__form" @reset.prevent.stop="resetFolder">
			<NcTextField class="share-folder__picker"
				type="text"
				:label="t('files_sharing', 'Set default folder for accepted shares')"
				:value="readableDirectory"
				@click.prevent="pickFolder" />

			<!-- Show reset button if folder is different -->
			<input v-if="readableDirectory !== defaultDirectory"
				class="share-folder__reset"
				type="reset"
				:value="t('files_sharing', 'Reset')"
				:aria-label="t('files_sharing', 'Reset folder to system default')">
		</form>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import path from 'path'
import { generateUrl } from '@nextcloud/router'
import { getFilePickerBuilder, showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

const defaultDirectory = loadState('files_sharing', 'default_share_folder', '/')
const directory = loadState('files_sharing', 'share_folder', defaultDirectory)

export default {
	name: 'SelectShareFolderDialogue',
	components: {
		NcTextField,
	},
	data() {
		return {
			directory,
			defaultDirectory,
		}
	},
	computed: {
		readableDirectory() {
			if (!this.directory) {
				return '/'
			}
			return this.directory
		},
	},
	methods: {
		async pickFolder() {

			// Setup file picker
			const picker = getFilePickerBuilder(t('files_sharing', 'Choose a default folder for accepted shares'))
				.startAt(this.readableDirectory)
				.setMultiSelect(false)
				.setType(1)
				.setMimeTypeFilter(['httpd/unix-directory'])
				.allowDirectories()
				.build()

			try {
				// Init user folder picking
				const dir = await picker.pick() || '/'
				if (!dir.startsWith('/')) {
					throw new Error(t('files_sharing', 'Invalid path selected'))
				}

				// Fix potential path issues and save results
				this.directory = path.normalize(dir)
				await axios.put(generateUrl('/apps/files_sharing/settings/shareFolder'), {
					shareFolder: this.directory,
				})
			} catch (error) {
				showError(error.message || t('files_sharing', 'Unknown error'))
			}
		},

		resetFolder() {
			this.directory = this.defaultDirectory
			axios.delete(generateUrl('/apps/files_sharing/settings/shareFolder'))
		},
	},
}
</script>

<style scoped lang="scss">
.share-folder {
	&__form {
		display: flex;
	}

	&__picker {
		cursor: pointer;
		max-width: 300px;
	}

	// Make the reset button looks like text
	&__reset {
		background-color: transparent;
		border: none;
		font-weight: normal;
		text-decoration: underline;
		font-size: inherit;
	}
}
</style>
