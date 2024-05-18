/**
 * @copyright Copyright (c) 2018 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
import { translate, translatePlural } from '@nextcloud/l10n'

import Vue from 'vue'
import Root from './components/UpdateNotification.vue'

Vue.mixin({
	methods: {
		t(app, text, vars, count, options) {
			return translate(app, text, vars, count, options)
		},
		n(app, textSingular, textPlural, count, vars, options) {
			return translatePlural(app, textSingular, textPlural, count, vars, options)
		},
	},
})

// eslint-disable-next-line no-new
new Vue({
	el: '#updatenotification',
	render: h => h(Root),
})
