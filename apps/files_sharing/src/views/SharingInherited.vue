<!--
  - @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<ul id="sharing-inherited-shares">
		<!-- Main collapsible entry -->
		<SharingEntrySimple class="sharing-entry__inherited"
			:title="mainTitle"
			:aria-expanded="showInheritedShares">
			<template #avatar>
				<div class="avatar-shared icon-more-white" />
			</template>
			<NcActionButton :icon="showInheritedSharesIcon"
				:aria-label="toggleTooltip"
				:title="toggleTooltip"
				@click.prevent.stop="toggleInheritedShares" />
		</SharingEntrySimple>

		<!-- Inherited shares list -->
		<div v-if="loaded && shares.length === 0" class="sharing-entry__desc">
			<span class="sharing-entry__title">{{ t('files_sharing', 'No others with access') }}</span>
			<p>
				{{ t('files_sharing', 'People with access to parent folders will show up here') }}
			</p>
		</div>
		<template v-else>
			<SharingEntryInherited v-for="share in shares"
			:key="share.id"
			:file-info="fileInfo"
			:share="share"
			@remove:share="removeShare" />
		</template>
	</ul>
</template>

<script>
import { generateOcsUrl } from '@nextcloud/router'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import axios from '@nextcloud/axios'

import Share from '../models/Share.js'
import SharingEntryInherited from '../components/SharingEntryInherited.vue'
import SharingEntrySimple from '../components/SharingEntrySimple.vue'

export default {
	name: 'SharingInherited',

	components: {
		NcActionButton,
		SharingEntryInherited,
		SharingEntrySimple,
	},

	props: {
		fileInfo: {
			type: Object,
			default: () => {},
			required: true,
		},
	},

	data() {
		return {
			loaded: false,
			loading: false,
			showInheritedShares: false,
			shares: [],
		}
	},
	computed: {
		showInheritedSharesIcon() {
			if (this.loading) {
				return 'icon-loading-small'
			}
			if (this.showInheritedShares) {
				return 'icon-triangle-n'
			}
			return 'icon-triangle-s'
		},
		mainTitle() {
			return t('files_sharing', 'Others with access')
		},
		toggleTooltip() {
			return this.fileInfo.type === 'dir'
				? t('files_sharing', 'Toggle list of others with access to this directory')
				: t('files_sharing', 'Toggle list of others with access to this file')
		},
		fullPath() {
			const path = `${this.fileInfo.path}/${this.fileInfo.name}`
			return path.replace('//', '/')
		},
	},
	watch: {
		fileInfo() {
			this.resetState()
		},
	},
	methods: {
		/**
		 * Toggle the list view and fetch/reset the state
		 */
		toggleInheritedShares() {
			this.showInheritedShares = !this.showInheritedShares
			if (this.showInheritedShares) {
				this.fetchInheritedShares()
			} else {
				this.resetState()
			}
		},
		/**
		 * Fetch the Inherited Shares array
		 */
		async fetchInheritedShares() {
			this.loading = true
			try {
				const url = generateOcsUrl('apps/files_sharing/api/v1/shares/inherited?format=json&path={path}', { path: this.fullPath })
				const shares = await axios.get(url)

				this.shares = shares.data.ocs.data
					.map(share => new Share(share))
					.sort((a, b) => b.createdTime - a.createdTime)
				console.info(this.shares)
				
				this.loaded = true
			} catch (error) {
				OC.Notification.showTemporary(t('files_sharing', 'Unable to fetch inherited shares'), { type: 'error' })
			} finally {
				this.loading = false
			}
		},
		/**
		 * Reset current component state
		 */
		resetState() {
			this.loaded = false
			this.loading = false
			this.showInheritedShares = false
			this.shares = []
		},
		/**
		 * Remove a share from the shares list
		 *
		 * @param {Share} share the share to remove
		 */
		removeShare(share) {
			const index = this.shares.findIndex(item => item === share)
			// eslint-disable-next-line vue/no-mutating-props
			this.shares.splice(index, 1)
		},
	},
}
</script>

<style lang="scss" scoped>
.sharing-entry {
	display: flex;
	align-items: center;
	min-height: 44px;
	&__desc {
		padding: 8px 8px 8px 40px;
		line-height: 1.2em;
		position: relative;
		flex: 1 1;
		min-width: 0;
		p {
			color: var(--color-text-maxcontrast);
		}
	}
	&__title {
		white-space: nowrap;
		text-overflow: ellipsis;
		overflow: hidden;
		max-width: inherit;
	}
}

.sharing-entry__inherited {
	.avatar-shared {
		width: 32px;
		height: 32px;
		line-height: 32px;
		font-size: 18px;
		background-color: var(--color-text-maxcontrast);
		border-radius: 50%;
		flex-shrink: 0;
	}
}
</style>
