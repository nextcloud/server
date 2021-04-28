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
	<ListItemIcon class="version-entry"
		icon="icon-text"
		title="10 days ago"
		subtitle="< 1KB">
		<Actions>
			<ActionButton icon="icon-edit" @click="alert('Edit')">
				{{ version.timestamp }}Restore
			</ActionButton>
			<ActionButton icon="icon-delete" @click="alert('Delete')">
				Download
			</ActionButton>
		</Actions>
	</ListItemIcon>
</template>

<script>
import Actions from '@nextcloud/vue/dist/Components/Actions'
import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip'
import ListItemIcon from '@nextcloud/vue/dist/Components/ListItemIcon'
export default {
	name: 'VersionEntry',

	components: {
		Actions,
		ListItemIcon

	},

	directives: {
		Tooltip,
	},
	props: {
		versions: {

		},
	},

}
</script>

<style lang="scss" scoped>
.version-entry {
	display: flex;
	align-items: center;
	min-height: 44px;
	&__desc {
		padding: 8px;
		line-height: 1.2em;
		position: relative;
		flex: 1 1;
		min-width: 0;
		h5 {
			white-space: nowrap;
			text-overflow: ellipsis;
			overflow: hidden;
			max-width: inherit;
		}
		p {
			color: var(--color-text-maxcontrast);
		}
	}
	&__actions {
		margin-left: auto !important;
	}
}
</style>
