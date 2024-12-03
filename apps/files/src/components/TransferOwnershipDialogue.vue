<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div>
		<h3>{{ t('files', 'Transfer ownership of a file or folder') }} </h3>
		<form @submit.prevent="submit">
			<p class="transfer-select-row">
				<span>{{ readableDirectory }}</span>
				<NcButton v-if="directory === undefined"
					class="transfer-select-row__choose_button"
					@click.prevent="start">
					{{ t('files', 'Choose file or folder to transfer') }}
				</NcButton>
				<NcButton v-else @click.prevent="start">
					{{ t('files', 'Change') }}
				</NcButton>
			</p>
			<p class="new-owner-row">
				<label for="targetUser">
					<span>{{ t('files', 'New owner') }}</span>
				</label>
				<NcSelect v-model="selectedUser"
					input-id="targetUser"
					:options="formatedUserSuggestions"
					:multiple="false"
					:loading="loadingUsers"
					label="displayName"
					:user-select="true"
					class="middle-align"
					@search="findUserDebounced" />
			</p>
			<p>
				<NcButton native-type="submit"
					type="primary"
					:disabled="!canSubmit">
					{{ submitButtonText }}
				</NcButton>
			</p>
		</form>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import debounce from 'debounce'
import { generateOcsUrl } from '@nextcloud/router'
import { getFilePickerBuilder, showSuccess, showError } from '@nextcloud/dialogs'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import Vue from 'vue'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'

import logger from '../logger.ts'

const picker = getFilePickerBuilder(t('files', 'Choose a file or folder to transfer'))
	.setMultiSelect(false)
	.setType(1)
	.allowDirectories()
	.build()

export default {
	name: 'TransferOwnershipDialogue',
	components: {
		NcSelect,
		NcButton,
	},
	data() {
		return {
			directory: undefined,
			directoryPickerError: undefined,
			submitError: undefined,
			loadingUsers: false,
			selectedUser: null,
			userSuggestions: {},
			config: {
				minSearchStringLength: parseInt(OC.config['sharing.minSearchStringLength'], 10) || 0,
			},
		}
	},
	computed: {
		canSubmit() {
			return !!this.directory && !!this.selectedUser
		},
		formatedUserSuggestions() {
			return Object.keys(this.userSuggestions).map((uid) => {
				const user = this.userSuggestions[uid]
				return {
					user: user.uid,
					displayName: user.displayName,
					icon: 'icon-user',
				}
			})
		},
		submitButtonText() {
			if (!this.canSubmit) {
				return t('files', 'Transfer')
			}
			const components = this.readableDirectory.split('/')
			return t('files', 'Transfer {path} to {userid}', { path: components[components.length - 1], userid: this.selectedUser.displayName })
		},
		readableDirectory() {
			if (!this.directory) {
				return ''
			}
			return this.directory.substring(1)
		},
	},
	created() {
		this.findUserDebounced = debounce(this.findUser, 300)
		this.findUser('')
	},
	methods: {
		start() {
			this.directoryPickerError = undefined

			picker.pick()
				.then(dir => dir === '' ? '/' : dir)
				.then(dir => {
					logger.debug(`path ${dir} selected for transferring ownership`)
					if (!dir.startsWith('/')) {
						throw new Error(t('files', 'Invalid path selected'))
					}
					// /ocs/v2.php/apps/files/api/v1/transferownership
					// /ocs/v2.php/apps/files/api/v1/transferownership
					this.directory = dir
				}).catch(error => {
					logger.error(`Selecting object for transfer aborted: ${error.message || 'Unknown error'}`, { error })

					this.directoryPickerError = error.message || t('files', 'Unknown error')
					showError(this.directoryPickerError)
				})
		},
		async findUser(query) {
			this.query = query.trim()

			if (query.length < this.config.minSearchStringLength) {
				return
			}

			this.loadingUsers = true
			try {
				const response = await axios.get(generateOcsUrl('apps/files_sharing/api/v1/sharees'), {
					params: {
						format: 'json',
						itemType: 'file',
						search: query,
						perPage: 20,
						lookup: false,
					},
				})

				this.userSuggestions = {}
				response.data.ocs.data.exact.users.concat(response.data.ocs.data.users).forEach(user => {
					Vue.set(this.userSuggestions, user.value.shareWith, {
						uid: user.value.shareWith,
						displayName: user.label,
					})
				})
			} catch (error) {
				logger.error('could not fetch users', { error })
			} finally {
				this.loadingUsers = false
			}
		},
		submit() {
			if (!this.canSubmit) {
				logger.warn('ignoring form submit')
			}

			this.submitError = undefined
			const data = {
				path: this.directory,
				recipient: this.selectedUser.user,
			}
			logger.debug('submit transfer ownership form', data)

			const url = generateOcsUrl('apps/files/api/v1/transferownership')

			axios.post(url, data)
				.then(resp => resp.data)
				.then(data => {
					logger.info('Transfer ownership request sent', { data })

					this.directory = undefined
					this.selectedUser = null
					showSuccess(t('files', 'Ownership transfer request sent'))
				})
				.catch(error => {
					logger.error('Could not send ownership transfer request', { error })

					if (error?.response?.status === 403) {
						this.submitError = t('files', 'Cannot transfer ownership of a file or folder you do not own')
					} else {
						this.submitError = error.message || t('files', 'Unknown error')
					}
					showError(this.submitError)
				})
		},
	},
}
</script>

<style scoped lang="scss">
.middle-align {
	vertical-align: middle;
}

p {
	margin-top: 12px;
	margin-bottom: 12px;
}

.new-owner-row {
	display: flex;
	flex-wrap: wrap;

	label {
		display: flex;
		align-items: center;
		margin-bottom: calc(var(--default-grid-baseline) * 2);

		span {
			margin-inline-end: 8px;
		}
	}

	.multiselect {
		flex-grow: 1;
		max-width: 280px;
	}
}

.transfer-select-row {
	span {
		margin-inline-end: 8px;
	}

	&__choose_button {
		width: min(100%, 400px) !important;
	}
}
</style>
