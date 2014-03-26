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
describe('OC.Upload tests', function() {
	var $dummyUploader;
	var testFile;

	beforeEach(function() {
		testFile = {
			name: 'test.txt',
			size: 5000, // 5 KB
			type: 'text/plain',
			lastModifiedDate: new Date()
		};
		// need a dummy button because file-upload checks on it
		$('#testArea').append(
			'<input type="file" id="file_upload_start" name="files[]" multiple="multiple">' +
			'<input type="hidden" id="upload_limit" name="upload_limit" value="10000000">' + // 10 MB
			'<input type="hidden" id="free_space" name="free_space" value="50000000">' // 50 MB
		);
		$dummyUploader = $('#file_upload_start');
	});
	afterEach(function() {
		delete window.file_upload_param;
		$dummyUploader = undefined;
	});
	describe('Adding files for upload', function() {
		var params;
		var failStub;

		beforeEach(function() {
			params = OC.Upload.init();
			failStub = sinon.stub();
			$dummyUploader.on('fileuploadfail', failStub);
		});
		afterEach(function() {
			params = undefined;
			failStub = undefined;
		});

		/**
		 * Add file for upload
		 * @param file file data
		 */
		function addFile(file) {
			return params.add.call(
					$dummyUploader[0],
					{},
					{
					originalFiles: {},
					files: [file]
				});
		}

		it('adds file when size is below limits', function() {
			var result = addFile(testFile);
			expect(result).toEqual(true);
		});
		it('adds file when free space is unknown', function() {
			var result;
			$('#free_space').val(-2);

			result = addFile(testFile);

			expect(result).toEqual(true);
			expect(failStub.notCalled).toEqual(true);
		});
		it('does not add file if it exceeds upload limit', function() {
			var result;
			$('#upload_limit').val(1000);

			result = addFile(testFile);

			expect(result).toEqual(false);
			expect(failStub.calledOnce).toEqual(true);
			expect(failStub.getCall(0).args[1].textStatus).toEqual('sizeexceedlimit');
			expect(failStub.getCall(0).args[1].errorThrown).toEqual(
				'Total file size 5 kB exceeds upload limit 1000 B'
			);
		});
		it('does not add file if it exceeds free space', function() {
			var result;
			$('#free_space').val(1000);

			result = addFile(testFile);

			expect(result).toEqual(false);
			expect(failStub.calledOnce).toEqual(true);
			expect(failStub.getCall(0).args[1].textStatus).toEqual('notenoughspace');
			expect(failStub.getCall(0).args[1].errorThrown).toEqual(
				'Not enough free space, you are uploading 5 kB but only 1000 B is left'
			);
		});
		it('does not add file if it has invalid characters', function() {
			var result;
			testFile.name = 'stars*stars.txt';

			result = addFile(testFile);

			expect(result).toEqual(false);
			expect(failStub.calledOnce).toEqual(true);
			expect(failStub.getCall(0).args[1].textStatus).toEqual('invalidcharacters');
			expect(failStub.getCall(0).args[1].errorThrown.substr(0, 12)).toEqual(
				'Invalid name'
			);
		});
	});
});
