<!--
  - @copyright 2021 Hinrich Mahler <nextcloud@mahlerhome.de>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program.  If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
	<div class="share-folder">
		<span>{{ t('files_sharing', 'Set default folder for accepted shares') }} </span>

		<!-- Folder picking form -->
		<form class="share-folder__form" @reset.prevent.stop="resetFolder">
			<input class="share-folder__picker"
				type="text"
				:placeholder="readableDirectory"
				@click.prevent="pickFolder">

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

const defaultDirectory = loadState('files_sharing', 'default_share_folder', '/')
const directory = loadState('files_sharing', 'share_folder', defaultDirectory)

export default {
	name: 'SelectShareFolderDialogue',
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
			const picker = getFilePickerBuilder(t('files', 'Choose a default folder for accepted shares'))
				.startAt(this.readableDirectory)
				.setMultiSelect(false)
				.setModal(true)
				.setType(1)
				.setMimeTypeFilter(['httpd/unix-directory'])
				.allowDirectories()
				.build()

			try {
				// Init user folder picking
				const dir = await picker.pick() || '/'
				if (!dir.startsWith('/')) {
					throw new Error(t('files', 'Invalid path selected'))
				}

				// Fix potential path issues and save results
				this.directory = path.normalize(dir)
				await axios.put(generateUrl('/apps/files_sharing/settings/shareFolder'), {
					shareFolder: this.directory,
				})
			} catch (error) {
				showError(error.message || t('files', 'Unknown error'))
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
		min-width: 266px;
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
