<!--
 - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<template>
	<div class="example-event-settings">
		<NcCheckboxRadioSwitch :checked="createExampleEvent"
			:disabled="savingConfig"
			type="switch"
			@update:model-value="updateCreateExampleEvent">
			{{ t('dav', 'Create default event when a user logs in for the first time') }}
		</NcCheckboxRadioSwitch>
		<em>
			{{ t('dav', 'The example event serves as a showcase of the features of Nextcloud Calendar. A default example event is shipped with Nextcloud. You can replace the default event with a custom event by uploading an ICS file below.') }}
		</em>
		<div class="example-event-settings__buttons">
			<NcButton v-if="createExampleEvent"
				type="primary"
				@click="showImportModal = true">
				<template #icon>
					<IconUpload :size="20" />
				</template>
				{{ t('dav', 'Import calendar event') }}
			</NcButton>
			<NcButton v-if="createExampleEvent && hasCustomEvent"
				:disabled="deleting"
				@click="deleteCustomEvent">
				<template #icon>
					<IconDelete :size="20" />
				</template>
				{{ t('dav', 'Restore default event') }}
			</NcButton>
		</div>
		<NcDialog :open.sync="showImportModal"
			:name="t('dav', 'Import calendar event')">
			<div class="import-event-modal">
				<p>
					{{ t('dav', 'Uploading a new event will overwrite the existing one.') }}
				</p>
				<input ref="event-file"
					:disabled="uploading"
					type="file"
					accept=".ics,text/calendar"
					class="import-event-modal__file-picker"
					@change="selectFile" />
				<div class="import-event-modal__buttons">
					<NcButton :disabled="uploading || !selectedFile"
						type="primary"
						@click="uploadCustomEvent()">
						<template #icon>
							<IconUpload :size="20" />
						</template>
						{{ t('dav', 'Upload event') }}
					</NcButton>
				</div>
			</div>
		</NcDialog>
	</div>
</template>

<script>
import {
	NcSettingsSection,
	NcCheckboxRadioSwitch,
	NcDialog,
	NcButton,
} from '@nextcloud/vue'
import { loadState } from '@nextcloud/initial-state'
import IconUpload from 'vue-material-design-icons/Upload.vue'
import IconDelete from 'vue-material-design-icons/Delete.vue'
import * as ExampleEventService from '../service/ExampleEventService.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import logger from '../service/logger.js'

export default {
	name: 'ExampleEventSettings',
	components: {
		NcSettingsSection,
		NcCheckboxRadioSwitch,
		NcButton,
		NcDialog,
		IconUpload,
		IconDelete,
	},
	data() {
		return {
			createExampleEvent: loadState('dav', 'create_example_event', false),
			hasCustomEvent: loadState('dav', 'has_custom_example_event', false),
			showImportModal: false,
			uploading: false,
			deleting: false,
			savingConfig: false,
			selectedFile: undefined,
		}
	},
	methods: {
		selectFile() {
			this.selectedFile = this.$refs['event-file']?.files[0]
		},
		async updateCreateExampleEvent() {
			this.savingConfig = true

			const enable = !this.createExampleEvent
			try {
				await ExampleEventService.setCreateExampleEvent(enable)
			} catch (error) {
				showError(t('dav', 'Failed to save example event creation setting'))
				logger.error('Failed to save example event creation setting', {
					error,
					enable,
				})
			} finally {
				this.savingConfig = false
			}

			this.createExampleEvent = enable
		},
		uploadCustomEvent() {
			if (!this.selectedFile) {
				return
			}

			this.uploading = true

			const reader = new FileReader()
			reader.addEventListener('load', async () => {
				const ics = reader.result

				try {
					await ExampleEventService.uploadExampleEvent(ics)
				} catch (error) {
					showError(t('dav', 'Failed to upload the example event'))
					logger.error('Failed to upload example ICS', {
						error,
						ics,
					})
					return
				} finally {
					this.uploading = false
				}

				showSuccess(t('dav', 'Custom example event was saved successfully'))
				this.showImportModal = false
				this.hasCustomEvent = true
			})
			reader.readAsText(this.selectedFile)
		},
		async deleteCustomEvent() {
			this.deleting = true

			try {
				await ExampleEventService.deleteExampleEvent()
			} catch (error) {
				showError(t('dav', 'Failed to delete the custom example event'))
				logger.error('Failed to delete the custom example event', {
					error,
				})
				return
			} finally {
				this.deleting = false
			}

			showSuccess(t('dav', 'Custom example event was deleted successfully'))
			this.hasCustomEvent = false
		},
	},
}
</script>

<style lang="scss" scoped>
.example-event-settings {
	margin-block: 2rem;

	&__buttons {
		display: flex;
		gap: calc(var(--default-grid-baseline) * 2);
		margin-top: calc(var(--default-grid-baseline) * 2);
	}
}

.import-event-modal {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 2);
	padding: calc(var(--default-grid-baseline) * 2);

	&__file-picker {
		width: 100%;
	}

	&__buttons {
		display: flex;
		justify-content: flex-end;
	}
}
</style>
