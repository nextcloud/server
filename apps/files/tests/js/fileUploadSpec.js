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

/* global FileList */

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
			'<input type="hidden" id="free_space" name="free_space" value="50000000">' + // 50 MB
			// TODO: handlebars!
			'<div id="new">' +
			'<a>New</a>' +
			'<ul>' +
			'<li data-type="file" data-newname="New text file.txt"><p>Text file</p></li>' +
			'</ul>' +
			'</div>'
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
				'Total file size 5 KB exceeds upload limit 1000 B'
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
				'Not enough free space, you are uploading 5 KB but only 1000 B is left'
			);
		});
	});
	describe('Upload conflicts', function() {
		var oldFileList;
		var conflictDialogStub;
		var callbacks;

		beforeEach(function() {
			oldFileList = FileList;
			$('#testArea').append(
				'<div id="tableContainer">' +
				'<table id="filestable">' +
				'<thead><tr>' +
				'<th id="headerName" class="hidden column-name">' +
				'<input type="checkbox" id="select_all_files" class="select-all">' +
				'<a class="name columntitle" data-sort="name"><span>Name</span><span class="sort-indicator"></span></a>' +
				'<span id="selectedActionsList" class="selectedActions hidden">' +
				'<a href class="download"><img src="actions/download.svg">Download</a>' +
				'<a href class="delete-selected">Delete</a></span>' +
				'</th>' +
				'<th class="hidden column-size"><a class="columntitle" data-sort="size"><span class="sort-indicator"></span></a></th>' +
				'<th class="hidden column-mtime"><a class="columntitle" data-sort="mtime"><span class="sort-indicator"></span></a></th>' +
				'</tr></thead>' +
				'<tbody id="fileList"></tbody>' +
				'<tfoot></tfoot>' +
				'</table>' +
				'</div>'
			);
			FileList = new OCA.Files.FileList($('#tableContainer'));

			FileList.add({name: 'conflict.txt', mimetype: 'text/plain'});
			FileList.add({name: 'conflict2.txt', mimetype: 'text/plain'});

			conflictDialogStub = sinon.stub(OC.dialogs, 'fileexists');
			callbacks = {
				onNoConflicts: sinon.stub()
			};
		});
		afterEach(function() {
			conflictDialogStub.restore();

			FileList.destroy();
			FileList = oldFileList;
		});
		it('does not show conflict dialog when no client side conflict', function() {
			var selection = {
				// yes, the format of uploads is weird...
				uploads: [
					{files: [{name: 'noconflict.txt'}]},
					{files: [{name: 'noconflict2.txt'}]}
				]
			};

			OC.Upload.checkExistingFiles(selection, callbacks);

			expect(conflictDialogStub.notCalled).toEqual(true);
			expect(callbacks.onNoConflicts.calledOnce).toEqual(true);
			expect(callbacks.onNoConflicts.calledWith(selection)).toEqual(true);
		});
		it('shows conflict dialog when no client side conflict', function() {
			var selection = {
				// yes, the format of uploads is weird...
				uploads: [
					{files: [{name: 'conflict.txt'}]},
					{files: [{name: 'conflict2.txt'}]},
					{files: [{name: 'noconflict.txt'}]}
				]
			};

			var deferred = $.Deferred();
			conflictDialogStub.returns(deferred.promise());
			deferred.resolve();

			OC.Upload.checkExistingFiles(selection, callbacks);

			expect(conflictDialogStub.callCount).toEqual(3);
			expect(conflictDialogStub.getCall(1).args[0])
				.toEqual({files: [ { name: 'conflict.txt' } ]});
			expect(conflictDialogStub.getCall(1).args[1])
				.toEqual({ name: 'conflict.txt', mimetype: 'text/plain', directory: '/' });
			expect(conflictDialogStub.getCall(1).args[2]).toEqual({ name: 'conflict.txt' });

			// yes, the dialog must be called several times...
			expect(conflictDialogStub.getCall(2).args[0]).toEqual({
				files: [ { name: 'conflict2.txt' } ]
			});
			expect(conflictDialogStub.getCall(2).args[1])
				.toEqual({ name: 'conflict2.txt', mimetype: 'text/plain', directory: '/' });
			expect(conflictDialogStub.getCall(2).args[2]).toEqual({ name: 'conflict2.txt' });

			expect(callbacks.onNoConflicts.calledOnce).toEqual(true);
			expect(callbacks.onNoConflicts.calledWith({
				uploads: [
					{files: [{name: 'noconflict.txt'}]}
				]
			})).toEqual(true);
		});
	});
});
