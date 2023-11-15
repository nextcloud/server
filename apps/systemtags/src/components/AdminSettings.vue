<!--
  - @copyright 2023 Ferdinand Thiessen <opensource@fthiessen.de>
  -
  - @author Ferdinand Thiessen <opensource@fthiessen.de>
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
	<NcSettingsSection data-cy-systemtags-section
		:name="t('systemtags', 'Collaborative tags')"
		:description="t('systemtags', 'Collaborative tags are available for all users. Restricted tags are visible to users but cannot be assigned by them. Invisible tags are for internal use, since users cannot see or assign them.')"
		:limit-width="true">
		<p>{{ t('systemtags', 'Either select an existing tag to edit or create a new one.') }}</p>
		<NcSelectTags v-model="selectedTag"
			data-cy-systemtags-select
			:fetch-tags="false"
			:options="allTags"
			:multiple="false"
			:passthru="true"
			:placeholder="t('systemtags', 'Select tag to edit')" />
		<h3>
			{{ selectedTag ? t('systemtags', 'Edit tag "{tag}"', { tag: selectedTag.displayName }) : t('systemtags', 'Create a new tag') }}
		</h3>
		<div class="tag-editor">
			<NcTextField class="tag-editor__name"
				data-cy-systemtags-name
				:value.sync="editableTag.name"
				:label="t('systemtags', 'Tag name')"
				:error="error !== ''"
				:helper-text="error"
				@input="error=''" />
			<label class="tag-editor__visibility-label">
				<span>{{ t('systemtags', 'Tag visibility') }}</span>
				<NcSelect :value="currentVisibility"
					data-cy-systemtags-visibility
					class="tag-editor__visibility"
					label="name"
					:clearable="false"
					:multiple="false"
					:options="TAG_VISIBILITY"
					@input="onUpdateVisibility" />
			</label>
			<div class="tag-editor__button-group">
				<NcButton v-show="selectedTag" type="error" @click="onDeleteTag">
					{{ t('systemtags', 'Delete') }}
				</NcButton>
				<NcButton :disabled="hasChanges" type="tertiary" @click="onResetTag">
					{{ t('systemtags', 'Reset') }}
				</NcButton>
				<NcButton :disabled="hasChanges" type="primary" @click="onUpdateTag">
					{{ selectedTag ? t('systemtags', 'Update') : t('systemtags', 'Create') }}
				</NcButton>
			</div>
		</div>
	</NcSettingsSection>
</template>

<script lang="ts">
import type { ServerTag, Tag, TagWithId } from '../types'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'
import { logger } from '../logger'
import { convertTag, formatTag } from '../utils.js'
import { deleteTag, createTag, renameTag, fetchTags } from '../services/api.js'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcSelectTags from '@nextcloud/vue/dist/Components/NcSelectTags.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'

const TAG_VISIBILITY = [
	{ userVisible: true, userAssignable: true, name: t('systemtags', 'Public') },
	{ userVisible: true, name: t('systemtags', 'Restricted') },
	{ name: t('systemtags', 'Invisible') },
] as readonly Omit<Partial<ServerTag>, 'canAssign'>[]

const EMPTY_TAG: ServerTag = { name: '', canAssign: true, userAssignable: true, userVisible: true }

export default defineComponent({
	name: 'AdminSettings',
	components: {
		NcButton,
		NcSelect,
		NcSelectTags,
		NcSettingsSection,
		NcTextField,
	},
	data() {
		return {
			allTags: [] as TagWithId[],
			error: '',
			selectedTag: null as Tag,
			editableTag: { ...EMPTY_TAG } as ServerTag,
			TAG_VISIBILITY,
		}
	},
	computed: {
		/**
		 * The currently selected visibility for the tag
		 */
		currentVisibility() {
			if (this.editableTag.userAssignable) {
				return TAG_VISIBILITY[0]
			} else if (this.editableTag.userVisible) {
				return TAG_VISIBILITY[1]
			}
			return TAG_VISIBILITY[2]
		},
		/**
		 * Check if there are changes on the editable tag compared to the selected / empty tag
		 */
		hasChanges() {
			if (this.editableTag.id) {
				return JSON.stringify(this.editableTag) === JSON.stringify(formatTag(this.selectedTag))
			}
			return JSON.stringify(this.editableTag) === JSON.stringify(EMPTY_TAG)
		},
	},
	watch: {
		/**
		 * Set editable tag to selected tag or create a new empty one
		 */
		selectedTag() {
			this.onResetTag()
		},
	},
	async mounted() {
		this.allTags = await fetchTags()
	},
	methods: {
		t,

		/**
		 * Update the tag visibility to the selected one
		 * @param visibility New selected visibility
		 */
		onUpdateVisibility(visibility: typeof TAG_VISIBILITY[number]) {
			this.editableTag = { ...visibility, name: this.editableTag.name, id: this.editableTag.id }
		},

		/**
		 * Handle deleting existing tag (the selected one)
		 */
		async onDeleteTag() {
			try {
				console.warn(this.selectedTag)
				await deleteTag(this.selectedTag)
				showSuccess(t('systemtags', 'Tag "{name}" deleted', { name: this.selectedTag.displayName }))
				this.allTags = this.allTags.filter(({ id }) => id !== this.selectedTag.id)
				this.selectedTag = null
			} catch (error) {
				logger.error(error as Error)
				showError(t('systemtags', 'Could not delete tag'))
			}
		},

		/**
		 * Update or create the selected tag / new tag
		 */
		async onUpdateTag() {
			if (this.editableTag.id) {
				try {
					await renameTag(convertTag(this.editableTag) as unknown as TagWithId)
					this.allTags.filter(({ id }) => id === this.editableTag.id)[0].displayName = this.editableTag.name
					this.onResetTag()
					showSuccess(t('systemtags', 'Tag renamed to "{name}"', { name: this.editableTag.name }))
				} catch (error) {
					showError(t('systemtags', 'Could not rename tag'))
				}
			} else {
				try {
					const tag = await createTag({ ...this.editableTag })
					showSuccess(t('systemtags', 'Tag "{name}" created', { name: this.editableTag.name }))
					this.allTags.push(convertTag(tag))
					this.onResetTag()
				} catch (error) {
					if (error.response?.status === 409) {
						this.error = t('systemtags', 'Tag "{name}" already exists', { name: this.editableTag.name })
					} else {
						showError(t('systemtags', 'Could not create tag "{name}"', { name: this.editableTag.name }))
					}
				}
			}
		},

		/**
		 * Reset edit mode to the selected tag without changes
		 */
		onResetTag() {
			if (this.selectedTag) {
				this.editableTag = formatTag(this.selectedTag)
			} else {
				this.editableTag = { ...EMPTY_TAG }
			}
		},
	},
})
</script>

<style scoped lang="scss">
.tag-editor {
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;
	gap: 12px;
	justify-content: start;
	align-items: end;

	&__name {
		flex: 1 1 fit-content;
	}

	&__visibility {
		width: 100%;
		min-width: 160px;
	}

	&__visibility-label {
		display: flex;
		flex: 1 1;
		flex-direction: column;
	}

	&__button-group {
		height: 44px;
		display: flex;
		flex-wrap: wrap;
		gap: 8px;
	}
}
</style>
