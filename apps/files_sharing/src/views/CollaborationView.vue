<!--
  - @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
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
	<collection-list v-if="fileId" type="files" :id="fileId" :name="filename"></collection-list>
</template>

<script>
	import { CollectionList } from 'nextcloud-vue-collections'

	/**
	 * Those translations will be used by the vue component but they should be shipped with the server
	 * t('files_sharing', 'Add to a collection')
	 * t('files_sharing', 'Details')
	 * t('files_sharing', 'Rename collection')
	 */

	export default {
		name: 'CollaborationView',
		computed: {
			fileId() {
				if (this.$root.model && this.$root.model.id) {
					return '' + this.$root.model.id;
				}
				return null;
			},
			filename() {
				if (this.$root.model && this.$root.model.name) {
					return '' + this.$root.model.name;
				}
				return '';
			}
		},
		components: {
			CollectionList
		}
	}
</script>
