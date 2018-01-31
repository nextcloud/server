/**
 * @copyright (c) 2018 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 */

/* global $, define */

define(function (require) {
	"use strict";

	return {

		/** @type {Vue|null} */
		vm: null,

		/**
		 * Initialise the app
		 */
		initialise: function() {
			var data = JSON.parse($('#updatenotification').attr('data-json'));
			var Vue = require('vue');
			var vSelect = require('vue-select');
			Vue.component('v-select', vSelect.VueSelect);
			this.vm = new Vue(require('./components/root.vue'));

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
});
