/**
* @copyright 2014 Vincent Petry <pvince81@owncloud.com>
 *
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

describe('OCA.Files.FileSummary tests', function() {
	var FileSummary = OCA.Files.FileSummary;
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
		expect($container.find('.dirinfo').text()).toEqual('5 folders');
		expect($container.find('.fileinfo').text()).toEqual('2 files');
		expect($container.find('.filesize').text()).toEqual('256 KB');
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
		expect($container.find('.dirinfo').text()).toEqual('6 folders');
		expect($container.find('.fileinfo').text()).toEqual('3 files');
		expect($container.find('.filesize').text()).toEqual('512 KB');
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
		expect($container.find('.dirinfo').text()).toEqual('4 folders');
		expect($container.find('.fileinfo').text()).toEqual('1 file');
		expect($container.find('.filesize').text()).toEqual('128 KB');
		expect(s.summary.totalDirs).toEqual(4);
		expect(s.summary.totalFiles).toEqual(1);
		expect(s.summary.totalSize).toEqual(127900);
	});

	it('renders filtered summary as text', function() {
		var s = new FileSummary($container);
		s.setSummary({
			totalDirs: 5,
			totalFiles: 2,
			totalSize: 256000,
			filter: 'foo'
		});
		expect($container.hasClass('hidden')).toEqual(false);
		expect($container.find('.dirinfo').text()).toEqual('5 folders');
		expect($container.find('.fileinfo').text()).toEqual('2 files');
		expect($container.find('.filter').text()).toEqual(' match "foo"');
		expect($container.find('.filesize').text()).toEqual('256 KB');
	});
	it('hides filtered summary when no files or folders', function() {
		var s = new FileSummary($container);
		s.setSummary({
			totalDirs: 0,
			totalFiles: 0,
			totalSize: 0,
			filter: 'foo'
		});
		expect($container.hasClass('hidden')).toEqual(true);
	});
	it('increases filtered summary when adding files', function() {
		var s = new FileSummary($container);
		s.setSummary({
			totalDirs: 5,
			totalFiles: 2,
			totalSize: 256000,
			filter: 'foo'
		});
		s.add({name: 'bar.txt', type: 'file', size: 256000});
		s.add({name: 'foo.txt', type: 'file', size: 256001});
		s.add({name: 'bar', type: 'dir', size: 100});
		s.add({name: 'foo', type: 'dir', size: 102});
		s.update();
		expect($container.hasClass('hidden')).toEqual(false);
		expect($container.find('.dirinfo').text()).toEqual('6 folders');
		expect($container.find('.fileinfo').text()).toEqual('3 files');
		expect($container.find('.filter').text()).toEqual(' match "foo"');
		expect($container.find('.filesize').text()).toEqual('512 KB');
		expect(s.summary.totalDirs).toEqual(6);
		expect(s.summary.totalFiles).toEqual(3);
		expect(s.summary.totalSize).toEqual(512103);
	});
	it('decreases filtered summary when removing files', function() {
		var s = new FileSummary($container);
		s.setSummary({
			totalDirs: 5,
			totalFiles: 2,
			totalSize: 256000,
			filter: 'foo'
		});
		s.remove({name: 'bar.txt', type: 'file', size: 128000});
		s.remove({name: 'foo.txt', type: 'file', size: 127999});
		s.remove({name: 'bar', type: 'dir', size: 100});
		s.remove({name: 'foo', type: 'dir', size: 98});
		s.update();
		expect($container.hasClass('hidden')).toEqual(false);
		expect($container.find('.dirinfo').text()).toEqual('4 folders');
		expect($container.find('.fileinfo').text()).toEqual('1 file');
		expect($container.find('.filter').text()).toEqual(' match "foo"');
		expect($container.find('.filesize').text()).toEqual('128 KB');
		expect(s.summary.totalDirs).toEqual(4);
		expect(s.summary.totalFiles).toEqual(1);
		expect(s.summary.totalSize).toEqual(127903);
	});
	it('properly sum up pending folder sizes after adding', function() {
		var s = new FileSummary($container);
		s.setSummary({
			totalDirs: 0,
			totalFiles: 0,
			totalSize: 0
		});
		s.add({type: 'dir', size: -1});
		s.update();
		expect($container.hasClass('hidden')).toEqual(false);
		expect($container.find('.dirinfo').text()).toEqual('1 folder');
		expect($container.find('.fileinfo').hasClass('hidden')).toEqual(true);
		expect($container.find('.filesize').text()).toEqual('Pending');
		expect(s.summary.totalDirs).toEqual(1);
		expect(s.summary.totalFiles).toEqual(0);
		expect(s.summary.totalSize).toEqual(0);
	});
	it('properly sum up pending folder sizes after remove', function() {
		var s = new FileSummary($container);
		s.setSummary({
			totalDirs: 0,
			totalFiles: 0,
			totalSize: 0
		});
		s.add({type: 'dir', size: -1});
		s.remove({type: 'dir', size: -1});
		s.update();
		expect($container.hasClass('hidden')).toEqual(true);
		expect(s.summary.totalDirs).toEqual(0);
		expect(s.summary.totalFiles).toEqual(0);
		expect(s.summary.totalSize).toEqual(0);
	});
	describe('hidden files', function() {
		var config;
		var summary;

		beforeEach(function() {
			config = new OC.Backbone.Model();
			summary = new FileSummary($container, {
				config: config
			});
		});

		it('renders hidden count section when hidden files are hidden', function() {
			window._nc_event_bus.emit('files:config:updated', { key: 'show_hidden', value: false });

			summary.add({name: 'abc', type: 'file', size: 256000});
			summary.add({name: 'def', type: 'dir', size: 100});
			summary.add({name: '.hidden', type: 'dir', size: 512000});
			summary.update();
			expect($container.hasClass('hidden')).toEqual(false);
			expect($container.find('.dirinfo').text()).toEqual('2 folders');
			expect($container.find('.fileinfo').text()).toEqual('1 file');
			expect($container.find('.hiddeninfo').hasClass('hidden')).toEqual(false);
			expect($container.find('.hiddeninfo').text()).toEqual(' (including 1 hidden)');
			expect($container.find('.filesize').text()).toEqual('768 KB');
		});
		it('does not render hidden count section when hidden files exist but are visible', function() {
			window._nc_event_bus.emit('files:config:updated', { key: 'show_hidden', value: true });

			summary.add({name: 'abc', type: 'file', size: 256000});
			summary.add({name: 'def', type: 'dir', size: 100});
			summary.add({name: '.hidden', type: 'dir', size: 512000});
			summary.update();
			expect($container.hasClass('hidden')).toEqual(false);
			expect($container.find('.dirinfo').text()).toEqual('2 folders');
			expect($container.find('.fileinfo').text()).toEqual('1 file');
			expect($container.find('.hiddeninfo').hasClass('hidden')).toEqual(true);
			expect($container.find('.filesize').text()).toEqual('768 KB');
		});
		it('does not render hidden count section when no hidden files exist', function() {
			window._nc_event_bus.emit('files:config:updated', { key: 'show_hidden', value: false });

			summary.add({name: 'abc', type: 'file', size: 256000});
			summary.add({name: 'def', type: 'dir', size: 100});
			summary.update();
			expect($container.hasClass('hidden')).toEqual(false);
			expect($container.find('.dirinfo').text()).toEqual('1 folder');
			expect($container.find('.fileinfo').text()).toEqual('1 file');
			expect($container.find('.hiddeninfo').hasClass('hidden')).toEqual(true);
			expect($container.find('.filesize').text()).toEqual('256 KB');
		});
	});
});
