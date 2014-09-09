/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global protractor, module, element, by, browser */
(function() {
	var Page = function() {

	};

	Page.prototype.moveMouseTo = function(locator) {
		var ele = element(locator);
		return browser.actions().mouseMove(ele).perform();
	};

	Page.toggleAppsMenu = function() {
		var el = element(this.appsMenuId());
		return el.click();
	};

	Page.logout = function() {
		element(Page.settingsMenuId()).click();
		element(by.id('logout')).click();
		browser.sleep(300);
	};

	//================ LOCATOR FUNCTIONS ====================================//
	Page.appsMenuId = function() {
		return by.css('#header .menutoggle');
	};

	Page.appMenuEntryId = function(appId) {
		return by.css('nav #apps [data-id=\'' + appId + '\']');
	};

	Page.settingsMenuId = function() {
		return by.css('#header #settings');
	};

	//================ UTILITY FUNCTIONS ====================================//

	/**
	 * Sets the selection of a multiselect element
	 *
	 * @param el select element of the multiselect
	 * @param {Array} id of the values to select
	 */
	Page.multiSelectSetSelection = function(el, selection) {
		var d = protractor.promise.defer();
		var dropDownEl = element(by.css('.multiselectoptions.down'));

		el.click();

		function processEntry(entry) {
			entry.isSelected().then(function(selected) {
				entry.getAttribute('id').then(function(inputId) {
					// format is "ms0-option-theid", we extract that id
					var dataId = inputId.split('-')[2];
					var mustBeSelected = selection.indexOf(dataId) >= 0;
					// if state doesn't match what we want, toggle

					if (selected !== mustBeSelected) {
						// need to click on the label, not input
						entry.element(by.xpath('following-sibling::label')).click();
						// confirm that the checkbox was set
						browser.wait(function() {
							return entry.isSelected().then(function(newSelection) {
								return newSelection === mustBeSelected;
							});
						});
					}
				});
			});
		}

		browser.wait(function() {
			return dropDownEl.isPresent();
		}, 1000).then(function() {
			dropDownEl.all(by.css('[type=checkbox]')).then(function(entries) {
				for (var i = 0; i < entries.length; i++) {
					processEntry(entries[i]);
				}
				// give it some time to save changes
				browser.sleep(300).then(function() {
					d.fulfill(true);
				});
			});
		});

		return d.promise;
	};

	module.exports = Page;
})();
