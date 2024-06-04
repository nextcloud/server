<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section>
		<NcSettingsSection :name="t('theming', 'Theming')"
			:description="t('theming', 'Theming makes it possible to easily customize the look and feel of your instance and supported clients. This will be visible for all users.')"
			:doc-url="docUrl"
			data-admin-theming-settings>
			<div class="admin-theming">
				<NcNoteCard v-if="!isThemable"
					type="error"
					:show-alert="true">
					<p>{{ notThemableErrorMessage }}</p>
				</NcNoteCard>

				<!-- Name, web link, slogan... fields -->
				<TextField v-for="field in textFields"
					:key="field.name"
					:data-admin-theming-setting-field="field.name"
					:default-value="field.defaultValue"
					:display-name="field.displayName"
					:maxlength="field.maxlength"
					:name="field.name"
					:placeholder="field.placeholder"
					:type="field.type"
					:value.sync="field.value"
					@update:theming="refreshStyles" />

				<!-- Primary color picker -->
				<ColorPickerField :name="primaryColorPickerField.name"
					:description="primaryColorPickerField.description"
					:default-value="primaryColorPickerField.defaultValue"
					:display-name="primaryColorPickerField.displayName"
					:value.sync="primaryColorPickerField.value"
					data-admin-theming-setting-primary-color
					@update:theming="refreshStyles" />

				<!-- Background color picker -->
				<ColorPickerField name="background_color"
					:description="t('theming', 'Instead of a background image you can also configure a plain background color. If you use a background image changing this color will influence the color of the app menu icons.')"
					:default-value.sync="defaultBackgroundColor"
					:display-name="t('theming', 'Background color')"
					:value.sync="backgroundColor"
					data-admin-theming-setting-background-color
					@update:theming="refreshStyles" />

				<!-- Default background picker -->
				<FileInputField :aria-label="t('theming', 'Upload new logo')"
					data-admin-theming-setting-file="logo"
					:display-name="t('theming', 'Logo')"
					mime-name="logoMime"
					:mime-value.sync="logoMime"
					name="logo"
					@update:theming="refreshStyles" />

				<FileInputField :aria-label="t('theming', 'Upload new background and login image')"
					data-admin-theming-setting-file="background"
					:display-name="t('theming', 'Background and login image')"
					mime-name="backgroundMime"
					:mime-value.sync="backgroundMime"
					name="background"
					@uploaded="backgroundURL = $event"
					@update:theming="refreshStyles" />

				<div class="admin-theming__preview" data-admin-theming-preview>
					<div class="admin-theming__preview-logo" data-admin-theming-preview-logo />
				</div>
			</div>
		</NcSettingsSection>

		<NcSettingsSection :name="t('theming', 'Advanced options')">
			<div class="admin-theming-advanced">
				<TextField v-for="field in advancedTextFields"
					:key="field.name"
					:name="field.name"
					:value.sync="field.value"
					:default-value="field.defaultValue"
					:type="field.type"
					:display-name="field.displayName"
					:placeholder="field.placeholder"
					:maxlength="field.maxlength"
					@update:theming="refreshStyles" />
				<FileInputField v-for="field in advancedFileInputFields"
					:key="field.name"
					:name="field.name"
					:mime-name="field.mimeName"
					:mime-value.sync="field.mimeValue"
					:default-mime-value="field.defaultMimeValue"
					:display-name="field.displayName"
					:aria-label="field.ariaLabel"
					@update:theming="refreshStyles" />
				<CheckboxField :name="userThemingField.name"
					:value="userThemingField.value"
					:default-value="userThemingField.defaultValue"
					:display-name="userThemingField.displayName"
					:label="userThemingField.label"
					:description="userThemingField.description"
					data-admin-theming-setting-disable-user-theming
					@update:theming="refreshStyles" />
				<a v-if="!canThemeIcons"
					:href="docUrlIcons"
					rel="noreferrer noopener">
					<em>{{ t('theming', 'Install the ImageMagick PHP extension with support for SVG images to automatically generate favicons based on the uploaded logo and color.') }}</em>
				</a>
			</div>
		</NcSettingsSection>
		<AppMenuSection :default-apps.sync="defaultApps" />
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { refreshStyles } from './helpers/refreshStyles.js'

import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import CheckboxField from './components/admin/CheckboxField.vue'
import ColorPickerField from './components/admin/ColorPickerField.vue'
import FileInputField from './components/admin/FileInputField.vue'
import TextField from './components/admin/TextField.vue'
import AppMenuSection from './components/admin/AppMenuSection.vue'

const {
	defaultBackgroundURL,

	backgroundMime,
	backgroundURL,
	backgroundColor,
	canThemeIcons,
	docUrl,
	docUrlIcons,
	faviconMime,
	isThemable,
	legalNoticeUrl,
	logoheaderMime,
	logoMime,
	name,
	notThemableErrorMessage,
	primaryColor,
	privacyPolicyUrl,
	slogan,
	url,
	userThemingDisabled,
	defaultApps,
} = loadState('theming', 'adminThemingParameters')

