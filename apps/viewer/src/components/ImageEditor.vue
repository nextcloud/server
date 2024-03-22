<template>
	<div ref="editor" class="viewer__image-editor" v-bind="themeDataAttr" />
</template>
<script>
import { basename, dirname, extname, join } from 'path'
import { emit } from '@nextcloud/event-bus'
import { Node } from '@nextcloud/files'
import { showError, showSuccess } from '@nextcloud/dialogs'
import axios from '@nextcloud/axios'

import logger from '../services/logger.js'
import translations from '../models/editorTranslations.js'
import { rawStat } from '../services/FileInfo.ts'

let TABS, TOOLS

export default {
	name: 'ImageEditor',

	props: {
		fileid: {
			type: [String, Number],
			required: true,
		},
		mime: {
			type: String,
			required: true,
		},
		src: {
			type: String,
			required: true,
		},
	},

	data() {
		return {
			imageEditor: null,
		}
	},

	computed: {
		config() {
			return {
				source: this.src,

				defaultSavedImageName: this.defaultSavedImageName,
				defaultSavedImageType: this.defaultSavedImageType,
				// We use our own translations
				useBackendTranslations: false,

				// Watch resize
				observePluginContainerSize: true,

				// Default tab and tool
				defaultTabId: TABS.ADJUST,
				defaultToolId: TOOLS.CROP,

				// Displayed tabs, disabling watermark
				tabsIds: Object.values(TABS)
					.filter(tab => tab !== TABS.WATERMARK)
					.sort((a, b) => a.localeCompare(b)),

				// onBeforeSave: this.onBeforeSave,
				onClose: this.onClose,
				// onModify: this.onModify,
				onSave: this.onSave,

				// Translations
				translations,

				theme: {
					palette: {
						'bg-secondary': 'var(--color-main-background)',
						'bg-primary': 'var(--color-background-dark)',
						'bg-hover': 'var(--color-background-hover)',
						'bg-stateless': 'var(--color-background-dark)',
						// Accent
						'accent-primary': 'var(--color-primary-element)',
						'accent-stateless': 'var(--color-primary-element)',
						'border-active-bottom': 'var(--color-primary-element)',
						// Active state
						'bg-primary-active': 'var(--color-background-dark)',
						'bg-primary-hover': 'var(--color-background-hover)',
						'accent-primary-active': 'var(--color-main-text)',
						'accent-primary-hover': 'var(--color-primary-element)',

						warning: 'var(--color-error)',
					},
					typography: {
						fontFamily: 'var(--font-face)',
					},
				},
			}
		},

		defaultSavedImageName() {
			return basename(this.src, extname(this.src))
		},
		defaultSavedImageType() {
			return extname(this.src).slice(1) || 'jpeg'
		},

		hasHighContrastEnabled() {
			const themes = OCA?.Theming?.enabledThemes || []
			return themes.find(theme => theme.indexOf('highcontrast') !== -1)
		},

		themeDataAttr() {
			if (this.hasHighContrastEnabled) {
				return {
					'data-theme-dark-highcontrast': true,
				}
			}
			return {
				'data-theme-dark': true,
			}
		},
	},

	async mounted() {
		// Lazy load the image editor
		const FilerobotImageEditor = (await import(/* webpackChunkName: 'filerobot' */'filerobot-image-editor')).default
		TABS = FilerobotImageEditor.TABS
		TOOLS = FilerobotImageEditor.TOOLS

		this.imageEditor = new FilerobotImageEditor(
			this.$refs.editor,
			this.config,
		)
		this.imageEditor.render()
		window.addEventListener('keydown', this.handleKeydown, true)
		window.addEventListener('DOMNodeInserted', this.handleSfxModal)

	},

	beforeDestroy() {
		if (this.imageEditor) {
			this.imageEditor.terminate()
		}
		window.removeEventListener('keydown', this.handleKeydown, true)
	},

	methods: {
		onClose(closingReason, haveNotSavedChanges) {
			if (haveNotSavedChanges) {
				this.onExitWithoutSaving()
				return
			}
			window.removeEventListener('keydown', this.handleKeydown, true)
			this.$emit('close')
		},

		/**
		 * User saved the image
		 *
		 * @see https://github.com/scaleflex/filerobot-image-editor#onsave
		 * @param {object} props destructuring object
		 * @param {string} props.fullName the file name
		 * @param {HTMLCanvasElement} props.imageCanvas the image canvas
		 * @param {string} props.mimeType the image mime type
		 * @param {number} props.quality the image saving quality
		 */
		async onSave({ fullName, imageCanvas, mimeType, quality }) {
			const { origin, pathname } = new URL(this.src)
			const putUrl = origin + join(dirname(pathname), fullName)
			logger.debug('Saving image...', { putUrl, src: this.src, fullName })

			// toBlob is not very smart...
			mimeType = mimeType.replace('jpg', 'jpeg')

			// Sanity check, 0 < quality < 1
			quality = Math.max(Math.min(quality, 1), 0) || 1

			try {
				const blob = await new Promise(resolve => imageCanvas.toBlob(resolve, mimeType, quality))
				const response = await axios.put(putUrl, new File([blob], fullName))

				logger.info('Edited image saved!', { response })
				showSuccess(t('viewer', 'Image saved'))
				if (putUrl !== this.src) {
					emit('files:node:created', { fileid: parseInt(response?.headers?.['oc-fileid']?.split('oc')[0]) || null })
				} else {
					this.$emit('updated')
					const updatedFile = await rawStat(origin, decodeURI(pathname))

					const node = new Node({
						id: Number.parseInt(this.fileid),
						source: this.src,
						mtime: new Date(updatedFile.lastmod),
						...updatedFile,
						attributes: {
							...updatedFile,
							...updatedFile.props,
						},
					})

					emit('files:node:updated', node)
				}
			} catch (error) {
				logger.error('Error saving image', { error })
				showError(t('viewer', 'Error saving image'))
			}
		},

		/**
		 * Show warning if unsaved changes
		 */
		onExitWithoutSaving() {
			OC.dialogs.confirmDestructive(
				translations.changesLoseConfirmation + '\n\n' + translations.changesLoseConfirmationHint,
				t('viewer', 'Unsaved changes'),
				{
					type: OC.dialogs.YES_NO_BUTTONS,
					confirm: t('viewer', 'Drop changes'),
					confirmClasses: 'error',
					cancel: translations.cancel,
				},
				(decision) => {
					if (!decision) {
						return
					}
					this.onClose('warning-ignored', false)
				},
			)
		},

		// Key Handlers, override default Viewer arrow and escape key
		handleKeydown(event) {
			// Enter needs to be reached through as otherwise saving text does not work
			if (event.key !== 'Enter') {
				event.stopImmediatePropagation()
			}
			// escape key
			if (event.key === 'Escape') {
				// Since we cannot call the closeMethod and know if there
				// are unsaved changes, let's fake a close button trigger.
				event.preventDefault()
				document.querySelector('.FIE_topbar-close-button').click()
			}

			// ctrl + S = save
			if (event.ctrlKey && event.key === 's') {
				event.preventDefault()
				document.querySelector('.FIE_topbar-save-button').click()
			}

			// ctrl + Z = undo
			if (event.ctrlKey && event.key === 'z') {
				event.preventDefault()
				document.querySelector('.FIE_topbar-undo-button').click()
			}
		},

		/**
		 * Watch out for Modal inject in document root
		 * That way we can adjust the focusTrap
		 *
		 * @param {Event} event Dom insertion event
		 */
		handleSfxModal(event) {
			if (event.target?.classList && event.target.classList.contains('SfxModal-Wrapper')) {
				emit('viewer:trapElements:changed', event.target)
			}
		},
	},
}
</script>

