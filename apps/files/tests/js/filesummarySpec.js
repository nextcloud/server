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

/* global FileSummary */
describe('FileSummary tests', function() {
	var $container;

	beforeEach(function() {
		$container = $('<table><tr></tr></table>').find('tr');
	});
	afterEach(function() {
		$container = null;
	});

	it('renders summary as text', function() {
		var s = new FileSummary($container);
		s.setSummary({
			totalDirs: 5,
			totalFiles: 2,
			totalSize: 256000
		});
		expect($container.hasClass('hidden')).toEqual(false);
		expect($container.find('.info').text()).toEqual('5 folders and 2 files');
		expect($container.find('.filesize').text()).toEqual('250 kB');
	});
	it('hides summary when no files or folders', function() {
		var s = new FileSummary($container);
		s.setSummary({
			totalDirs: 0,
			totalFiles: 0,
			totalSize: 0
		});
		expect($container.hasClass('hidden')).toEqual(true);
	});
	it('increases summary when adding files', function() {
		var s = new FileSummary($container);
		s.setSummary({
			totalDirs: 5,
			totalFiles: 2,
			totalSize: 256000
		});
		s.add({type: 'file', size: 256000});
		s.add({type: 'dir', size: 100});
		s.update();
		expect($container.hasClass('hidden')).toEqual(false);
		expect($container.find('.info').text()).toEqual('6 folders and 3 files');
		expect($container.find('.filesize').text()).toEqual('500 kB');
		expect(s.summary.totalDirs).toEqual(6);
		expect(s.summary.totalFiles).toEqual(3);
		expect(s.summary.totalSize).toEqual(512100);
	});
	it('decreases summary when removing files', function() {
		var s = new FileSummary($container);
		s.setSummary({
			totalDirs: 5,
			totalFiles: 2,
			totalSize: 256000
		});
		s.remove({type: 'file', size: 128000});
		s.remove({type: 'dir', size: 100});
		s.update();
		expect($container.hasClass('hidden')).toEqual(false);
		expect($container.find('.info').text()).toEqual('4 folders and 1 file');
		expect($container.find('.filesize').text()).toEqual('125 kB');
		expect(s.summary.totalDirs).toEqual(4);
		expect(s.summary.totalFiles).toEqual(1);
		expect(s.summary.totalSize).toEqual(127900);
	});
});
