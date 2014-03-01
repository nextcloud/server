/**
* ownCloud
*
* @author Vincent Petry
* @copyright 2014 Vincent Petry <pvince81@owncloud.com>
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

/* global OC */

/**
 * Simulate the variables that are normally set by PHP code
 */

// from core/js/config.php
window.TESTING = true;
window.oc_debug = true;
window.datepickerFormatDate = 'MM d, yy';
window.dayNames = [
	'Sunday',
	'Monday',
	'Tuesday',
	'Wednesday',
	'Thursday',
	'Friday',
	'Saturday'
];
window.monthNames = [
	'January',
	'February',
	'March',
	'April',
	'May',
	'June',
	'July',
	'August',
	'September',
	'October',
	'November',
	'December'
];
window.firstDay = 0;

// setup dummy webroots
window.oc_webroot = location.href + '/';
window.oc_appswebroots = {
	"files": window.oc_webroot + '/apps/files/'
};
window.oc_config = {
	session_lifetime: 600 * 1000,
	session_keepalive: false
};
window.oc_appconfig = {
	core: {}
};
window.oc_defaults = {};

// global setup for all tests
(function setupTests() {
	var fakeServer = null,
		$testArea = null,
		routesRequestStub;

	beforeEach(function() {
		// test area for elements that need absolute selector access or measure widths/heights
		// which wouldn't work for detached or hidden elements
		$testArea = $('<div id="testArea" style="position: absolute; width: 1280px; height: 800px; top: -3000px; left: -3000px;"></div>');
		$('body').append($testArea);
		// enforce fake XHR, tests should not depend on the server and
		// must use fake responses for expected calls
		fakeServer = sinon.fakeServer.create();

		// return fake translations as they might be requested for many test runs
		fakeServer.respondWith(/\/index.php\/core\/ajax\/translations.php$/, [
				200, {
					"Content-Type": "application/json"
				},
				'{"data": [], "plural_form": "nplurals=2; plural=(n != 1);"}'
			]
		);

		// make it globally available, so that other tests can define
		// custom responses
		window.fakeServer = fakeServer;
	});

	afterEach(function() {
		// uncomment this to log requests
		// console.log(window.fakeServer.requests);
		fakeServer.restore();

		$testArea.remove();
	});
})();

