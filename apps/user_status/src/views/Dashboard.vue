<!--
  - @copyright Copyright (c) 2020 Georg Ehrke <oc.list@georgehrke.com>
  - @author Georg Ehrke <oc.list@georgehrke.com>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<DashboardWidget
		id="user-status_panel"
		:items="items"
		:loading="loading">
		<template v-slot:empty-content>
			<EmptyContent
				id="user_status-widget-empty-content"
				icon="icon-user-status">
				{{ t('user_status', 'No recent status changes') }}
			</EmptyContent>
		</template>
	</DashboardWidget>
</template>

<script>
import { DashboardWidget } from '@nextcloud/vue-dashboard'
import EmptyContent from '@nextcloud/vue/dist/Components/EmptyContent'
import { loadState } from '@nextcloud/initial-state'
import moment from '@nextcloud/moment'

export default {
	name: 'Dashboard',
	components: {
		DashboardWidget,
		EmptyContent,
	},
	data() {
		return {
			statuses: [],
			loading: true,
		}
	},
	computed: {
		items() {
			return this.statuses.map((item) => {
				const icon = item.icon || ''
				const message = item.message || ''
				const status = `${icon} ${message}`

				let subText
				if (item.icon === null && item.message === null && item.timestamp === null) {
					subText = ''
				} else if (item.icon === null && item.message === null && item.timestamp !== null) {
					subText = moment(item.timestamp, 'X').fromNow()
				} else if (item.timestamp !== null) {
					subText = this.t('user_status', '{status}, {timestamp}', {
						status,
						timestamp: moment(item.timestamp, 'X').fromNow(),
					})
				} else {
					subText = status
				}

				return {
					mainText: item.displayName,
					subText,
					avatarUsername: item.userId,
				}
			})
		},
	},
	mounted() {
		try {
			this.statuses = loadState('user_status', 'dashboard_data')
			this.loading = false
		} catch (e) {
			console.error(e)
		}
	},
}
</script>

<style lang="scss">
#user_status-widget-empty-content {
	text-align: center;
	margin-top: 5vh;
}
</style>