<style lang="scss" scoped>
// Take full screen size ()
.viewer__image-editor {
	position: absolute;
	z-index: 10100;
	top: calc(var(--header-height) * -1);
	bottom: calc(var(--header-height) * -1);
	left: 0;
	width: 100%;
	height: 100vh;
}

</style>

<style lang="scss">
// Make sure the editor and its modals are above everything
.SfxModal-Wrapper {
	z-index: 10101 !important;
}

#SfxPopper {
	z-index: 10102;
	position: relative;
}

// Default styling
.viewer__image-editor,
.SfxModal-Wrapper,
.SfxPopper-wrapper {
	* {
		// Fix font size for the entire image editor
		font-size: var(--default-font-size) !important;
	}

	label,
	button {
		color: var(--color-main-text);
		> span {
			font-size: var(--default-font-size) !important;
		}
	}

	// Fix button ratio and center content
	button {
		display: flex;
		align-items: center;
		justify-content: center;
		min-width: 44px;
		min-height: 44px;
		padding: 6px 12px;
	}
}

// Input styling
.SfxInput-root {
	height: auto !important;
	padding: 0 !important;
	.SfxInput-Base {
		margin: 0 !important;
	}
}

// Select styling
.SfxSelect-root {
	padding: 8px !important;
}

// Global buttons
.SfxButton-root {
	min-height: 44px !important;
	margin: 0 !important;
	border: transparent !important;
	&[color='error'] {
		color: white  !important;
		background-color: var(--color-error) !important;
		&:hover,
		&:focus {
			border-color: white !important;
			background-color: var(--color-error-hover) !important;
		}
	}
	&[color='primary'] {
		color: var(--color-primary-element-text)  !important;
		background-color: var(--color-primary-element) !important;
		&:hover,
		&:focus {
			background-color: var(--color-primary-element-hover) !important;
		}
	}
}

// Menu items
.SfxMenuItem-root {
	height: 44px;
	padding-left: 8px !important;
	// Center the menu entry icon and fix width
	> div {
		margin-right: 0;
		padding: 14px;
		// Minus the parent padding-left
		padding: calc(14px - 8px);
		cursor: pointer;
	}

	// Disable jpeg saving (jpg is already here)
	&[value='jpeg'] {
		display: none;
	}
}

