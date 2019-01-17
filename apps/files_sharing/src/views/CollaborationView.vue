<template>
	<div :class="{'icon-loading': !collections}">
		<ul id="shareWithList" class="shareWithList" v-if="collections">
			<li @click="showSelect">
				<div class="avatar"><span class="icon-category-integration icon-white"></span></div>
				<multiselect v-model="value" :options="options" :placeholder="placeholder" tag-placeholder="Create a new collection" ref="select" @select="select" label="title" track-by="title" :reset-after="true">
					<template slot="singleLabel" slot-scope="props">
						<span class="option__desc">
							<span class="option__title">{{ props.option.title }}</span></span>
					</template>
					<template slot="option" slot-scope="props">
						<span class="option__wrapper">
							<span v-if="props.option.class" :class="props.option.class" class="avatar"></span>
							<avatar v-else :displayName="props.option.title" :allowPlaceholder="true"></avatar>
							<span class="option__title">{{ props.option.title }}</span>
						</span>
					</template>
				</multiselect>
			</li>
			<collection-list-item v-for="collection in collections" :collection="collection" :key="collection.id" />
		</ul>
	</div>
</template>

<style lang="scss" scoped>
	.multiselect {
		width: 100%;
		margin-left: 3px;
	}
	span.avatar {
		padding: 16px;
		display: block;
		background-repeat: no-repeat;
		background-position: center;
		opacity: 0.7;
		&:hover {
			opacity: 1;
		}
	}

	/** TODO provide white icon in core */
	.icon-category-integration.icon-white {
		filter: invert(100%);
		padding: 16px;
		display: block;
		background-repeat: no-repeat;
		background-position: center;
	}

	.option__wrapper {
		display: flex;
		.avatar {
			display: block;
			background-color: var(--color-background-darker) !important;
		}
		.option__title {
			padding: 4px;
		}
	}

</style>
<style lang="scss">
	/** TODO check why this doesn't work when scoped */
	.shareWithList .multiselect:not(.multiselect--active ) .multiselect__tags {
		border: none !important;
		input::placeholder {
			color: var(--color-main-text);
		}
	}
</style>

<script>
	import { Multiselect, Avatar } from 'nextcloud-vue';
	import CollectionListItem from '../components/CollectionListItem';
	import axios from 'nextcloud-axios';

	export default {
		name: 'CollaborationView',
		components: {
			CollectionListItem,
			Avatar,
			Multiselect: Multiselect,
		},
		data() {
			return {
				selectIsOpen: false,
				generatingCodes: false,
				codes: undefined,
				value: null,
				model: {},
				collections: null
			};
		},
		mounted() {
			let resourceId = this.$root.model.id
			/** TODO move to service */
			const resourceBase = OC.linkToOCS(`collaboration/resources/files`);
			axios.get(`${resourceBase}${resourceId}?format=json`, {
				headers: {
					'OCS-APIRequest': true,
					'Content-Type': 'application/json; charset=UTF-8'
				}
			}).then((response) => {
				this.collections = response.data.ocs.data
			});
		},
		computed: {
			placeholder() {
				return t('files_sharing', 'Add to a collection');
			},
			options() {
				let options = [];
				let types = window.Collaboration.getTypes();
				for(let type in types) {
					options.push({
						type: types[type],
						title: window.Collaboration.getLabel(types[type]),
						class: window.Collaboration.getIcon(types[type]),
						action: () => window.Collaboration.trigger(types[type])
					})
				}
				for(let index in this.collections) {
					options.push({
						title: this.collections[index].name
					})
				}
				return options;
			}
		},
		created: function() {
		},
		methods: {
			select(selectedOption, id) {
				selectedOption.action().then((id) => {
					console.log('Create a new collection with')
					console.log('This file ', this.$root.model.id)
					console.log('Selected resource ', selectedOption.type, id)
					this.createCollection(this.$root.model.id, selectedOption.type, id)
				}).catch((e) => {
					console.error('No resource selected');
				});
			},
			showSelect() {
				this.selectIsOpen = true
				this.$refs.select.$el.focus()
			},
			hideSelect() {
				this.selectIsOpen = false
			},
			isVueComponent(object) {
				return object._isVue
			},
			createCollection(resourceIdBase, resourceType, resourceId) {
				/** TODO move to service */
				const resourceBase = OC.linkToOCS(`collaboration/resources/files`, 2);
				axios.post(`${resourceBase}${resourceIdBase}?format=json`, {
					name: 'Example collection'
				}, {
					headers: {
						'OCS-APIRequest': true,
						'Content-Type': 'application/json; charset=UTF-8'
					}
				}).then((response) => {
					console.log(response.data.ocs.data)
				});
			},
			addResourceToCollection(collectionId, resourceType, resourceId) {
				/** TODO move to service */
				const resourceBase = OC.linkToOCS(`collaboration/resources/collections`, 2);
				axios.post(`${resourceBase}${collectionId}?format=json`, {
					resourceType,
					resourceId
				}, {
					headers: {
						'OCS-APIRequest': true,
						'Content-Type': 'application/json; charset=UTF-8'
					}
				}).then((response) => {
					console.log(response)
				});
			}
		}
	}
</script>
