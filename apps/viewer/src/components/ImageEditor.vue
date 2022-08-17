<template>
	<div ref="editor" class="viewer__image-editor" />
</template>
<script>
import FilerobotImageEditor from 'filerobot-image-editor'
import { basename, dirname, extname, join } from 'path'
import client from '../services/DavClient.js'
import logger from '../services/logger.js'

import translations from '../models/editorTranslations.js'

const { TABS, TOOLS } = FilerobotImageEditor

export default {
	props: {
		filename: {
			type: String,
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
						// Accent
						'accent-primary': 'var(--color-primary)',
						// Use by the slider
						'border-active-bottom': 'var(--color-primary)',
						'icons-primary': 'var(--color-main-text)',
						// Active state
						'bg-primary-active': 'var(--color-background-dark)',
						'bg-primary-hover': 'var(--color-background-darker)',
						'accent-primary-active': 'var(--color-main-text)',
						// Used by the save button
						'accent-primary-hover': 'var(--color-primary)',

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
	},

	mounted() {
		this.imageEditor = new FilerobotImageEditor(
			this.$refs.editor,
			this.config
		)
		this.imageEditor.render()
		window.addEventListener('keydown', this.handleKeydown, true)
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

		async onSave(imageData) {
			const filename = join(dirname(this.filename), imageData.fullName)
			logger.debug('Saving image...', { src: this.src, filename })
			try {
				const b64string = imageData.imageBase64.split(';base64,').pop()
				const buff = Buffer.from(b64string, 'base64')
				await client.putFileContents(filename, buff, {
					// @see https://github.com/perry-mitchell/webdav-client#putfilecontents
					// https://github.com/perry-mitchell/webdav-client/issues/150
					contentLength: false,
				})
				logger.info('Edited image saved!')
			} catch (error) {
				logger.error('Error saving image', { error })
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
				}
			)
		},

		// Key Handlers, override default Viewer arrow and escape key
		handleKeydown(event) {
			event.stopImmediatePropagation()
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
	},
}
</script>

<style lang="scss" scoped>
// Take full screen size ()
.viewer__image-editor {
	width: 100%;
	height: 100vh;
	top: calc(var(--header-height) * -1);
	bottom: calc(var(--header-height) * -1);
	left: 0;
	position: absolute;
	z-index: 10100;
}
</style>

<style lang="scss">
// Make sure the editor and its modals are above everything
.SfxModal-Wrapper {
	z-index: 10101 !important;
}

.SfxPopper-wrapper {
	z-index: 10102 !important;
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
		padding: 6px 12px;
		min-width: 44px;
		min-height: 44px;
		display: flex;
		justify-content: center;
		align-items: center;
	}
}

// Input styling
.SfxInput-root {
	height: auto !important;
	padding: 0 !important;
}

.SfxInput-root .SfxInput-Base {
	margin: 0 !important;
}

// Select styling
.SfxSelect-root {
	padding: 8px !important;
}

// Global buttons
.SfxButton-root {
	margin: 0 !important;
	min-height: 44px !important;
	border: transparent !important;
	&[color='error'] {
		background-color: var(--color-error) !important;
		color: white  !important;
		&:hover,
		&:focus {
			border-color: white !important;
			background-color: var(--color-error-hover) !important;
		}
	}
	&[color='primary'] {
		background-color: var(--color-primary-element) !important;
		color: var(--color-primary-text)  !important;
		&:hover,
		&:focus {
			background-color: var(--color-primary-element-hover) !important;
		}
	}
}

// Menu items
.SfxMenuItem-root {
	height: 44px;
	padding-left: 0 !important;
	// Center the menu entry icon and fix width
	> div {
		cursor: pointer;
		margin-right: 0;
		padding: 14px;
	}
}

// Modal
.SfxModal-Container {
	padding: 22px;
	min-height: 300px;

	// Fill height
	.SfxModal-root,
	.SfxModalTitle-root {
		flex: 1 1 100%;
		justify-content: center;
		color: var(--color-main-text);
	}
	.SfxModalTitle-Icon {
		background: none !important;
		margin-bottom: 22px !important;
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
}

.FIE_tab {
	padding: 8px;
	width: 80px !important;
	height: 80px !important;
	border-radius: var(--border-radius-large) !important;
	svg {
		width: 16px;
		height: 16px;
	}
	&-label {
		margin-top: 8px;
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
		height: 44px;
		min-width: 44px;
		display: flex;
		justify-content: center;
		align-items: center;
		border-radius: var(--border-radius-pill);
		padding: 6px 16px;
	}
}

// Crop preset select button
.FIE_crop-presets-opener-button {
	background-color: transparent !important;
	border: none !important;
	padding: 5px !important;
	padding-left: 10px !important;
	// override default button width
	min-width: 0px !important;
}

// Force icon-only style
.FIE_topbar-history-buttons button,
.FIE_topbar-close-button,
.FIE_resize-ratio-locker {
	border: none !important;
	background-color: transparent !important;

	&:hover,
	&:focus {
		background-color: var(--color-background-dark) !important;
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
.FIE_topbar-save-button {
	background-color: var(--color-primary-element) !important;
	color: var(--color-primary-text) !important;
	border: none !important;
	&:hover,
	&:focus {
		background-color: var(--color-primary-element-hover) !important;
	}
}

// Resize lock
.FIE_resize-ratio-locker {
	margin-right: 8px !important;
}

// Close editor button fixes
.FIE_topbar-close-button {
	svg path {
		// The path viewbox is weird and
		// not correct, this fixes it
		transform: scale(1.6);
	}
}

// Disable jpeg saving (jpg is already here)
.SfxMenuItem-root[value='jpeg'] {
	display: none;
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
	z-index: 2;
	content: '';
	height: 28px;
	width: 28px;
	margin: -16px 0 0 -16px;
	position: absolute;
	top: 50%;
	left: 50%;
	border-radius: 100%;
	-webkit-animation: rotate .8s infinite linear;
	animation: rotate .8s infinite linear;
	-webkit-transform-origin: center;
	-ms-transform-origin: center;
	transform-origin: center;
	border: 2px solid var(--color-loading-light);
	border-top-color: var(--color-loading-dark);
	filter: var(--background-invert-if-dark);
}
</style>