// Modal
.SfxModal-Container {
	min-height: 300px;
	padding: 22px;

	// Fill height
	.SfxModal-root,
	.SfxModalTitle-root {
		flex: 1 1 100%;
		justify-content: center;
		color: var(--color-main-text);
	}
	.SfxModalTitle-Icon {
		margin-bottom: 22px !important;
		background: none !important;
		// Fit EmptyContent styling
		svg {
			width: 64px;
			height: 64px;
			opacity: .4;
			// Override all coloured icons

			--color-primary: var(--color-main-text);
			--color-error: var(--color-main-text);
		}
	}
	// Hide close icon (use cancel button)
	.SfxModalTitle-Close {
		display: none !important;
	}
	// Modal actions buttons display
	.SfxModalActions-root {
		justify-content: space-evenly !important;
	}
}

// Header buttons
.FIE_topbar-center-options > button,
.FIE_topbar-center-options > label {
	margin-left: 6px !important;
}

// Tabs
.FIE_tabs {
	padding: 6px !important;
	overflow: hidden;
	overflow-y: auto;
}

.FIE_tab {
	width: 80px !important;
	height: 80px !important;
	padding: 8px;
	border-radius: var(--border-radius-large) !important;
	svg {
		width: 16px;
		height: 16px;
	}
	&-label {
		margin-top: 8px !important;
		overflow: hidden;
		text-overflow: ellipsis;
		max-width: 100%;
		white-space: nowrap;
		display: block !important;
	}

	&:hover,
	&:focus {
		background-color: var(--color-background-hover) !important;
	}

	&[aria-selected=true] {
		color: var(--color-main-text);
		background-color: var(--color-background-dark);
		box-shadow: 0 0 0 2px var(--color-primary-element);
	}
}

// Tools bar
.FIE_tools-bar {
	&-wrapper {
		max-height: max-content !important;
	}

	// Matching buttons tools
	& > div[class$='-tool-button'],
	& > div[class$='-tool'] {
		display: flex;
		align-items: center;
		justify-content: center;
		min-width: 44px;
		height: 44px;
		padding: 6px 16px;
		border-radius: var(--border-radius-pill);
	}
}

// Crop preset select button
.FIE_crop-presets-opener-button {
	// override default button width
	min-width: 0 !important;
	padding: 5px !important;
	padding-left: 10px !important;
	border: none !important;
	background-color: transparent !important;
}

// Force icon-only style
.FIE_topbar-history-buttons button,
.FIE_topbar-close-button,
.FIE_resize-ratio-locker {
	border: none !important;
	background-color: transparent !important;

	&:hover,
	&:focus {
		background-color: var(--color-background-hover) !important;
	}

	svg {
		width: 16px;
		height: 16px;
	}
}

// Left top bar buttons
.FIE_topbar-history-buttons button {
	&.FIE_topbar-reset-button {
		&::before {
			content: attr(title);
			font-weight: normal;
		}
		svg {
			display: none;
		}
	}
}

// Save button fixes
.FIE_topbar-save-wrapper {
	width: auto !important;
}

.FIE_topbar-save-button {
	color: var(--color-primary-text) !important;
	border: none !important;
	background-color: var(--color-primary-element) !important;
	&:hover,
	&:focus {
		background-color: var(--color-primary-element-hover) !important;
	}
}

// Save Modal fixes
.FIE_resize-tool-options {
	.FIE_resize-width-option,
	.FIE_resize-height-option {
		flex: 1 1;
		min-width: 0;
	}
}

// Resize lock
.FIE_resize-ratio-locker {
	margin-right: 8px !important;
	// Icon is very thin
	svg {
		width: 20px;
		height: 20px;
		path {
			stroke-width: 1;
			stroke: var(--color-main-text);
			fill: var(--color-main-text);
		}
	}
}

// Close editor button fixes
.FIE_topbar-close-button {
	svg path {
		// The path viewbox is weird and
		// not correct, this fixes it
		transform: scale(1.6);
	}
}

// Canvas container
.FIE_canvas-container {
	background-color: var(--color-main-background) !important;
}

// Loader
.FIE_spinner::after,
.FIE_spinner-label {
	display: none !important;
}

.FIE_spinner-wrapper {
	background-color: transparent !important;
}

.FIE_spinner::before {
	position: absolute;
	z-index: 2;
	top: 50%;
	left: 50%;
	width: 28px;
	height: 28px;
	margin: -16px 0 0 -16px;
	content: '';
	-webkit-transform-origin: center;
	-ms-transform-origin: center;
	transform-origin: center;
	-webkit-animation: rotate .8s infinite linear;
	animation: rotate .8s infinite linear;
	border: 2px solid var(--color-loading-light);
	border-top-color: var(--color-loading-dark);
	border-radius: 100%;

	filter: var(--background-invert-if-dark);
}

</style>
