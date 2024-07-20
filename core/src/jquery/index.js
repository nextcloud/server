/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import $ from 'jquery'

import './avatar.js'
import './contactsmenu.js'
import './exists.js'
import './filterattr.js'
import './ocdialog.js'
import './octemplate.js'
import './placeholder.js'
import './requesttoken.js'
import './selectrange.js'
import './showpassword.js'
import './ui-fixes.js'

import './css/jquery-ui-fixes.scss'
import './css/jquery.ocdialog.scss'

/**
 * Disable automatic evaluation of responses for $.ajax() functions (and its
 * higher-level alternatives like $.get() and $.post()).
 *
 * If a response to a $.ajax() request returns a content type of "application/javascript"
 * JQuery would previously execute the response body. This is a pretty unexpected
 * behaviour and can result in a bypass of our Content-Security-Policy as well as
 * multiple unexpected XSS vectors.
 */
$.ajaxSetup({
	contents: {
		script: false,
	},
})

/**
 * Disable execution of eval in jQuery. We do require an allowed eval CSP
 * configuration at the moment for handlebars et al. But for jQuery there is
 * not much of a reason to execute JavaScript directly via eval.
 *
 * This thus mitigates some unexpected XSS vectors.
 */
$.globalEval = function() {
}
