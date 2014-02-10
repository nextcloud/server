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

/* global OC, FileList */
describe('FileList tests', function() {
	beforeEach(function() {
		// init horrible parameters
		var $body = $('body');
		$body.append('<input type="hidden" id="dir" value="/subdir"></input>');
		$body.append('<input type="hidden" id="permissions" value="31"></input>');
		// dummy files table
		$body.append('<table id="filestable"></table>');
	});
	afterEach(function() {
		$('#dir, #permissions, #filestable').remove();
	});
	it('generates file element with correct attributes when calling addFile', function() {
		var lastMod = new Date(10000);
		// note: download_url is actually the link target, not the actual download URL...
		var $tr = FileList.addFile('testName.txt', 1234, lastMod, false, false, {download_url: 'test/download/url'});

		expect($tr).toBeDefined();
		expect($tr[0].tagName.toLowerCase()).toEqual('tr');
		expect($tr.find('a:first').attr('href')).toEqual('test/download/url');
		expect($tr.attr('data-type')).toEqual('file');
		expect($tr.attr('data-file')).toEqual('testName.txt');
		expect($tr.attr('data-size')).toEqual('1234');
		expect($tr.attr('data-permissions')).toEqual('31');
		//expect($tr.attr('data-mime')).toEqual('plain/text');
	});
	it('generates dir element with correct attributes when calling addDir', function() {
		var lastMod = new Date(10000);
		var $tr = FileList.addDir('testFolder', 1234, lastMod, false);

		expect($tr).toBeDefined();
		expect($tr[0].tagName.toLowerCase()).toEqual('tr');
		expect($tr.attr('data-type')).toEqual('dir');
		expect($tr.attr('data-file')).toEqual('testFolder');
		expect($tr.attr('data-size')).toEqual('1234');
		expect($tr.attr('data-permissions')).toEqual('31');
		//expect($tr.attr('data-mime')).toEqual('httpd/unix-directory');
	});
	it('returns correct download URL', function() {
		expect(FileList.getDownloadUrl('some file.txt')).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?files=some%20file.txt&dir=%2Fsubdir&download');
		expect(FileList.getDownloadUrl('some file.txt', '/anotherpath/abc')).toEqual(OC.webroot + '/index.php/apps/files/ajax/download.php?files=some%20file.txt&dir=%2Fanotherpath%2Fabc&download');
	});
});
