<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcSettingsSection :name="t('systemtags', 'Collaborative tags')"
		:description="t('systemtags', 'Collaborative tags are available for all users. Restricted tags are visible to users but cannot be assigned by them. Invisible tags are for internal use, since users cannot see or assign them.')">
		<SystemTagsCreationControl />
		<NcLoadingIcon v-if="loadingTags"
			:name="t('systemtags', 'Loading collaborative tags …')"
			:size="32" />
		<SystemTagForm v-else
			:tags="tags"
			@tag:created="handleCreate"
			@tag:updated="handleUpdate"
			@tag:deleted="handleDelete" />
	</NcSettingsSection>
</template>

<script lang="ts">
/* eslint-disable */
import Vue from 'vue'

import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'

import { translate as t } from '@nextcloud/l10n'
import { showError } from '@nextcloud/dialogs'

import SystemTagForm from '../components/SystemTagForm.vue'
import SystemTagsCreationControl from '../components/SystemTagsCreationControl.vue'

import { fetchTags } from '../services/api.js'

import type { TagWithId } from '../types.js'

export default Vue.extend({
	name: 'SystemTagsSection',

	components: {
		NcLoadingIcon,
		NcSettingsSection,
		SystemTagForm,
		SystemTagsCreationControl,
	},

	data() {
		return {
			loadingTags: false,
			tags: [] as TagWithId[],
		}
	},

	async created() {
		this.loadingTags = true
		try {
			this.tags = await fetchTags()
		} catch (error) {
			showError(t('systemtags', 'Failed to load tags'))
		}
		this.loadingTags = false
	},

	methods: {
		t,

		handleCreate(tag: TagWithId) {
			this.tags.unshift(tag)
		},

		handleUpdate(tag: TagWithId) {
			const tagIndex = this.tags.findIndex(currTag => currTag.id === tag.id)
			this.tags.splice(tagIndex, 1)
			this.tags.unshift(tag)
		},

		handleDelete(tag: TagWithId) {
			const tagIndex = this.tags.findIndex(currTag => currTag.id === tag.id)
			this.tags.splice(tagIndex, 1)
		},
	},
})
</script>
