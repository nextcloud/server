/**
* ownCloud
*
* @author Vincent Petry
* @copyright 2015 Vincent Petry <pvince81@owncloud.com>
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

describe('OCA.Files.MainFileInfoDetailView tests', function() {
	var view, tipsyStub, previewStub, fncLazyLoadPreview, fileListMock;

	beforeEach(function() {
		tipsyStub = sinon.stub($.fn, 'tipsy');
		fileListMock = sinon.mock(OCA.Files.FileList.prototype);
		view = new OCA.Files.MainFileInfoDetailView();
	});
	afterEach(function() {
		view.destroy();
		view = undefined;
		tipsyStub.restore();
		fileListMock.restore();

	});
	describe('rendering', function() {
		var testFileInfo;
		beforeEach(function() {
			view = new OCA.Files.MainFileInfoDetailView();
			testFileInfo = {
				id: 5,
				name: 'One.txt',
				path: '/subdir',
				size: 123456789,
				dateLabel: new Date(Date.UTC(2015, 6, 17, 1, 2, 3, 4))
			};
		});
		it('displays basic info', function() {
			var clock = sinon.useFakeTimers(Date.UTC(2015, 6, 17, 1, 2, 0, 0));
			view.setFileInfo(testFileInfo);
			expect(view.$el.find('.fileName').text()).toEqual('One.txt');
			expect(view.$el.find('.fileName').attr('title')).toEqual('One.txt');
			expect(view.$el.find('.size').text()).toEqual('117.7 MB');
			expect(view.$el.find('.size').attr('title')).toEqual('123456789 bytes');
			expect(view.$el.find('.date').text()).toEqual('a few seconds ago');
			expect(view.$el.find('.date').attr('title')).toEqual('July 17, 2015 3:02 AM');
			clock.restore();
		});
		it('displays favorite icon', function() {
			view.setFileInfo(_.extend(testFileInfo, {
				tags: [OC.TAG_FAVORITE]
			}));
			expect(view.$el.find('.favorite img').attr('src'))
				.toEqual(OC.imagePath('core', 'actions/starred'));

			view.setFileInfo(_.extend(testFileInfo, {
				tags: []
			}));
			expect(view.$el.find('.favorite img').attr('src'))
				.toEqual(OC.imagePath('core', 'actions/star'));
		});
		it('displays mime icon', function() {
			// File
			view.setFileInfo(_.extend(testFileInfo, {
				mimetype: 'text/calendar'
			}));

			expect(view.$el.find('.thumbnail').css('background-image'))
				.toContain('filetypes/text-calendar.svg');

			// Folder
			view.setFileInfo(_.extend(testFileInfo, {
				mimetype: 'httpd/unix-directory'
			}));

			expect(view.$el.find('.thumbnail').css('background-image'))
				.toContain('filetypes/folder.svg');
		});
		it('displays thumbnail', function() {
			view.setFileInfo(_.extend(testFileInfo, {
				mimetype: 'text/plain'
			}));

			var expectation = fileListMock.expects('lazyLoadPreview');
			expectation.once();

			view.setFileInfo(testFileInfo);

			fileListMock.verify();
		});
	});
});
