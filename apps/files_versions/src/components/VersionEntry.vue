<!--
  - @copyright Copyright (c) 2021 Enoch <enoch@nextcloud.com>
  -
  - @author Enoch <enoch@nextcloud.com>
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
	<li>

		<ListItemIcon
			v-if="!isLatestChange"
			:title="relativeDate"
			:subtitle="formattedSize"
			:url="iconUrl"
			class="version-entry">
			<Actions class="version-entry__actions">
				<ActionButton v-if="canRevert" icon="icon-history" @click="restoreVersion">
					{{ t('files_versions','Restore') }}
				</ActionButton>
				<ActionLink icon="icon-download" :href="versionUrl">
					{{ t('files_versions','Download') }}
				</ActionLink>
			</Actions>
		</ListItemIcon>
		</div>
	</li>
</template>

<script>
import moment from '@nextcloud/moment'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionLink from '@nextcloud/vue/dist/Components/ActionLink'
import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip'
import ListItemIcon from '@nextcloud/vue/dist/Components/ListItemIcon'

import { generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import client from "../services/DavClient";


export default {
	name: 'VersionEntry',

	components: {
		Actions,
		ActionButton,
		ActionLink,
		ListItemIcon,

	},

	directives: {
		Tooltip,
	},

	props: {
		fileInfo: {
			type: Object,
			required: true,
		},
		version: {
			type: Object,
			required: true,
		},
		tooltip: {
			type: String,
			default: '',
		},
	},

	computed: {
		// Does the current user have permissions to revert this file
		canRevert () {
			// TODO: implement permission check
			return true
		},
		/**
		 * If the basename is just the file id,
		 * this is the latest file version entry
		 * @returns {boolean}
		 */
		isLatestChange() {
			return this.fileInfo.id === this.version.basename
		},
		versionUrl() {
			return generateUrl('/remote.php/dav/versions/{user}' + this.version.filename, {
				user: getCurrentUser().uid,
			})
		},
		iconUrl() {
			return OC.MimeType.getIconUrl(this.fileInfo.mimetype)
		},
		formattedSize() {
			return OC.Util.humanFileSize(this.version.size, true)
		},
		relativeDate() {
			return moment(this.version.lastmod).fromNow()
		},
	},
	methods: {
             // Restores the original file to this revision
		 restoreVersion () {
			// TODO: implement restore request and loading
			return client.move('/remote.php/dav/versions/{user}' + this.version.basename, {
				user: getCurrentUser().uid,
			},'/restore/target', true)
		},
	},
}

</script>

	<style lang="scss" scoped>
	.version-entry {
		display: flex;
		align-items: center;
		height: 44px;
		&__desc {
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			padding: 8px;
			line-height: 1.6em;
			p {
				color: var(--color-text-maxcontrast);
			}
			&-unique {
				color: var(--color-text-maxcontrast);
			}
		}
		&__actions {
			margin-left: auto;
		}
		// Remove avatar border-radius around file type icon
		::v-deep .avatardiv img {
			border-radius: 0;
		}

	}
	</style>
