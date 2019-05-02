/*
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

import Apps from './apps'
import AppConfig from './appconfig'
import Backbone from './backbone'
import Config from './config'
import ContactsMenu from './contactsmenu'
import Dialogs from './dialogs'
import EventSource from './eventsource'
import L10N from './l10n'
import msg from './msg'
import Notification from './notification'
import PasswordConfirmation from './password-confirmation'
import Plugins from './plugins'
import search from './search'
import Util from './util'
import {redirect, reload} from './navigation'

/** @namespace OC */
export default {
	Apps,
	AppConfig,
	Backbone,
	ContactsMenu,
	config: Config,
	dialogs: Dialogs,
	EventSource,
	L10N,
	msg,
	Notification,
	PasswordConfirmation,
	Plugins,
	search,
	Util,
	redirect,
	reload,
}
