<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcDialog data-cy-systemtags-picker
		:can-close="status !== Status.LOADING"
		:name="t('systemtags', 'Manage tags')"
		:open="opened"
		:class="'systemtags-picker--' + status"
		class="systemtags-picker"
		close-on-click-outside
		out-transition
		@update:open="onCancel">
		<NcEmptyContent v-if="status === Status.LOADING || status === Status.DONE"
			:name="t('systemtags', 'Applying tags changes…')">
			<template #icon>
				<NcLoadingIcon v-if="status === Status.LOADING" />
				<CheckIcon v-else fill-color="var(--color-success)" />
			</template>
		</NcEmptyContent>

		<template v-else>
			<!-- Search or create input -->
			<div class="systemtags-picker__input">
				<NcTextField :value.sync="input"
					:label="t('systemtags', 'Search or create tag')"
					data-cy-systemtags-picker-input>
					<TagIcon :size="20" />
				</NcTextField>
			</div>

			<!-- Tags list -->
			<ul class="systemtags-picker__tags"
				data-cy-systemtags-picker-tags>
				<li v-for="tag in filteredTags"
					:key="tag.id"
					:data-cy-systemtags-picker-tag="tag.id"
					:style="tagListStyle(tag)"
					class="systemtags-picker__tag">
					<NcCheckboxRadioSwitch :checked="isChecked(tag)"
						:disabled="!tag.canAssign"
						:indeterminate="isIndeterminate(tag)"
						:label="tag.displayName"
						class="systemtags-picker__tag-checkbox"
						@update:checked="onCheckUpdate(tag, $event)">
						{{ formatTagName(tag) }}
					</NcCheckboxRadioSwitch>

					<!-- Color picker -->
					<NcColorPicker :data-cy-systemtags-picker-tag-color="tag.id"
						:value="`#${tag.color}`"
						:shown="openedPicker === tag.id"
						class="systemtags-picker__tag-color"
						@update:value="onColorChange(tag, $event)"
						@update:shown="openedPicker = $event ? tag.id : false"
						@submit="openedPicker = false">
						<NcButton :aria-label="t('systemtags', 'Change tag color')" type="tertiary">
							<template #icon>
								<CircleIcon v-if="tag.color" :size="24" fill-color="var(--color-circle-icon)" />
								<CircleOutlineIcon v-else :size="24" fill-color="var(--color-circle-icon)" />
								<PencilIcon />
							</template>
						</NcButton>
					</NcColorPicker>
				</li>

				<!-- Create new tag -->
				<li>
					<NcButton v-if="canCreateTag"
						:disabled="status === Status.CREATING_TAG"
						alignment="start"
						class="systemtags-picker__tag-create"
						native-type="submit"
						type="tertiary"
						data-cy-systemtags-picker-button-create
						@click="onNewTag">
						{{ input.trim() }}<br>
						<span class="systemtags-picker__tag-create-subline">{{ t('systemtags', 'Create new tag') }}</span>
						<template #icon>
							<PlusIcon />
						</template>
					</NcButton>
				</li>
			</ul>

			<!-- Note -->
			<div class="systemtags-picker__note">
				<NcNoteCard v-if="!hasChanges" type="info">
					{{ t('systemtags', 'Select or create tags to apply to all selected files') }}
				</NcNoteCard>
				<NcNoteCard v-else type="info">
					<span v-html="statusMessage" />
				</NcNoteCard>
			</div>
		</template>

		<template #actions>
			<NcButton :disabled="status !== Status.BASE"
				type="tertiary"
				data-cy-systemtags-picker-button-cancel
				@click="onCancel">
				{{ t('systemtags', 'Cancel') }}
			</NcButton>
			<NcButton :disabled="!hasChanges || status !== Status.BASE"
				data-cy-systemtags-picker-button-submit
				@click="onSubmit">
				{{ t('systemtags', 'Apply changes') }}
			</NcButton>
		</template>

		<!-- Chip html for v-html tag rendering -->
		<div v-show="false">
			<NcChip ref="chip"
				text="%s"
				type="primary"
				no-close />
		</div>
	</NcDialog>
</template>

<script lang="ts">
import type { Node } from '@nextcloud/files'
import type { PropType } from 'vue'
import type { Tag, TagWithId } from '../types'

import { defineComponent } from 'vue'
import { emit } from '@nextcloud/event-bus'
import { getLanguage, n, t } from '@nextcloud/l10n'
import { sanitize } from 'dompurify'
import { showError, showInfo } from '@nextcloud/dialogs'
import debounce from 'debounce'
import escapeHTML from 'escape-html'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcChip from '@nextcloud/vue/dist/Components/NcChip.js'
import NcColorPicker from '@nextcloud/vue/dist/Components/NcColorPicker.js'
import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import CheckIcon from 'vue-material-design-icons/CheckCircle.vue'
import CircleIcon from 'vue-material-design-icons/Circle.vue'
import CircleOutlineIcon from 'vue-material-design-icons/CircleOutline.vue'
import PencilIcon from 'vue-material-design-icons/Pencil.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import TagIcon from 'vue-material-design-icons/Tag.vue'

