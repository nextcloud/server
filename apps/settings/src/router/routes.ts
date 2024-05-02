import type { RouteConfig } from 'vue-router'

// Dynamic loading
const AppStore = () => import(/* webpackChunkName: 'settings-apps-view' */'../views/AppStore.vue')
const AppStoreNavigation = () => import(/* webpackChunkName: 'settings-apps-view' */'../views/AppStoreNavigation.vue')
const AppStoreSidebar = () => import(/* webpackChunkName: 'settings-apps-view' */'../views/AppStoreSidebar.vue')

const UserManagement = () => import(/* webpackChunkName: 'settings-users' */'../views/UserManagement.vue')
const UserManagementNavigation = () => import(/* webpackChunkName: 'settings-users' */'../views/UserManagementNavigation.vue')

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
		// redirect to our default route - the app discover section
		redirect: {
			name: 'apps-category',
			params: {
				category: 'discover',
			},
		},
		components: {
			default: AppStore,
			navigation: AppStoreNavigation,
			sidebar: AppStoreSidebar,
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
