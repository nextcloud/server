/**
 * @copyright (c) 2018 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 */

(function(OC, OCA, Vue, $) {
	"use strict";

	OCA.UpdateNotification = OCA.UpdateNotification || {};

	OCA.UpdateNotification.App = {


		/** @type {number|null} */
		interval: null,

		/** @type {Vue|null} */
		vm: null,

		/**
		 * Initialise the app
		 */
		initialise: function() {
			var data = JSON.parse($('#updatenotification').attr('data-json'));
			this.vm = new Vue(OCA.UpdateNotification.Components.Root);

			this.vm.newVersionString = data.newVersionString;
			this.vm.lastCheckedDate = data.lastChecked;
			this.vm.isUpdateChecked = data.isUpdateChecked;
			this.vm.updaterEnabled = data.updaterEnabled;
			this.vm.downloadLink = data.downloadLink;
			this.vm.isNewVersionAvailable = data.isNewVersionAvailable;
			this.vm.updateServerURL = data.updateServerURL;
			this.vm.currentChannel = data.currentChannel;
			this.vm.channels = data.channels;
			this.vm.notifyGroups = data.notifyGroups;
			this.vm.isDefaultUpdateServerURL = data.isDefaultUpdateServerURL;
		}
	};
})(OC, OCA, Vue, $);

$(document).ready(function () {
	OCA.UpdateNotification.App.initialise();
});
