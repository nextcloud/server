/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

describe('OCA.Files.Files tests', function() {
	var Files = OCA.Files.Files;

	describe('File name validation', function() {
		it('Validates correct file names', function() {
			var fileNames = [
				'boringname',
				'something.with.extension',
				'now with spaces',
				'.a',
				'..a',
				'.dotfile',
				'single\'quote',
				'  spaces before',
				'spaces after   ',
				'allowed chars including the crazy ones $%&_-^@!,()[]{}=;#',
				'汉字也能用',
				'und Ümläüte sind auch willkommen'
			];
			for ( var i = 0; i < fileNames.length; i++ ) {
				var error = false;
				try {
					expect(Files.isFileNameValid(fileNames[i])).toEqual(true);
				}
				catch (e) {
					error = e;
				}
				expect(error).toEqual(false);
			}
		});
		it('Detects invalid file names', function() {
			var fileNames = [
				'',
				'     ',
				'.',
				'..',
				' ..',
				'.. ',
				'. ',
				' .',
				'foo.part',
				'bar.filepart'
			];
			for ( var i = 0; i < fileNames.length; i++ ) {
				var threwException = false;
				try {
					Files.isFileNameValid(fileNames[i]);
					console.error('Invalid file name not detected:', fileNames[i]);
				}
				catch (e) {
					threwException = true;
				}
				expect(threwException).toEqual(true);
			}
		});
	});
	describe('getDownloadUrl', function() {
		it('returns the ajax download URL when filename and dir specified', function() {
			var url = Files.getDownloadUrl('test file.txt', '/subdir');
			expect(url).toEqual(OC.getRootPath() + '/remote.php/webdav/subdir/test%20file.txt');
		});
		it('returns the webdav download URL when filename and root dir specified', function() {
			var url = Files.getDownloadUrl('test file.txt', '/');
			expect(url).toEqual(OC.getRootPath() + '/remote.php/webdav/test%20file.txt');
		});
		it('returns the ajax download URL when multiple files specified', function() {
			var url = Files.getDownloadUrl(['test file.txt', 'abc.txt'], '/subdir');
			expect(url).toEqual(OC.getRootPath() + '/index.php/apps/files/ajax/download.php?dir=%2Fsubdir&files=%5B%22test%20file.txt%22%2C%22abc.txt%22%5D');
		});
	});
	describe('handleDownload', function() {
		var redirectStub;
		var cookieStub;
		var clock;
		var testUrl;

		beforeEach(function() {
			testUrl = 'http://example.com/owncloud/path/download.php';
			redirectStub = sinon.stub(OC, 'redirect');
			cookieStub = sinon.stub(OC.Util, 'isCookieSetToValue');
			clock = sinon.useFakeTimers();
		});
		afterEach(function() {
			redirectStub.restore();
			cookieStub.restore();
			clock.restore();
		});

		it('appends secret to url when no existing parameters', function() {
			Files.handleDownload(testUrl);
			expect(redirectStub.calledOnce).toEqual(true);
			expect(redirectStub.getCall(0).args[0]).toContain(testUrl + '?downloadStartSecret=');
		});
		it('appends secret to url with existing parameters', function() {
			Files.handleDownload(testUrl + '?test=1');
			expect(redirectStub.calledOnce).toEqual(true);
			expect(redirectStub.getCall(0).args[0]).toContain(testUrl + '?test=1&downloadStartSecret=');
		});
		it('sets cookie and calls callback when cookie appears', function() {
			var callbackStub = sinon.stub();
			var token;
			Files.handleDownload(testUrl, callbackStub);
			expect(redirectStub.calledOnce).toEqual(true);
			token = OC.parseQueryString(redirectStub.getCall(0).args[0]).downloadStartSecret;
			expect(token).toBeDefined();

			expect(cookieStub.calledOnce).toEqual(true);
			cookieStub.returns(false);
			clock.tick(600);

			expect(cookieStub.calledTwice).toEqual(true);
			expect(cookieStub.getCall(1).args[0]).toEqual('ocDownloadStarted');
			expect(cookieStub.getCall(1).args[1]).toEqual(token);
			expect(callbackStub.notCalled).toEqual(true);

			cookieStub.returns(true);
			clock.tick(2000);

			expect(cookieStub.callCount).toEqual(3);
			expect(callbackStub.calledOnce).toEqual(true);
		});
	});
});
