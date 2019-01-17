<template>
	<div>
		<ul id="shareWithList" class="shareWithList">
			<li @click="showSelect">
				<div class="avatar icon-category-integration icon-white"></div>
				<span class="username" title="" v-if="!selectIsOpen">{{ t('files_sharing', 'Add to a collection') }}</span>
				<multiselect v-else v-model="value" :options="options" :placeholder="placeholder">
					<template slot="singleLabel" slot-scope="props">
						<span class="option__desc"><span class="option__title">{{ props.option }}</span></span>
					</template>
				</multiselect>
			</li>
			<resource-list-item></resource-list-item>
			<resource-list-item></resource-list-item>
			<resource-list-item></resource-list-item>
		</ul>
		<pre>Vue component file model: {{ this.$root.model.name }} </pre>

	</div>
</template>

<style lang="scss" scoped>
	.multiselect {
		width: 100%;
	}


</style>

<script>
	import { Multiselect } from 'nextcloud-vue';
	import ResourceListItem from '../components/ResourceListItem';

	export default {
		name: 'CollaborationView',
		components: {
			ResourceListItem,
			Multiselect: Multiselect,
		},
		data() {
			return {
				selectIsOpen: false,
				generatingCodes: false,
				codes: undefined,
				value: null,
				model: {},
				options: ['list', 'of', 'options']
			};
		},
		mounted() {
			console.log('mounted');
			console.log(this.$root.model)
		},
		computed: {
			placeholder() {
				return t('core', 'Name, federated cloud ID or email address...');
			}
		},
		created: function() {
		},
		methods: {
			showSelect() {
				this.selectIsOpen = true
			},
			isVueComponent(object) {
				return object._isVue
			}
		}
	}
</script>
