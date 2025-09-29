/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014 ownCloud Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

describe('OC.L10N tests', function() {
	var TEST_APP = 'jsunittestapp';

	beforeEach(function() {
		window._oc_appswebroots[TEST_APP] = OC.getRootPath() + '/apps3/jsunittestapp';

		window.OC = window.OC ?? {}
		window.OC.appswebroots = window.OC.appswebroots || {}
		window.OC.appswebroots[TEST_APP] = OC.getRootPath() + '/apps3/jsunittestapp'
	});
	afterEach(function() {
		OC.L10N._unregister(TEST_APP);
		delete window._oc_appswebroots[TEST_APP];
		delete window.OC.appswebroots[TEST_APP];
	});

	describe('text translation', function() {
		beforeEach(function() {
			spyOn(console, 'warn');
			OC.L10N.register(TEST_APP, {
				'Hello world!': 'Hallo Welt!',
				'Hello {name}, the weather is {weather}': 'Hallo {name}, das Wetter ist {weather}',
				'sunny': 'sonnig'
			});
		});
		it('returns untranslated text when no bundle exists', function() {
			OC.L10N._unregister(TEST_APP);
			expect(t(TEST_APP, 'unknown text')).toEqual('unknown text');
		});
		it('returns untranslated text when no key exists', function() {
			expect(t(TEST_APP, 'unknown text')).toEqual('unknown text');
		});
		it('returns translated text when key exists', function() {
			expect(t(TEST_APP, 'Hello world!')).toEqual('Hallo Welt!');
		});
		it('returns translated text with placeholder', function() {
			expect(
				t(TEST_APP, 'Hello {name}, the weather is {weather}', {name: 'Steve', weather: t(TEST_APP, 'sunny')})
			).toEqual('Hallo Steve, das Wetter ist sonnig');
		});
		it('returns text with escaped placeholder', function() {
			expect(
				t(TEST_APP, 'Hello {name}', {name: '<strong>Steve</strong>'})
			).toEqual('Hello &lt;strong&gt;Steve&lt;/strong&gt;');
		});
		it('returns text with not escaped placeholder', function() {
			expect(
				t(TEST_APP, 'Hello {name}', {name: '<strong>Steve</strong>'}, null, {escape: false})
			).toEqual('Hello <strong>Steve</strong>');
		});
		it('uses DOMPurify to escape the text', function() {
			expect(
				t(TEST_APP, '<strong>These are your search results<script>alert(1)</script></strong>', null, {escape: false})
			).toEqual('<strong>These are your search results</strong>');
		});
		it('keeps old texts when registering existing bundle', function() {
			OC.L10N.register(TEST_APP, {
				'sunny': 'sonnig',
				'new': 'neu'
			});
			expect(t(TEST_APP, 'sunny')).toEqual('sonnig');
			expect(t(TEST_APP, 'new')).toEqual('neu');
		});
	});
	describe('plurals', function() {
		function checkPlurals() {
			expect(
				n(TEST_APP, 'download %n file', 'download %n files', 0)
			).toEqual('0 Dateien herunterladen');
			expect(
				n(TEST_APP, 'download %n file', 'download %n files', 1)
			).toEqual('1 Datei herunterladen');
			expect(
				n(TEST_APP, 'download %n file', 'download %n files', 2)
			).toEqual('2 Dateien herunterladen');
			expect(
				n(TEST_APP, 'download %n file', 'download %n files', 1024)
			).toEqual('1024 Dateien herunterladen');
		}

		it('generates plural for default text when translation does not exist', function() {
			spyOn(console, 'warn');
			OC.L10N.register(TEST_APP, {
			});
			expect(
				n(TEST_APP, 'download %n file', 'download %n files', 0)
			).toEqual('download 0 files');
			expect(
				n(TEST_APP, 'download %n file', 'download %n files', 1)
			).toEqual('download 1 file');
			expect(
				n(TEST_APP, 'download %n file', 'download %n files', 2)
			).toEqual('download 2 files');
			expect(
				n(TEST_APP, 'download %n file', 'download %n files', 1024)
			).toEqual('download 1024 files');
		});
		it('generates plural with default function when no forms specified', function() {
			spyOn(console, 'warn');
			OC.L10N.register(TEST_APP, {
				'_download %n file_::_download %n files_':
					['%n Datei herunterladen', '%n Dateien herunterladen']
			});
			checkPlurals();
		});
	});
});
