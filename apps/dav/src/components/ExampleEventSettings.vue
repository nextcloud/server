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
			{{ t('dav', "Add example event to user's calendar when they first log in") }}
		</NcCheckboxRadioSwitch>
		<div v-if="createExampleEvent"
			class="example-event-settings__buttons">
			<NcButton type="tertiary"
				:href="exampleEventDownloadUrl">
				<template #icon>
					<IconCalendarBlank :size="20" />
				</template>
				<span class="example-event-settings__buttons__download-link">
					example_event.ics
					<IconDownload :size="20" />
				</span>
			</NcButton>
			<NcButton type="secondary"
				@click="showImportModal = true">
				<template #icon>
					<IconUpload :size="20" />
				</template>
				{{ t('dav', 'Import calendar event') }}
			</NcButton>
			<NcButton v-if="hasCustomEvent"
				type="tertiary"
				:disabled="deleting"
				@click="deleteCustomEvent">
				<template #icon>
					<IconRestore :size="20" />
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
import { NcButton, NcCheckboxRadioSwitch, NcDialog } from '@nextcloud/vue'
import { loadState } from '@nextcloud/initial-state'
import IconDownload from 'vue-material-design-icons/Download.vue'
import IconCalendarBlank from 'vue-material-design-icons/CalendarBlank.vue'
import IconUpload from 'vue-material-design-icons/Upload.vue'
import IconRestore from 'vue-material-design-icons/Restore.vue'
import * as ExampleEventService from '../service/ExampleEventService.js'
import { showError, showSuccess } from '@nextcloud/dialogs'
import logger from '../service/logger.js'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'ExampleEventSettings',
	components: {
		NcButton,
		NcCheckboxRadioSwitch,
		NcDialog,
		IconDownload,
		IconCalendarBlank,
		IconUpload,
		IconRestore,
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
	computed: {
		exampleEventDownloadUrl() {
			return generateUrl('/apps/dav/api/exampleEvent/event')
		},
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

		&__download-link {
			display: flex;
			text-decoration: underline;
		}
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
