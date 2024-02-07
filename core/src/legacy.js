/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
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

import { deprecate } from './utils/deprecate.js'

// This needs to be the first to setup the globals
import './legacy-globals.js'
import 'jquery-ui-dist/jquery-ui.js'
import 'jquery-ui-dist/jquery-ui.css'
import 'jquery-ui-dist/jquery-ui.theme.css'
import 'select2'
import 'select2/select2.css'
import 'snap.js/dist/snap.js'
import 'strengthify'
import 'strengthify/strengthify.css'

window.$.fn.select2 = deprecate(window.$.fn.select2, 'select2', 19)