import { createTag, fetchTag, fetchTags, getTagObjects, setTagObjects, updateTag } from '../services/api'
import { getNodeSystemTags, setNodeSystemTags } from '../utils'
import { elementColor, invertTextColor, isDarkModeEnabled } from '../utils/colorUtils'
import logger from '../logger.ts'

const debounceUpdateTag = debounce(updateTag, 500)
const mainBackgroundColor = getComputedStyle(document.body)
	.getPropertyValue('--color-main-background')
	.replace('#', '') || (isDarkModeEnabled() ? '000000' : 'ffffff')

type TagListCount = {
	string: number
}

enum Status {
	BASE = 'base',
	LOADING = 'loading',
	CREATING_TAG = 'creating-tag',
	DONE = 'done',
}

export default defineComponent({
	name: 'SystemTagPicker',

	components: {
		CheckIcon,
		CircleIcon,
		CircleOutlineIcon,
		NcButton,
		NcCheckboxRadioSwitch,
		// eslint-disable-next-line vue/no-unused-components
		NcChip,
		NcColorPicker,
		NcDialog,
		NcEmptyContent,
		NcLoadingIcon,
		NcNoteCard,
		NcTextField,
		PencilIcon,
		PlusIcon,
		TagIcon,
	},

	props: {
		nodes: {
			type: Array as PropType<Node[]>,
			required: true,
		},
	},

	setup() {
		return {
			emit,
			Status,
			t,
		}
	},

	data() {
		return {
			status: Status.BASE,
			opened: true,
			openedPicker: false as number | false,

			input: '',
			tags: [] as TagWithId[],
			tagList: {} as TagListCount,

			toAdd: [] as TagWithId[],
			toRemove: [] as TagWithId[],
		}
	},

	computed: {
		sortedTags(): TagWithId[] {
			return [...this.tags]
				.sort((a, b) => a.displayName.localeCompare(b.displayName, getLanguage(), { ignorePunctuation: true }))
		},

		filteredTags(): TagWithId[] {
			if (this.input.trim() === '') {
				return this.sortedTags
			}

			return this.sortedTags
				.filter(tag => tag.displayName.normalize().includes(this.input.normalize()))
		},

		hasChanges(): boolean {
			return this.toAdd.length > 0 || this.toRemove.length > 0
		},

		canCreateTag(): boolean {
			return this.input.trim() !== ''
				&& !this.tags.some(tag => tag.displayName.trim().toLocaleLowerCase() === this.input.trim().toLocaleLowerCase())
		},

		statusMessage(): string {
			if (this.toAdd.length === 0 && this.toRemove.length === 0) {
				// should not happen™
				return ''
			}

			if (this.toAdd.length === 1 && this.toRemove.length === 1) {
				return n(
					'systemtags',
					'{tag1} will be set and {tag2} will be removed from 1 file.',
					'{tag1} will be set and {tag2} will be removed from {count} files.',
					this.nodes.length,
					{
						tag1: this.formatTagChip(this.toAdd[0]),
						tag2: this.formatTagChip(this.toRemove[0]),
						count: this.nodes.length,
					},
					{ escape: false },
				)
			}

			const tagsAdd = this.toAdd.map(this.formatTagChip)
			const lastTagAdd = tagsAdd.pop() as string
			const tagsRemove = this.toRemove.map(this.formatTagChip)
			const lastTagRemove = tagsRemove.pop() as string

			const addStringSingular = n(
				'systemtags',
				'{tag} will be set to 1 file.',
				'{tag} will be set to {count} files.',
				this.nodes.length,
				{
					tag: lastTagAdd,
					count: this.nodes.length,
				},
				{ escape: false },
			)

			const removeStringSingular = n(
				'systemtags',
				'{tag} will be removed from 1 file.',
				'{tag} will be removed from {count} files.',
				this.nodes.length,
				{
					tag: lastTagRemove,
					count: this.nodes.length,
				},
				{ escape: false },
			)

			const addStringPlural = n(
				'systemtags',
				'{tags} and {lastTag} will be set to 1 file.',
				'{tags} and {lastTag} will be set to {count} files.',
				this.nodes.length,
				{
					tags: tagsAdd.join(', '),
					lastTag: lastTagAdd,
					count: this.nodes.length,
				},
				{ escape: false },
			)

			const removeStringPlural = n(
				'systemtags',
				'{tags} and {lastTag} will be removed from 1 file.',
				'{tags} and {lastTag} will be removed from {count} files.',
				this.nodes.length,
				{
					tags: tagsRemove.join(', '),
					lastTag: lastTagRemove,
					count: this.nodes.length,
				},
				{ escape: false },
			)

			// Singular
			if (this.toAdd.length === 1 && this.toRemove.length === 0) {
				return addStringSingular
			}
			if (this.toAdd.length === 0 && this.toRemove.length === 1) {
				return removeStringSingular
			}

			// Plural
			if (this.toAdd.length > 1 && this.toRemove.length === 0) {
				return addStringPlural
			}
			if (this.toAdd.length === 0 && this.toRemove.length > 1) {
				return removeStringPlural
			}

			// Mixed
			if (this.toAdd.length > 1 && this.toRemove.length === 1) {
				return `${addStringPlural} ${removeStringSingular}`
			}
			if (this.toAdd.length === 1 && this.toRemove.length > 1) {
				return `${addStringSingular} ${removeStringPlural}`
			}

			// Both plural
			return `${addStringPlural} ${removeStringPlural}`
		},
	},

	beforeMount() {
		fetchTags().then(tags => {
			this.tags = tags
		})

		// Efficient way of counting tags and their occurrences
		this.tagList = this.nodes.reduce((acc: TagListCount, node: Node) => {
			const tags = getNodeSystemTags(node) || []
			tags.forEach(tag => {
				acc[tag] = (acc[tag] || 0) + 1
			})
			return acc
		}, {} as TagListCount) as TagListCount
	},

	methods: {
		// Format & sanitize a tag chip for v-html tag rendering
		formatTagChip(tag: TagWithId): string {
			const chip = this.$refs.chip as NcChip
			const chipCloneEl = chip.$el.cloneNode(true) as HTMLElement
			if (tag.color) {
				const style = this.tagListStyle(tag)
				Object.entries(style).forEach(([key, value]) => {
					chipCloneEl.style.setProperty(key, value)
				})
			}
			const chipHtml = chipCloneEl.outerHTML
			return chipHtml.replace('%s', escapeHTML(sanitize(tag.displayName)))
		},

		formatTagName(tag: TagWithId): string {
			if (!tag.userVisible) {
				return t('systemtags', '{displayName} (hidden)', { displayName: tag.displayName })
			}

			if (!tag.userAssignable) {
				return t('systemtags', '{displayName} (restricted)', { displayName: tag.displayName })
			}

			return tag.displayName
		},

		onColorChange(tag: TagWithId, color: `#${string}`) {
			tag.color = color.replace('#', '')
			debounceUpdateTag(tag)
		},

		isChecked(tag: TagWithId): boolean {
			return tag.displayName in this.tagList
				&& this.tagList[tag.displayName] === this.nodes.length
		},

		isIndeterminate(tag: TagWithId): boolean {
			return tag.displayName in this.tagList
				&& this.tagList[tag.displayName] !== 0
				&& this.tagList[tag.displayName] !== this.nodes.length
		},

		onCheckUpdate(tag: TagWithId, checked: boolean) {
			if (checked) {
				this.toAdd.push(tag)
				this.toRemove = this.toRemove.filter(search => search.id !== tag.id)
				this.tagList[tag.displayName] = this.nodes.length
			} else {
				this.toRemove.push(tag)
				this.toAdd = this.toAdd.filter(search => search.id !== tag.id)
				this.tagList[tag.displayName] = 0
			}
		},

		async onNewTag() {
			this.status = Status.CREATING_TAG
			try {
				const payload: Tag = {
					displayName: this.input.trim(),
					userAssignable: true,
					userVisible: true,
					canAssign: true,
				}
				const id = await createTag(payload)
				const tag = await fetchTag(id)
				this.tags.push(tag)
				this.input = ''

				// Check the newly created tag
				this.onCheckUpdate(tag, true)

				// Scroll to the newly created tag
				await this.$nextTick()
				const newTagEl = this.$el.querySelector(`input[type="checkbox"][label="${tag.displayName}"]`)
				newTagEl?.scrollIntoView({
					behavior: 'instant',
					block: 'center',
					inline: 'center',
				})
			} catch (error) {
				showError((error as Error)?.message || t('systemtags', 'Failed to create tag'))
			} finally {
				this.status = Status.BASE
			}
		},

		async onSubmit() {
			this.status = Status.LOADING
			logger.debug('Applying tags', {
				toAdd: this.toAdd,
				toRemove: this.toRemove,
			})

			try {
				// Add tags
				for (const tag of this.toAdd) {
					const { etag, objects } = await getTagObjects(tag, 'files')

					// Create a new list of ids in one pass
					const ids = [...new Set([
						...objects.map(obj => obj.id).filter(Boolean),
						...this.nodes.map(node => node.fileid).filter(Boolean),
					])] as number[]

					// Set tags
					await setTagObjects(tag, 'files', ids.map(id => ({ id, type: 'files' })), etag)
				}

				// Remove tags
				for (const tag of this.toRemove) {
					const { etag, objects } = await getTagObjects(tag, 'files')

					// Get file IDs from the nodes array just once
					const nodeFileIds = new Set(this.nodes.map(node => node.fileid))

					// Create a filtered and deduplicated list of ids in one pass
					const ids = objects
						.map(obj => obj.id)
						.filter((id, index, self) => !nodeFileIds.has(id) && self.indexOf(id) === index)

					// Set tags
					await setTagObjects(tag, 'files', ids.map(id => ({ id, type: 'files' })), etag)
				}
			} catch (error) {
				logger.error('Failed to apply tags', { error })
				showError(t('systemtags', 'Failed to apply tags changes'))
				this.status = Status.BASE
				return
			}

			const nodes = [] as Node[]

			// Update nodes
			this.toAdd.forEach(tag => {
				this.nodes.forEach(node => {
					const tags = [...(getNodeSystemTags(node) || []), tag.displayName]
						.sort((a, b) => a.localeCompare(b, getLanguage(), { ignorePunctuation: true }))
					setNodeSystemTags(node, tags)
					nodes.push(node)
				})
			})

			this.toRemove.forEach(tag => {
				this.nodes.forEach(node => {
					const tags = [...(getNodeSystemTags(node) || [])].filter(t => t !== tag.displayName)
						.sort((a, b) => a.localeCompare(b, getLanguage(), { ignorePunctuation: true }))
					setNodeSystemTags(node, tags)
					nodes.push(node)
				})
			})

			// trigger update event
			nodes.forEach(node => emit('systemtags:node:updated', node))

			this.status = Status.DONE
			setTimeout(() => {
				this.opened = false
				this.$emit('close', true)
			}, 2000)
		},

		onCancel() {
			this.opened = false
			showInfo(t('systemtags', 'File tags modification canceled'))
			this.$emit('close', null)
		},

		tagListStyle(tag: TagWithId): Record<string, string> {
			// No color, no style
			if (!tag.color) {
				return {
					// See inline system tag color
					'--color-circle-icon': 'var(--color-text-maxcontrast)',
				}
			}

			// Make the checkbox color the same as the tag color
			// as well as the circle icon color picker
			const primaryElement = elementColor(`#${tag.color}`, `#${mainBackgroundColor}`)
			const textColor = invertTextColor(primaryElement) ? '#000000' : '#ffffff'
			return {
				'--color-circle-icon': 'var(--color-primary-element)',
				'--color-primary': primaryElement,
				'--color-primary-text': textColor,
				'--color-primary-element': primaryElement,
				'--color-primary-element-text': textColor,
			}
		},
	},
})
</script>

