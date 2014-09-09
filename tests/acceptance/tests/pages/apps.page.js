/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global module, protractor, element, by, browser, require */
(function() {
	var Page = require('../helper/page.js');

	var AppsPage = function(baseUrl) {
		this.baseUrl = baseUrl;
		this.path = 'index.php/settings/apps';
		this.url = baseUrl + this.path;

		this.appList = element(by.css('#app-navigation .applist'));
	};

	//================ LOCATOR FUNCTIONS ====================================//
	AppsPage.prototype.appId = function(appId) {
		return by.css('#app-navigation .applist [data-id=\'' + appId + '\']');
	};

	AppsPage.prototype.enableButtonId = function() {
		return by.css('#app-content .appinfo .enable');
	};

	AppsPage.prototype.groupsEnableCheckboxId = function() {
		return by.id('groups_enable');
	};

	AppsPage.prototype.groupsEnableListId = function() {
		return by.css('#app-content .multiselect.button');
	};
	//================ SHARED ===============================================//

	AppsPage.prototype.get = function() {
		browser.get(this.url);

		var appList = this.appList;
		browser.wait(function() {
			return appList.isDisplayed();
		}, 5000, 'load app page');
	};

	/**
	* Enables or disables the given app.
	*
	* @param {String} appId app id
	* @param {bool} [state] true (default) to enable the app, false otherwise
	* @param {Array} [groups] groups for which to enable the app or null to disable
	* group selection. If not specified (undefined), the group checkbox, if it exists,
	* will be left as is.
	*/
	AppsPage.prototype.enableApp = function(appId, state, groups) {
		var d = protractor.promise.defer();
		if (state === undefined) {
			state = true;
		}

		var enableButton = element(this.enableButtonId());

		element(this.appId(appId)).click();
		browser.wait(function() {
			return enableButton.isPresent();
		}, 800);

		// an app is already enabled if the button value is "Disable"
		enableButton.getAttribute('value').then(function(attr) {
			if (state !== (attr === 'Disable')) {
				enableButton.click();
			}
		});

		// wait for the button to change its attribute
		browser.wait(function() {
			return enableButton.getAttribute('value').then(function(attr) {
				return attr === state ? 'Disable' : 'Enable';
			});
		}, 800);

		if (state && groups !== undefined) {
			var groupsCheckbox = element(this.groupsEnableCheckboxId());
			var hasGroups = false;

			if (groups && groups.length > 0) {
				hasGroups = true;
			}

			// check/uncheck checkbox to match desired state
			groupsCheckbox.isSelected().then(function(checkboxState) {
				if (hasGroups !== checkboxState) {
					groupsCheckbox.click();
				}
			});

			// wait for checkbox to change state
			browser.wait(function() {
				return groupsCheckbox.isSelected().then(function(checkboxState) {
					return hasGroups === checkboxState;
				});
			}, 800);

			if (hasGroups) {
				var groupsList = element(this.groupsEnableListId());
				Page.multiSelectSetSelection(groupsList, groups).then(function() {
					d.fulfill(true);
				});
			} else {
				d.fulfill(true);
			}
		}
		return d.promise;
	};

	module.exports = AppsPage;
})();
