<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcModal v-if="opened"
		:clear-view-delay="-1"
		class="templates-picker"
		size="large"
		@close="close">
		<form class="templates-picker__form"
			:style="style"
			@submit.prevent.stop="onSubmit">
			<h2>{{ t('files', 'Pick a template for {name}', { name: nameWithoutExt }) }}</h2>

			<!-- Templates list -->
			<ul class="templates-picker__list">
				<TemplatePreview v-bind="emptyTemplate"
					ref="emptyTemplatePreview"
					:checked="checked === emptyTemplate.fileid"
					@confirm-click="onConfirmClick"
					@check="onCheck" />

				<TemplatePreview v-for="template in provider.templates"
					:key="template.fileid"
					v-bind="template"
					:checked="checked === template.fileid"
					:ratio="provider.ratio"
					@confirm-click="onConfirmClick"
					@check="onCheck" />
			</ul>

			<!-- Cancel and submit -->
			<div class="templates-picker__buttons">
				<input type="submit"
					class="primary"
					:value="t('files', 'Create')"
					:aria-label="t('files', 'Create a new file with the selected template')">
			</div>
		</form>

		<NcEmptyContent v-if="loading" class="templates-picker__loading" icon="icon-loading">
			{{ t('files', 'Creating file') }}
		</NcEmptyContent>
	</NcModal>
</template>

<script lang="ts">
import type { TemplateFile } from '../types.ts'

import { getCurrentUser } from '@nextcloud/auth'
import { showError, spawnDialog } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { File } from '@nextcloud/files'
import { translate as t } from '@nextcloud/l10n'
import { generateRemoteUrl } from '@nextcloud/router'
import { normalize, extname, join } from 'path'
import { defineComponent } from 'vue'
import { createFromTemplate, getTemplates, getTemplateFields } from '../services/Templates.js'

import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcModal from '@nextcloud/vue/components/NcModal'
import TemplatePreview from '../components/TemplatePreview.vue'
import TemplateFiller from '../components/TemplateFiller.vue'
import logger from '../logger.ts'

const border = 2
const margin = 8

