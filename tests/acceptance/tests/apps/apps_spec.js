/*
 * Copyright (c) 2014
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

/* global element, browser, require */
var Page = require('../helper/page.js');
var AppsPage = require('../pages/apps.page.js');
var LoginPage = require('../pages/login.page.js');

describe('Enabling apps', function() {
	var testApp;
	var params = browser.params;
	var loginPage;
	var appsPage;
	var testGroup;

	beforeEach(function() {
		isAngularSite(false);
		// app to test, must have a navigation entry and allow group restriction
		testApp = 'calendar';
		// group to test, additionally to "admin"
		testGroup = 'group1';
		loginPage = new LoginPage(params.baseUrl);
		appsPage = new AppsPage(params.baseUrl);

		loginPage.get();
		loginPage.login(params.login.user, params.login.password);
		appsPage.get();
	});

	afterEach(function() {
		Page.logout();
	});

	it('user should see enabled app', function() {
		appsPage.enableApp(testApp, true, null).then(function() {
			// reload page
			appsPage.get();
			Page.toggleAppsMenu();
			expect(element(Page.appMenuEntryId(testApp + '_index')).isPresent()).toBe(true);
		});
	});

	it('user should not see disabled app', function() {
		appsPage.enableApp(testApp, false, null).then(function() {
			// reload page
			appsPage.get();
			Page.toggleAppsMenu();
			expect(element(Page.appMenuEntryId(testApp + '_index')).isPresent()).toBe(false);
		});
	});

	it('group member should see app when enabled in that group', function() {
		appsPage.enableApp(testApp, true, ['admin']).then(function() {
			// reload page
			appsPage.get();
			Page.toggleAppsMenu();
			expect(element(Page.appMenuEntryId(testApp + '_index')).isPresent()).toBe(true);
		});
	});

	it('group member should not see app when enabled in another group', function() {
		appsPage.enableApp(testApp, true, ['group1']).then(function() {
			// reload page
			appsPage.get();
			Page.toggleAppsMenu();
			expect(element(Page.appMenuEntryId(testApp + '_index')).isPresent()).toBe(false);
		});
	});

	it('group member should see app when all groups deselected (all case)', function() {
		// when selecting no groups, it will show "All" even though the checkboxes
		// are not checked
		appsPage.enableApp(testApp, true, []).then(function() {
			// reload page
			appsPage.get();
			Page.toggleAppsMenu();
			expect(element(Page.appMenuEntryId(testApp + '_index')).isPresent()).toBe(false);
		});
	});
});