const textFields = [
	{
		name: 'name',
		value: name,
		defaultValue: 'Nextcloud',
		type: 'text',
		displayName: t('theming', 'Name'),
		placeholder: t('theming', 'Name'),
		maxlength: 250,
	},
	{
		name: 'url',
		value: url,
		defaultValue: 'https://nextcloud.com',
		type: 'url',
		displayName: t('theming', 'Web link'),
		placeholder: 'https://…',
		maxlength: 500,
	},
	{
		name: 'slogan',
		value: slogan,
		defaultValue: t('theming', 'a safe home for all your data'),
		type: 'text',
		displayName: t('theming', 'Slogan'),
		placeholder: t('theming', 'Slogan'),
		maxlength: 500,
	},
]

const primaryColorPickerField = {
	name: 'primary_color',
	value: primaryColor,
	defaultValue: '#0082c9',
	displayName: t('theming', 'Primary color'),
	description: t('theming', 'The primary color is used for highlighting elements like important buttons. It might get slightly adjusted depending on the current color schema.'),
}

const advancedTextFields = [
	{
		name: 'imprintUrl',
		value: legalNoticeUrl,
		defaultValue: '',
		type: 'url',
		displayName: t('theming', 'Legal notice link'),
		placeholder: 'https://…',
		maxlength: 500,
	},
	{
		name: 'privacyUrl',
		value: privacyPolicyUrl,
		defaultValue: '',
		type: 'url',
		displayName: t('theming', 'Privacy policy link'),
		placeholder: 'https://…',
		maxlength: 500,
	},
]

const advancedFileInputFields = [
	{
		name: 'logoheader',
		mimeName: 'logoheaderMime',
		mimeValue: logoheaderMime,
		defaultMimeValue: '',
		displayName: t('theming', 'Header logo'),
		ariaLabel: t('theming', 'Upload new header logo'),
	},
	{
		name: 'favicon',
		mimeName: 'faviconMime',
		mimeValue: faviconMime,
		defaultMimeValue: '',
		displayName: t('theming', 'Favicon'),
		ariaLabel: t('theming', 'Upload new favicon'),
	},
]

const userThemingField = {
	name: 'disable-user-theming',
	value: userThemingDisabled,
	defaultValue: false,
	displayName: t('theming', 'User settings'),
	label: t('theming', 'Disable user theming'),
	description: t('theming', 'Although you can select and customize your instance, users can change their background and colors. If you want to enforce your customization, you can toggle this on.'),
}

export default {
	name: 'AdminTheming',

	components: {
		AppMenuSection,
		CheckboxField,
		ColorPickerField,
		FileInputField,
		NcNoteCard,
		NcSettingsSection,
		TextField,
	},

	data() {
		return {
			backgroundMime,
			backgroundURL,
			backgroundColor,
			defaultBackgroundColor: '#0069c3',

			logoMime,

			textFields,
			primaryColorPickerField,
			advancedTextFields,
			advancedFileInputFields,
			userThemingField,
			defaultApps,

			canThemeIcons,
			docUrl,
			docUrlIcons,
			isThemable,
			notThemableErrorMessage,
		}
	},

	computed: {
		cssBackgroundImage() {
			if (this.backgroundURL) {
				return `url('${this.backgroundURL}')`
			}
			return 'unset'
		},
	},

	watch: {
		backgroundMime() {
			if (this.backgroundMime === '') {
				// Reset URL to default value for preview
				this.backgroundURL = defaultBackgroundURL
			} else if (this.backgroundMime === 'backgroundColor') {
				// Reset URL to empty image when only color is configured
				this.backgroundURL = ''
			}
		},
		async backgroundURL() {
			// When the background is changed we need to emulate the background color change
			if (this.backgroundURL !== '') {
				const color = await this.calculateDefaultBackground()
				this.defaultBackgroundColor = color
				this.backgroundColor = color
			}
		},
	},

	async mounted() {
		if (this.backgroundURL) {
			this.defaultBackgroundColor = await this.calculateDefaultBackground()
		}
	},

	methods: {
		refreshStyles,

		/**
		 * Same as on server - if a user uploads an image the mean color will be set as the background color
		 */
		calculateDefaultBackground() {
			const toHex = (num) => `00${num.toString(16)}`.slice(-2)

			return new Promise((resolve, reject) => {
				const img = new Image()
				img.src = this.backgroundURL
				img.onload = () => {
					const context = document.createElement('canvas').getContext('2d')
					context.imageSmoothingEnabled = true
					context.drawImage(img, 0, 0, 1, 1)
					resolve('#' + [...context.getImageData(0, 0, 1, 1).data.slice(0, 3)].map(toHex).join(''))
				}
				img.onerror = reject
			})
		},
	},
}
</script>

<style lang="scss" scoped>
.admin-theming,
.admin-theming-advanced {
	display: flex;
	flex-direction: column;
	gap: 8px 0;
}

.admin-theming {
	&__preview {
		width: 230px;
		height: 140px;
		background-size: cover;
		background-position: center;
		text-align: center;
		margin-top: 10px;
		background-color: v-bind('backgroundColor');
		background-image: v-bind('cssBackgroundImage');

		&-logo {
			width: 20%;
			height: 20%;
			margin-top: 20px;
			display: inline-block;
			background-size: contain;
			background-position: center;
			background-repeat: no-repeat;
			background-image: var(--image-logo, url('../../../core/img/logo/logo.svg'));
		}
	}
}
</style>
