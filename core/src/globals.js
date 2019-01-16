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
import 'bootstrap/js/tooltip'
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

import OC from './oc';
import OCP from './OCP/index';
import OCA from './oca';
import escapeHTML from './util/escapeHTML'
import './jquery.avatar';
import './jquery.contactsmenu';
import './jquery.exists';
import './jquery.filter-attr';
import './jquery.ocdialog';
import './jquery.octemplate';
import './jquery.placeholder';
import './jquery.select-range';
import './jquery.tipsy';
import formatDate from './util/format-date';
import getURLParameter from './util/get-url-parameter';
import humanFileSize from './util/human-file-size';
import relative_modified_date from './util/relative-modified-date';

const expose = (name, object) => window[name] = object;

// Vendor scripts
expose('_', _);
expose('$', $);
expose('autosize', autosize);
expose('Backbone', Backbone);
expose('Clipboard', ClipboardJS);
expose('ClipboardJS', ClipboardJS);
expose('cssVars', cssVars);
expose('dav', dav);
expose('DOMPurify', DOMPurify);
expose('Handlebars', Handlebars);
expose('jstimezonedetect', jstimezonedetect);
expose('jstz', jstimezonedetect);
expose('jQuery', $);
expose('marked', marked);
expose('md5', md5);
expose('moment', moment);

// Nextcloud scripts
expose('escapeHTML', escapeHTML);
expose('formatDate', formatDate);
expose('getURLParameter ', getURLParameter);
window.humanFileSize = humanFileSize; // TODO: find out why `expose` does not work here
expose('humanFileSize ', humanFileSize);
expose('relative_modified_date ', relative_modified_date);
expose('OC', OC);
expose('OCP', OCP);
expose('OCA', OCA);
