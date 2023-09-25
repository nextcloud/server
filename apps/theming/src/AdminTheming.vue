<!--
  - @copyright 2022 Christopher Ng <chrng8@gmail.com>
  -
  - @author Christopher Ng <chrng8@gmail.com>
  -
  - @license AGPL-3.0-or-later
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
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
					@update:theming="$emit('update:theming')" />

				<!-- Primary color picker -->
				<ColorPickerField :name="colorPickerField.name"
					:default-value="colorPickerField.defaultValue"
					:display-name="colorPickerField.displayName"
					:value.sync="colorPickerField.value"
					data-admin-theming-setting-primary-color
					@update:theming="$emit('update:theming')" />

				<!-- Default background picker -->
				<FileInputField v-for="field in fileInputFields"
					:key="field.name"
					:aria-label="field.ariaLabel"
					:data-admin-theming-setting-file="field.name"
					:default-mime-value="field.defaultMimeValue"
					:display-name="field.displayName"
					:mime-name="field.mimeName"
					:mime-value.sync="field.mimeValue"
					:name="field.name"
					@update:theming="$emit('update:theming')" />
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
					@update:theming="$emit('update:theming')" />
				<FileInputField v-for="field in advancedFileInputFields"
					:key="field.name"
					:name="field.name"
					:mime-name="field.mimeName"
					:mime-value.sync="field.mimeValue"
					:default-mime-value="field.defaultMimeValue"
					:display-name="field.displayName"
					:aria-label="field.ariaLabel"
					@update:theming="$emit('update:theming')" />
				<CheckboxField :name="userThemingField.name"
					:value="userThemingField.value"
					:default-value="userThemingField.defaultValue"
					:display-name="userThemingField.displayName"
					:label="userThemingField.label"
					:description="userThemingField.description"
					data-admin-theming-setting-disable-user-theming
					@update:theming="$emit('update:theming')" />
				<a v-if="!canThemeIcons"
					:href="docUrlIcons"
					rel="noreferrer noopener">
					<em>{{ t('theming', 'Install the ImageMagick PHP extension with support for SVG images to automatically generate favicons based on the uploaded logo and color.') }}</em>
				</a>
			</div>
		</NcSettingsSection>
		<AppMenuSection :default-apps.sync="defaultApps" :user-default-app-enabled.sync="userDefaultAppEnabled" />
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import CheckboxField from './components/admin/CheckboxField.vue'
import ColorPickerField from './components/admin/ColorPickerField.vue'
import FileInputField from './components/admin/FileInputField.vue'
import TextField from './components/admin/TextField.vue'
import AppMenuSection from './components/admin/AppMenuSection.vue'

const {
	backgroundMime,
	canThemeIcons,
	color,
	docUrl,
	docUrlIcons,
	faviconMime,
	isThemable,
	legalNoticeUrl,
	logoheaderMime,
	logoMime,
	name,
	notThemableErrorMessage,
	privacyPolicyUrl,
	slogan,
	url,
	userThemingDisabled,
	defaultApps,
	userDefaultAppEnabled,
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

const colorPickerField = {
	name: 'color',
	value: color,
	defaultValue: '#0082c9',
	displayName: t('theming', 'Color'),
}

const fileInputFields = [
	{
		name: 'logo',
		mimeName: 'logoMime',
		mimeValue: logoMime,
		defaultMimeValue: '',
		displayName: t('theming', 'Logo'),
		ariaLabel: t('theming', 'Upload new logo'),
	},
	{
		name: 'background',
		mimeName: 'backgroundMime',
		mimeValue: backgroundMime,
		defaultMimeValue: '',
		displayName: t('theming', 'Background and login image'),
		ariaLabel: t('theming', 'Upload new background and login image'),
	},
]

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

	emits: [
		'update:theming',
	],

	textFields,

	data() {
		return {
			textFields,
			colorPickerField,
			fileInputFields,
			advancedTextFields,
			advancedFileInputFields,
			userThemingField,
			userDefaultAppEnabled,
			defaultApps,

			canThemeIcons,
			docUrl,
			docUrlIcons,
			isThemable,
			notThemableErrorMessage,
		}
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
		/* This is basically https://github.com/nextcloud/server/blob/master/core/css/guest.css
		   But without the user variables. That way the admin can preview the render as guest*/
		/* As guest, there is no user color color-background-plain */
		background-color: var(--color-primary-element-default, #0082c9);
		/* As guest, there is no user background (--image-background)
		1. Empty background if defined
		2. Else default background
		3. Finally default gradient (should not happened, the background is always defined anyway) */
		background-image: var(--image-background-plain, var(--image-background-default, linear-gradient(40deg, #0082c9 0%, #30b6ff 100%)));

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