<style scoped lang="scss">
// Common sticky properties
.systemtags-picker__input,
.systemtags-picker__note {
	position: sticky;
	z-index: 9;
	background-color: var(--color-main-background);
}

.systemtags-picker__input {
	display: flex;
	top: 0;
	gap: 8px;
	padding-block-end: 8px;
	align-items: flex-end;
}

.systemtags-picker__tags {
	padding-block: 8px;
	gap: var(--default-grid-baseline);
	display: flex;
	flex-direction: column;

	li {
		display: flex;
		align-items: center;
		justify-content: space-between;
		width: 100%;

		// Make switch full width
		:deep(.checkbox-radio-switch) {
			width: 100%;

			.checkbox-content {
				// adjust width
				max-width: none;
				// recalculate padding
				box-sizing: border-box;
				min-height: calc(var(--default-grid-baseline) * 2 + var(--default-clickable-area));
			}
		}
	}

	.systemtags-picker__tag-color button {
		margin-inline-start: calc(var(--default-grid-baseline) * 2);

		span.pencil-icon {
			display: none;
			color: var(--color-main-text);
		}

		&:focus,
		&:hover,
		&[aria-expanded='true'] {
			.pencil-icon {
				display: block;
			}
			.circle-icon,
			.circle-outline-icon {
				display: none;
			}
		}
	}

	.systemtags-picker__tag-create {
		:deep(span) {
			text-align: start;
		}
		&-subline {
			font-weight: normal;
		}
	}
}

.systemtags-picker__note {
	bottom: 0;
	padding-block: 8px;

	:deep(.notecard) {
		// min 2 lines of text to avoid jumping
		min-height: 2lh;
		align-items: center;
	}

	& > div {
		margin: 0 !important;
	}
}

.systemtags-picker--done :deep(.empty-content__icon) {
	opacity:  1;
}

// Rendered chip in note
.nc-chip {
	display: inline !important;
}
</style>
