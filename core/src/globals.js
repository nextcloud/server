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
// TODO: switch to `jquery-ui` package and import widgets and effects individually
//       `jquery-ui-dist` is used as a workaround for the issue of missing effects
import 'jquery-ui-dist/jquery-ui'
import 'jquery-ui-dist/jquery-ui.css'
import 'jquery-ui-dist/jquery-ui.theme.css'
// END TODO
import autosize from 'autosize'
import Backbone from 'backbone'
import 'bootstrap/js/dist/tooltip'
import ClipboardJS from 'clipboard'
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
import 'Select2'
import 'Select2/select2.css'
import 'snap.js/dist/snap'
import 'strengthify'
import 'strengthify/strengthify.css'

import OC from './OC/index'
import OCP from './OCP/index'
import OCA from './OCA/index'
import escapeHTML from './Util/escapeHTML'
import formatDate from './Util/format-date'
import getURLParameter from './Util/get-url-parameter'
import humanFileSize from './Util/human-file-size'
import relative_modified_date from './Util/relative-modified-date'

window['_'] = _
window['$'] = $
window['autosize'] = autosize
window['Backbone'] = Backbone
window['Clipboard'] = ClipboardJS
window['ClipboardJS'] = ClipboardJS
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

window['OC'] = OC
window['OCP'] = OCP
window['OCA'] = OCA
window['escapeHTML'] = escapeHTML
window['formatDate'] = formatDate
window['getURLParameter'] = getURLParameter
window['humanFileSize'] = humanFileSize
window['relative_modified_date'] = relative_modified_date

/**
 * translate a string
 * @param {string} app the id of the app for which to translate the string
 * @param {string} text the string to translate
 * @param [vars] map of placeholder key to value
 * @param {number} [count] number to replace %n with
 * @return {string}
 */
window.t = _.bind(OC.L10N.translate, OC.L10N);

/**
 * translate a string
 * @param {string} app the id of the app for which to translate the string
 * @param {string} text_singular the string to translate for exactly one object
 * @param {string} text_plural the string to translate for n objects
 * @param {number} count number to determine whether to use singular or plural
 * @param [vars] map of placeholder key to value
 * @return {string} Translated string
 */
window.n = _.bind(OC.L10N.translatePlural, OC.L10N);
