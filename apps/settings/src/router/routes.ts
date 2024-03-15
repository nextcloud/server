import type { RouteConfig } from 'vue-router'

import { defineAsyncComponent } from 'vue'

// Dynamic loading
const AppStore = defineAsyncComponent(() => import(/* webpackChunkName: 'settings-apps-view' */'../views/AppStore.vue'))
const AppStoreNavigation = defineAsyncComponent(() => import(/* webpackChunkName: 'settings-apps-view' */'../views/AppStoreNavigation.vue'))
const AppstoreSidebar = defineAsyncComponent(() => import(/* webpackChunkName: 'settings-apps-view' */'../views/AppStoreSidebar.vue'))

const UserManagement = defineAsyncComponent(() => import(/* webpackChunkName: 'settings-users' */'../views/UserManagement.vue'))
const UserManagementNavigation = defineAsyncComponent(() => import(/* webpackChunkName: 'settings-users' */'../views/UserManagementNavigation.vue'))

const routes: RouteConfig[] = [
	{
		name: 'users',
		path: '/:index(index.php/)?settings/users',
		components: {
			default: UserManagement,
			navigation: UserManagementNavigation,
		},
		props: true,
		children: [
			{
				path: ':selectedGroup',
				name: 'group',
			},
		],
	},
	{
		path: '/:index(index.php/)?settings/apps',
		name: 'apps',
		components: {
			default: AppStore,
			navigation: AppStoreNavigation,
			sidebar: AppstoreSidebar,
		},
		children: [
			{
				path: ':category',
				name: 'apps-category',
				children: [
					{
						path: ':id',
						name: 'apps-details',
					},
				],
			},
		],
	},
]

export default routes
