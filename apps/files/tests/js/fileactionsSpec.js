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

/* global OC, FileActions, FileList */
describe('FileActions tests', function() {
	var $filesTable;
	beforeEach(function() {
		// init horrible parameters
		var $body = $('body');
		$body.append('<input type="hidden" id="dir" value="/subdir"></input>');
		$body.append('<input type="hidden" id="permissions" value="31"></input>');
		// dummy files table
		$filesTable = $body.append('<table id="filestable"></table>');
	});
	afterEach(function() {
		$('#dir, #permissions, #filestable').remove();
	});
	it('calling display() sets file actions', function() {
		// note: download_url is actually the link target, not the actual download URL...
		var $tr = FileList.addFile('testName.txt', 1234, new Date(), false, false, {download_url: 'test/download/url'});

		// no actions before call
		expect($tr.find('.action.action-download').length).toEqual(0);
		expect($tr.find('.action.action-rename').length).toEqual(0);
		expect($tr.find('.action.delete').length).toEqual(0);

		FileActions.display($tr.find('td.filename'), true);

		// actions defined after cal
		expect($tr.find('.action.action-download').length).toEqual(1);
		expect($tr.find('.action.action-download').attr('data-action')).toEqual('Download');
		expect($tr.find('.nametext .action.action-rename').length).toEqual(1);
		expect($tr.find('.nametext .action.action-rename').attr('data-action')).toEqual('Rename');
		expect($tr.find('.action.delete').length).toEqual(1);
	});
	it('calling display() twice correctly replaces file actions', function() {
		var $tr = FileList.addFile('testName.txt', 1234, new Date(), false, false, {download_url: 'test/download/url'});

		FileActions.display($tr.find('td.filename'), true);
		FileActions.display($tr.find('td.filename'), true);

		// actions defined after cal
		expect($tr.find('.action.action-download').length).toEqual(1);
		expect($tr.find('.nametext .action.action-rename').length).toEqual(1);
		expect($tr.find('.action.delete').length).toEqual(1);
	});
	it('redirects to download URL when clicking download', function() {
		var redirectStub = sinon.stub(OC, 'redirect');
		// note: download_url is actually the link target, not the actual download URL...
		var $tr = FileList.addFile('test download File.txt', 1234, new Date(), false, false, {download_url: 'test/download/url'});
		FileActions.display($tr.find('td.filename'), true);

		$tr.find('.action-download').click();

		expect(redirectStub.calledOnce).toEqual(true);
		expect(redirectStub.getCall(0).args[0]).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?dir=%2Fsubdir&files=test%20download%20File.txt');
		redirectStub.restore();
	});
	it('deletes file when clicking delete', function() {
		var deleteStub = sinon.stub(FileList, 'do_delete');
		var $tr = FileList.addFile('test delete File.txt', 1234, new Date());
		FileActions.display($tr.find('td.filename'), true);

		$tr.find('.action.delete').click();

		expect(deleteStub.calledOnce).toEqual(true);
		deleteStub.restore();
	});
});
