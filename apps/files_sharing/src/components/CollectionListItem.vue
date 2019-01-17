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
	<li class="collection-list">
		<avatar :displayName="collection.name" :allowPlaceholder="true"></avatar>
		<span class="username" title="">{{ collection.name }}</span>
		<div class="linked-icons">
			<transition name="fade">
				<a v-if="!detailsOpen" v-for="resource in collection.resources" :href="getLink(resource)" v-tooltip="resource.name"><span :class="getIcon(resource)"></span></a>
			</transition>
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
		<transition name="fade">
			<ul class="resource-list-details" v-if="detailsOpen" v-click-outside="hideDetails">
				<li v-for="resource in collection.resources">
					<a :href="getLink(resource)"><span :class="getIcon(resource)"></span> {{ resource.name }}</a>
					<span class="icon-delete"></span>
				</li>
			</ul>
		</transition>
	</li>
</template>

<script>
	import { Avatar } from 'nextcloud-vue';

	export default {
		name: 'CollectionListItem',
		components: {
			Avatar
		},
		props: {
			collection: {
				type: Object
			}
		},
		data() {
			return {
				isOpen: false,
				detailsOpen: false
			}
		},
		computed: {
			menu() {
				return [
					{
						action: () => {
							this.detailsOpen = true
							this.isOpen = false
						},
						icon: 'icon-info',
						text: t('files_sharing', 'Details'),
					},
					{
						action: () => {  },
						icon: 'icon-rename',
						text: t('files_sharing', 'Rename collection'),
					},{
						action: () => {  },
						icon: 'icon-delete',
						text: t('files_sharing', 'Remove collection'),
					}
				]
			},
			getIcon() {
				return (resource) => [window.Collaboration.getIcon(resource.type)]
			},
			getLink() {
				return (resource) => window.Collaboration.getLink(resource.type, resource.id)
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
			},
			hideDetails() {
				this.detailsOpen = false
			}
		}
	}
</script>

<style scoped lang="scss">
	.fade-enter-active, .fade-leave-active {
		transition: opacity .3s ease;
	}
	.fade-enter, .fade-leave-to
		/* .fade-leave-active below version 2.1.8 */ {
		opacity: 0;
	}
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
	.collection-list {
		flex-wrap: wrap;
		.resource-list-details {
			width: 100%;
			li {
				display: flex;
				margin-left: 44px;
				a {
					flex-grow: 1;
					padding: 3px;
				}
			}
			span {
				display: inline-block;
				padding: 8px;
				vertical-align: top;
				margin-right: 10px;
			}
		}
	}
</style>