export default defineComponent({
	name: 'TemplatePicker',

	components: {
		NcEmptyContent,
		NcModal,
		TemplatePreview,
	},

	props: {
		/**
		 * The parent folder where to create the node
		 */
		parent: {
			type: Object,
			default: () => null,
		},
	},

	data() {
		return {
			// Check empty template by default
			checked: -1,
			loading: false,
			name: null as string|null,
			opened: false,
			provider: null as TemplateFile|null,
		}
	},

	computed: {
		extension() {
			return extname(this.name ?? '')
		},

		nameWithoutExt() {
			// Strip extension from name if defined
			return !this.extension
				? this.name!
				: this.name!.slice(0, 0 - this.extension.length)
		},

		emptyTemplate() {
			return {
				basename: t('files', 'Blank'),
				fileid: -1,
				filename: t('files', 'Blank'),
				hasPreview: false,
				mime: this.provider?.mimetypes[0] || this.provider?.mimetypes,
			}
		},

		selectedTemplate() {
			if (!this.provider) {
				return null
			}

			return this.provider.templates!.find((template) => template.fileid === this.checked)
		},

		/**
		 * Style css vars bind
		 *
		 * @return {object}
		 */
		style() {
			if (!this.provider) {
				return {}
			}

			// Fallback to 16:9 landscape ratio
			const ratio = this.provider.ratio ? this.provider.ratio : 1.77
			// Landscape templates should be wider than tall ones
			// We fit 3 templates per row at max for landscape and 4 for portrait
			const width = ratio > 1 ? margin * 30 : margin * 20
			return {
				'--margin': margin + 'px',
				'--width': width + 'px',
				'--border': border + 'px',
				'--fullwidth': width + 2 * margin + 2 * border + 'px',
				'--height': this.provider.ratio ? Math.round(width / this.provider.ratio) + 'px' : null,
			}
		},
	},

	methods: {
		t,

		/**
		 * Open the picker
		 *
		 * @param {string} name the file name to create
		 * @param {object} provider the template provider picked
		 */
		async open(name: string, provider) {
			this.checked = this.emptyTemplate.fileid
			this.name = name
			this.provider = provider

			const templates = await getTemplates()
			const fetchedProvider = templates.find((fetchedProvider) => fetchedProvider.app === provider.app && fetchedProvider.label === provider.label)
			if (fetchedProvider === null) {
				throw new Error('Failed to match provider in results')
			}
			this.provider = fetchedProvider

			// If there is no templates available, just create an empty file
			if (fetchedProvider.templates.length === 0) {
				this.onSubmit()
				return
			}

			// Else, open the picker
			this.opened = true

			// Set initial focus to the empty template preview
			this.$nextTick(() => {
				this.$refs.emptyTemplatePreview?.focus()
			})
		},

		/**
		 * Close the picker and reset variables
		 */
		close() {
			this.checked = this.emptyTemplate.fileid
			this.loading = false
			this.name = null
			this.opened = false
			this.provider = null
		},

		/**
		 * Manages the radio template picker change
		 *
		 * @param fileid the selected template file id
		 */
		onCheck(fileid: number) {
			this.checked = fileid
		},

		onConfirmClick(fileid: number) {
			if (fileid === this.checked) {
				this.onSubmit()
			}
		},

		async createFile(templateFields = []) {
			const currentDirectory = new URL(window.location.href).searchParams.get('dir') || '/'

			// If the file doesn't have an extension, add the default one
			if (this.nameWithoutExt === this.name) {
				logger.warn('Fixed invalid filename', { name: this.name, extension: this.provider?.extension })
				this.name = `${this.name}${this.provider?.extension ?? ''}`
			}

			try {
				const fileInfo = await createFromTemplate(
					normalize(`${currentDirectory}/${this.name}`),
					this.selectedTemplate?.filename as string ?? '',
					this.selectedTemplate?.templateType as string ?? '',
					templateFields,
				)
				logger.debug('Created new file', fileInfo)

				const owner = getCurrentUser()?.uid || null
				const node = new File({
					id: fileInfo.fileid,
					source: generateRemoteUrl(join(`dav/files/${owner}`, fileInfo.filename)),
					root: `/files/${owner}`,
					mime: fileInfo.mime,
					mtime: new Date(fileInfo.lastmod * 1000),
					owner,
					size: fileInfo.size,
					permissions: fileInfo.permissions,
					attributes: {
						// Inherit some attributes from parent folder like the mount type and real owner
						'mount-type': this.parent?.attributes?.['mount-type'],
						'owner-id': this.parent?.attributes?.['owner-id'],
						'owner-display-name': this.parent?.attributes?.['owner-display-name'],
						...fileInfo,
						'has-preview': fileInfo.hasPreview,
					},
				})

				// Update files list
				emit('files:node:created', node)

				// Open the new file
				window.OCP.Files.Router.goToRoute(
					null, // use default route
					{ view: 'files', fileid: node.fileid },
					{ dir: node.dirname, openfile: 'true' },
				)

				// Close the picker
				this.close()
			} catch (error) {
				logger.error('Error while creating the new file from template', { error })
				showError(t('files', 'Unable to create new file from template'))
			} finally {
				this.loading = false
			}
		},

		async onSubmit() {
			const fileId = this.selectedTemplate?.fileid

			// Only request field extraction if there is a valid template
			// selected and it's not the blank template
			let fields = []
			if (fileId && fileId !== this.emptyTemplate.fileid) {
				fields = await getTemplateFields(fileId)
			}

			if (fields.length > 0) {
				spawnDialog(TemplateFiller, {
					fields,
					onSubmit: this.createFile,
				})
			} else {
				this.loading = true
				await this.createFile()
			}
		},
	},
})
</script>

<style lang="scss" scoped>
.templates-picker {
	&__form {
		padding: calc(var(--margin) * 2);
		// Will be handled by the buttons
		padding-bottom: 0;

		h2 {
			text-align: center;
			font-weight: bold;
			margin: var(--margin) 0 calc(var(--margin) * 2);
		}
	}

	&__list {
		display: grid;
		grid-gap: calc(var(--margin) * 2);
		grid-auto-columns: 1fr;
		// We want maximum 5 columns. Putting 6 as we don't count the grid gap. So it will always be lower than 6
		max-width: calc(var(--fullwidth) * 6);
		grid-template-columns: repeat(auto-fit, var(--fullwidth));
		// Make sure all rows are the same height
		grid-auto-rows: 1fr;
		// Center the columns set
		justify-content: center;
	}

	&__buttons {
		display: flex;
		justify-content: end;
		padding: calc(var(--margin) * 2) var(--margin);
		position: sticky;
		bottom: 0;
		background-image: linear-gradient(0deg, var(--gradient-main-background));

		button, input[type='submit'] {
			height: 44px;
		}
	}

	// Make sure we're relative for the loading emptycontent on top
	:deep(.modal-container) {
		position: relative;
	}

	&__loading {
		position: absolute;
		top: 0;
		inset-inline-start: 0;
		justify-content: center;
		width: 100%;
		height: 100%;
		margin: 0;
		background-color: var(--color-main-background-translucent);
	}
}

</style>
