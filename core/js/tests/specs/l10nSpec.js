/**
 * Copyright (c) 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

describe('OC.L10N tests', function() {
	var TEST_APP = 'jsunittestapp';

	afterEach(function() {
		delete OC.L10N._bundles[TEST_APP];
	});

	describe('text translation', function() {
		beforeEach(function() {
			OC.L10N.register(TEST_APP, {
				'Hello world!': 'Hallo Welt!',
				'Hello {name}, the weather is {weather}': 'Hallo {name}, das Wetter ist {weather}',
				'sunny': 'sonnig'
			});
		});
		it('returns untranslated text when no bundle exists', function() {
			delete OC.L10N._bundles[TEST_APP];
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
			OC.L10N.register(TEST_APP, {
				'_download %n file_::_download %n files_':
					['%n Datei herunterladen', '%n Dateien herunterladen']
			});
			checkPlurals();
		});
		it('generates plural with generated function when forms is specified', function() {
			OC.L10N.register(TEST_APP, {
				'_download %n file_::_download %n files_':
					['%n Datei herunterladen', '%n Dateien herunterladen']
			}, 'nplurals=2; plural=(n != 1);');
			checkPlurals();
		});
		it('generates plural with function when forms is specified as function', function() {
			OC.L10N.register(TEST_APP, {
				'_download %n file_::_download %n files_':
					['%n Datei herunterladen', '%n Dateien herunterladen']
			}, function(n) {
				return {
					nplurals: 2,
					plural: (n !== 1) ? 1 : 0
				};
			});
			checkPlurals();
		});
	});
});
