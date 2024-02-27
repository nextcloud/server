/**
* @copyright 2015 Vincent Petry <pvince81@owncloud.com>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Michael Jobst <mjobst+github@tecratech.de>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Raghu Nayyar <hey@raghunayyar.com>
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

describe('OCA.Files.MainFileInfoDetailView tests', function() {
	var view, tooltipStub, fileActions, fileList, testFileInfo;

	beforeEach(function() {
		tooltipStub = sinon.stub($.fn, 'tooltip');
		fileActions = new OCA.Files.FileActions();
		fileList = new OCA.Files.FileList($('<table></table>'), {
			fileActions: fileActions
		});
		view = new OCA.Files.MainFileInfoDetailView({
			fileList: fileList,
			fileActions: fileActions
		});
		testFileInfo = new OCA.Files.FileInfoModel({
			id: 5,
			name: 'One.txt',
			mimetype: 'text/plain',
			permissions: 31,
			path: '/subdir',
			size: 123456789,
			etag: 'abcdefg',
			mtime: Date.UTC(2015, 6, 17, 1, 2, 0, 0)
		});
	});
	afterEach(function() {
		view.remove();
		view = undefined;
		tooltipStub.restore();

	});
	describe('rendering', function() {
		it('displays basic info', function() {
			var clock = sinon.useFakeTimers(Date.UTC(2015, 6, 17, 1, 2, 0, 3));
			var dateExpected = OC.Util.formatDate(Date(Date.UTC(2015, 6, 17, 1, 2, 0, 0)));
			view.setFileInfo(testFileInfo);
			expect(view.$el.find('.fileName h3').text()).toEqual('One.txt');
			expect(view.$el.find('.fileName h3').attr('title')).toEqual('One.txt');
			expect(view.$el.find('.size').text()).toEqual('117.7 MB');
			expect(view.$el.find('.size').attr('title')).toEqual('123456789 bytes');
			expect(view.$el.find('.date').text()).toEqual('seconds ago');
			expect(view.$el.find('.date').attr('title')).toEqual(dateExpected);
			clock.restore();
		});
		it('displays permalink', function() {
			view.setFileInfo(testFileInfo);
			expect(view.$el.find('.permalink').attr('href'))
				.toEqual(OC.getProtocol() + '://' + OC.getHost() + OC.generateUrl('/f/5'));
		});
		it('displays favorite icon', function() {
			fileActions.registerAction({
				name: 'Favorite',
				mime: 'all',
				permissions: OC.PERMISSION_NONE
			});

			testFileInfo.set('tags', [OC.TAG_FAVORITE]);
			view.setFileInfo(testFileInfo);
			expect(view.$el.find('.action-favorite > span').hasClass('icon-starred')).toEqual(true);
			expect(view.$el.find('.action-favorite > span').hasClass('icon-star')).toEqual(false);

			testFileInfo.set('tags', []);
			view.setFileInfo(testFileInfo);
			expect(view.$el.find('.action-favorite > span').hasClass('icon-starred')).toEqual(false);
			expect(view.$el.find('.action-favorite > span').hasClass('icon-star')).toEqual(true);
		});
		it('does not display favorite icon if favorite action is not available', function() {
			testFileInfo.set('tags', [OC.TAG_FAVORITE]);
			view.setFileInfo(testFileInfo);
			expect(view.$el.find('.action-favorite').length).toEqual(0);

			testFileInfo.set('tags', []);
			view.setFileInfo(testFileInfo);
			expect(view.$el.find('.action-favorite').length).toEqual(0);
		});
		it('displays mime icon', function() {
			// File
			var lazyLoadPreviewStub = sinon.stub(fileList, 'lazyLoadPreview');
			testFileInfo.set('mimetype', 'text/calendar');
			view.setFileInfo(testFileInfo);

			expect(lazyLoadPreviewStub.calledOnce).toEqual(true);
			var previewArgs = lazyLoadPreviewStub.getCall(0).args;
			expect(previewArgs[0].mime).toEqual('text/calendar');
			expect(previewArgs[0].path).toEqual('/subdir/One.txt');
			expect(previewArgs[0].etag).toEqual('abcdefg');

			expect(view.$el.find('.thumbnail').hasClass('icon-loading')).toEqual(true);

			// returns mime icon first without img parameter
			previewArgs[0].callback(
				OC.imagePath('core', 'filetypes/text-calendar.svg')
			);

			// still loading
			expect(view.$el.find('.thumbnail').hasClass('icon-loading')).toEqual(true);

			// preview loading failed, no prview
			previewArgs[0].error();

			// loading stopped, the mimetype icon gets displayed
			expect(view.$el.find('.thumbnail').hasClass('icon-loading')).toEqual(false);
			expect(view.$el.find('.thumbnail').css('background-image'))
				.toContain('filetypes/text-calendar.svg');

			// Folder
			testFileInfo.set('mimetype', 'httpd/unix-directory');
			view.setFileInfo(testFileInfo);

			expect(view.$el.find('.thumbnail').css('background-image'))
				.toContain('filetypes/folder.svg');

			lazyLoadPreviewStub.restore();
		});
		it('uses icon from model if present in model', function() {
			var lazyLoadPreviewStub = sinon.stub(fileList, 'lazyLoadPreview');
			testFileInfo.set('mimetype', 'httpd/unix-directory');
			testFileInfo.set('icon', OC.MimeType.getIconUrl('dir-external'));
			view.setFileInfo(testFileInfo);

			expect(lazyLoadPreviewStub.notCalled).toEqual(true);

			expect(view.$el.find('.thumbnail').hasClass('icon-loading')).toEqual(false);
			expect(view.$el.find('.thumbnail').css('background-image'))
				.toContain('filetypes/folder-external.svg');

			lazyLoadPreviewStub.restore();
		});
		it('displays thumbnail', function() {
			var lazyLoadPreviewStub = sinon.stub(fileList, 'lazyLoadPreview');

			testFileInfo.set('mimetype', 'text/plain');
			view.setFileInfo(testFileInfo);

			expect(lazyLoadPreviewStub.calledOnce).toEqual(true);
			var previewArgs = lazyLoadPreviewStub.getCall(0).args;
			expect(previewArgs[0].mime).toEqual('text/plain');
			expect(previewArgs[0].path).toEqual('/subdir/One.txt');
			expect(previewArgs[0].etag).toEqual('abcdefg');

			expect(view.$el.find('.thumbnail').hasClass('icon-loading')).toEqual(true);

			// returns mime icon first without img parameter
			previewArgs[0].callback(
				OC.imagePath('core', 'filetypes/text-plain.svg')
			);

			// still loading
			expect(view.$el.find('.thumbnail').hasClass('icon-loading')).toEqual(true);

			// return an actual (simulated) image
			previewArgs[0].callback(
				'testimage', {
					width: 100,
					height: 200
				}
			);

			// loading stopped, image got displayed
			expect(view.$el.find('.thumbnail').css('background-image'))
				.toContain('testimage');

			expect(view.$el.find('.thumbnail').hasClass('icon-loading')).toEqual(false);

			lazyLoadPreviewStub.restore();
		});
		it('does not show size if no size available', function() {
			testFileInfo.unset('size');
			view.setFileInfo(testFileInfo);

			expect(view.$el.find('.size').length).toEqual(0);
		});
		it('renders displayName instead of name if available', function() {
			testFileInfo.set('displayName', 'hello.txt');
			view.setFileInfo(testFileInfo);

			expect(view.$el.find('.fileName h3').text()).toEqual('hello.txt');
			expect(view.$el.find('.fileName h3').attr('title')).toEqual('hello.txt');
		});
		it('rerenders when changes are made on the model', function() {
			// Show the "Favorite" icon
			fileActions.registerAction({
				name: 'Favorite',
				mime: 'all',
				permissions: OC.PERMISSION_NONE
			});

			view.setFileInfo(testFileInfo);

			testFileInfo.set('tags', [OC.TAG_FAVORITE]);

			expect(view.$el.find('.action-favorite > span').hasClass('icon-starred')).toEqual(true);
			expect(view.$el.find('.action-favorite > span').hasClass('icon-star')).toEqual(false);

			testFileInfo.set('tags', []);

			expect(view.$el.find('.action-favorite > span').hasClass('icon-starred')).toEqual(false);
			expect(view.$el.find('.action-favorite > span').hasClass('icon-star')).toEqual(true);
		});
		it('unbinds change listener from model', function() {
			// Show the "Favorite" icon
			fileActions.registerAction({
				name: 'Favorite',
				mime: 'all',
				permissions: OC.PERMISSION_NONE
			});

			view.setFileInfo(testFileInfo);
			view.setFileInfo(new OCA.Files.FileInfoModel({
				id: 999,
				name: 'test.txt',
				path: '/'
			}));

			// set value on old model
			testFileInfo.set('tags', [OC.TAG_FAVORITE]);

			// no change
			expect(view.$el.find('.action-favorite > span').hasClass('icon-starred')).toEqual(false);
			expect(view.$el.find('.action-favorite > span').hasClass('icon-star')).toEqual(true);
		});
	});
	describe('events', function() {
		it('triggers default action when clicking on the thumbnail', function() {
			var actionHandler = sinon.stub();

			fileActions.registerAction({
				name: 'Something',
				mime: 'all',
				permissions: OC.PERMISSION_READ,
				actionHandler: actionHandler
			});
			fileActions.setDefault('text/plain', 'Something');

			view.setFileInfo(testFileInfo);

			view.$el.find('.thumbnail').click();

			expect(actionHandler.calledOnce).toEqual(true);
			expect(actionHandler.getCall(0).args[0]).toEqual('One.txt');
			expect(actionHandler.getCall(0).args[1].fileList).toEqual(fileList);
			expect(actionHandler.getCall(0).args[1].fileActions).toEqual(fileActions);
			expect(actionHandler.getCall(0).args[1].fileInfoModel).toEqual(testFileInfo);
		});
		it('triggers "Favorite" action when clicking on the star', function() {
			var actionHandler = sinon.stub();

			fileActions.registerAction({
				name: 'Favorite',
				mime: 'all',
				permissions: OC.PERMISSION_READ,
				actionHandler: actionHandler
			});

			view.setFileInfo(testFileInfo);

			view.$el.find('.action-favorite').click();

			expect(actionHandler.calledOnce).toEqual(true);
			expect(actionHandler.getCall(0).args[0]).toEqual('One.txt');
			expect(actionHandler.getCall(0).args[1].fileList).toEqual(fileList);
			expect(actionHandler.getCall(0).args[1].fileActions).toEqual(fileActions);
			expect(actionHandler.getCall(0).args[1].fileInfoModel).toEqual(testFileInfo);
		});
	});
});
