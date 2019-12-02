<!--
  - @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
  -
  - @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
	<div>
		<h3>{{ t('files', 'Transfer ownership') }} </h3>
		<p>
			{{ t('files', 'Here you can select a directory that is transferred to another user. It may take some time until the process is done.') }}
		</p>
		<form @submit.prevent="submit">
			<ol>
				<li>
					<div class="step-header">
						{{ t('files', 'Directory to move') }}
					</div>
					<span v-if="directory === undefined">{{ t('files', 'No directory selected') }}</span>
					<span v-else>{{ directory }}</span>
					<button class="primary" @click.prevent="start">
						{{ t('files', 'Select') }}
					</button>
					<span class="error">{{ directoryPickerError }}</span>
				</li>
				<li>
					<div class="step-header">
						{{ t('files', 'Target user') }}
					</div>
					<input id="files-transfer-user" v-model="uid" type="text">
				</li>
				<li>
					<input type="submit"
						class="primary"
						:value="t('files', 'Submit')"
						:disabled="!canSubmit">
					<span class="error">{{ submitError }}</span>
				</li>
			</ol>
		</form>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import { generateOcsUrl } from '@nextcloud/router'
import { getFilePickerBuilder } from '@nextcloud/dialogs'

import logger from '../logger'

const picker = getFilePickerBuilder(t('files', 'Select directory to transfer'))
	.setMultiSelect(false)
	.setModal(true)
	.setType(1)
	.allowDirectories()
	.build()

export default {
	name: 'TransferOwnershipDialogue',
	data() {
		return {
			directory: undefined,
			directoryPickerError: undefined,
			submitError: undefined,
			uid: ''
		}
	},
	computed: {
		canSubmit() {
			return !!this.directory && !!this.uid
		}
	},
	methods: {
		start() {
			this.directoryPickerError = undefined

			picker.pick()
				.then(dir => dir === '' ? '/' : dir)
				.then(dir => {
					logger.debug(`path ${dir} selected for transfer ownership`)
					if (!dir.startsWith('/')) {
						throw new Error(t('files', 'Invalid path selected'))
					}
					// /ocs/v2.php/apps/files/api/v1/transferownership
					// /ocs/v2.php/apps/files/api/v1/transferownership
					this.directory = dir
				}).catch(error => {
					logger.error(`Selecting dir for transfer aborted: ${error.message || 'Unknown error'}`, { error })

					this.directoryPickerError = error.message || t('files', 'Unknown error')
				})
		},
		submit() {
			if (!this.canSubmit) {
				logger.warn('ignoring form submit')
			}

			this.submitError = undefined
			const data = {
				path: this.directory,
				recipient: this.uid
			}
			logger.debug('submit transfer ownership form', data)

			const url = generateOcsUrl('apps/files/api/v1/', 2) + 'transferownership'

			axios.post(url, data)
				.then(resp => resp.data)
				.then(data => {
					logger.info('Transfer ownership request sent', { data })

					this.directory = undefined
					this.recipient = undefined
					OCP.Toast.success(t('files', 'Ownership transfer request sent'))
				})
				.catch(error => {
					logger.error('Could not send ownership transfer request', { error })

					this.submitError = error.message || t('files', 'Unknown error')
				})
		}
	}
}
</script>

<style scoped>

</style>
