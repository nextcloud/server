/**
 * @copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 */

(function(OCP) {
	"use strict";

	OCP.WhatsNew = {

		query: function(options) {
			options = options || {};
			$.ajax({
				type: 'GET',
				url: options.url || OC.linkToOCS('core', 2) + 'whatsnew?format=json',
				success: options.success || this._onQuerySuccess,
				error: options.error || this._onQueryError
			});
		},

		dismiss: function(version, options) {
			options = options || {};
			$.ajax({
				type: 'POST',
				url: options.url || OC.linkToOCS('core', 2) + 'whatsnew',
				data: {version: encodeURIComponent(version)},
				success: options.success || this._onDismissSuccess,
				error: options.error || this._onDismissError
			});
		},

		_onQuerySuccess: function(data, statusText) {
			console.debug('querying Whats New data was successful: ' + data || statusText);
			console.debug(data);
		},

		_onQueryError: function (o, t, e) {
			console.debug(o);
			console.debug('querying Whats New Data resulted in an error: ' + t +e);
		},

		_onDismissSuccess: function(data) {
			console.debug('dismissing Whats New data was successful: ' + data);
		},

		_onDismissError: function (data) {
			console.debug('dismissing Whats New data resulted in an error: ' + data);
		}
	};
})(OCP);
