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
	<li>
		<avatar displayName="Design" :allowPlaceholder="true"></avatar>
		<span class="username" title="">Design</span>
		<div class="linked-icons">
			<a href="" v-tooltip="'Design team calendar'"><span class="icon-calendar-dark"></span></a>
			<a href="" v-tooltip="'Design projet overview'"><span class="nav-icon-files"></span></a>
		</div>
		<span class="sharingOptionsGroup">
				<div class="share-menu" v-click-outside="close">
					<a href="#" class="icon icon-more" @click="toggle"></a>
					<span class="icon icon-loading-small hidden"></span>
					<div class="popovermenu" :class="{open: isOpen}">
						<popover-menu :menu="menu"></popover-menu>
					</div>
				</div>
		</span>
	</li>
</template>

<script>
	import { Avatar } from 'nextcloud-vue';

	export default {
		name: 'ResourceListItem',
		components: {
			Avatar
		},
		data() {
			return {
				isOpen: false
			}
		},
		computed: {
			menu() {
				return [
					{
						action: () => {  },
						icon: 'icon-delete',
						text: t('files_sharing', 'Remove collection'),
					}
				]
			}
		},
		methods: {
			open() {
				this.isOpen = true
			},
			close() {
				this.isOpen = false
			},
			toggle() {
				this.isOpen = !this.isOpen
			}
		}
	}
</script>

<style scoped lang="scss">
	.linked-icons {
		display: flex;
	span {
		padding: 16px;
		display: block;
		background-repeat: no-repeat;
		background-position: center;
		opacity: 0.7;
	&:hover {
		 opacity: 1;
	 }
	}
	}
	.icon-category-integration {
		background-color: var(--color-background-dark) !important;
	}
</style>
