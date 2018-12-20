/*
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
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

import _ from 'underscore'
import $ from 'jquery'
import 'jquery-migrate/dist/jquery-migrate.min'
import 'jquery-ui'
// TODO: remove anything that is not really used
import 'jquery-ui/ui/widgets/accordion'
import 'jquery-ui/ui/widgets/autocomplete'
import 'jquery-ui/ui/widgets/button'
import 'jquery-ui/ui/widgets/checkboxradio'
import 'jquery-ui/ui/widgets/controlgroup'
import 'jquery-ui/ui/widgets/datepicker'
import 'jquery-ui/ui/widgets/dialog'
import 'jquery-ui/ui/widgets/draggable'
import 'jquery-ui/ui/widgets/droppable'
import 'jquery-ui/ui/widgets/menu'
import 'jquery-ui/ui/widgets/mouse'
import 'jquery-ui/ui/widgets/progressbar'
import 'jquery-ui/ui/widgets/resizable'
import 'jquery-ui/ui/widgets/selectable'
import 'jquery-ui/ui/widgets/selectmenu'
import 'jquery-ui/ui/widgets/slider'
import 'jquery-ui/ui/widgets/sortable'
import 'jquery-ui/ui/widgets/tabs'
import 'jquery-ui/ui/widgets/tooltip'
// TODO END
import 'jquery-ui/themes/base/all.css'
import autosize from 'autosize'
import Backbone from 'backbone'
import 'bootstrap/js/tooltip'
import Clipboard from 'clipboard'
import cssVars from 'css-vars-ponyfill'
import dav from 'davclient.js'
import DOMPurify from 'dompurify'
import Handlebars from 'handlebars'
import 'jcrop/js/jquery.Jcrop'
import 'jcrop/css/jquery.Jcrop.css'
import jstimezonedetect from 'jstimezonedetect'
import marked from 'marked'
import md5 from 'blueimp-md5'
import moment from 'moment'
import Select2 from 'select2'
import 'snap.js/dist/snap'
import 'strengthify'
import 'strengthify/strengthify.css'

window['_'] = _
window['$'] = $
window['autosize'] = autosize
window['Backbone'] = Backbone
window['Clipboard'] = Clipboard
window['cssVars'] = cssVars
window['dav'] = dav
window['DOMPurify'] = DOMPurify
window['Handlebars'] = Handlebars
window['jstimezonedetect'] = jstimezonedetect
window['jstz'] = jstimezonedetect
window['jQuery'] = $
window['marked'] = marked
window['md5'] = md5
window['moment'] = moment
window['Select2'] = Select2
