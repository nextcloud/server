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

/* global oc_appconfig */
describe('OC.Share tests', function() {
	describe('markFileAsShared', function() {
		var $file;
		var tipsyStub;

		beforeEach(function() {
			tipsyStub = sinon.stub($.fn, 'tipsy');
			$file = $('<tr><td class="filename"><div class="thumbnail"></div><span class="name">File name</span></td></tr>');
			$file.find('.filename').append(
				'<span class="fileactions">' +
				'<a href="#" class="action action-share" data-action="Share">' +
				'<img></img><span> Share</span>' +
				'</a>' +
				'</span>'
			);
		});
		afterEach(function() {
			$file = null;
			tipsyStub.restore();
		});
		describe('displaying the share owner', function() {
			function checkOwner(input, output, title) {
				var $action;

				$file.attr('data-share-owner', input);
				OC.Share.markFileAsShared($file);

				$action = $file.find('.action-share>span');
				expect($action.text().trim()).toEqual(output);
				if (_.isString(title)) {
					expect($action.find('.remoteAddress').attr('title')).toEqual(title);
				} else {
					expect($action.find('.remoteAddress').attr('title')).not.toBeDefined();
				}
				expect(tipsyStub.calledOnce).toEqual(true);
				tipsyStub.reset();
			}

			it('displays the local share owner as is', function() {
				checkOwner('User One', 'User One', null);
			});
			it('displays the user name part of a remote share owner', function() {
				checkOwner(
					'User One@someserver.com',
					'User One@…',
					'User One@someserver.com'
				);
				checkOwner(
					'User One@someserver.com/',
					'User One@…',
					'User One@someserver.com'
				);
				checkOwner(
					'User One@someserver.com/root/of/owncloud',
					'User One@…',
					'User One@someserver.com'
				);
			});
			it('displays the user name part with domain of a remote share owner', function() {
				checkOwner(
					'User One@example.com@someserver.com',
					'User One@example.com',
					'User One@example.com@someserver.com'
				);
				checkOwner(
					'User One@example.com@someserver.com/',
					'User One@example.com',
					'User One@example.com@someserver.com'
				);
				checkOwner(
					'User One@example.com@someserver.com/root/of/owncloud',
					'User One@example.com',
					'User One@example.com@someserver.com'
				);
			});
		});

		describe('displaying the folder icon', function() {
			function checkIcon(expectedImage) {
				var imageUrl = OC.TestUtil.getImageUrl($file.find('.filename .thumbnail'));
				expectedIcon = OC.imagePath('core', expectedImage);
				expect(imageUrl).toEqual(expectedIcon);
			}

			it('shows a plain folder icon for non-shared folders', function() {
				$file.attr('data-type', 'dir');
				OC.Share.markFileAsShared($file);

				checkIcon('filetypes/folder');
			});
			it('shows a shared folder icon for folders shared with another user', function() {
				$file.attr('data-type', 'dir');
				OC.Share.markFileAsShared($file, true);

				checkIcon('filetypes/folder-shared');
			});
			it('shows a shared folder icon for folders shared with the current user', function() {
				$file.attr('data-type', 'dir');
				$file.attr('data-share-owner', 'someoneelse');
				OC.Share.markFileAsShared($file);

				checkIcon('filetypes/folder-shared');
			});
			it('shows a link folder icon for folders shared with link', function() {
				$file.attr('data-type', 'dir');
				OC.Share.markFileAsShared($file, false, true);

				checkIcon('filetypes/folder-public');
			});
			it('shows a link folder icon for folders shared with both link and another user', function() {
				$file.attr('data-type', 'dir');
				OC.Share.markFileAsShared($file, true, true);

				checkIcon('filetypes/folder-public');
			});
			it('shows a link folder icon for folders reshared with link', function() {
				$file.attr('data-type', 'dir');
				$file.attr('data-share-owner', 'someoneelse');
				OC.Share.markFileAsShared($file, false, true);

				checkIcon('filetypes/folder-public');
			});
			it('shows external storage icon if external mount point', function() {
				$file.attr('data-type', 'dir');
				$file.attr('data-mountType', 'external');
				OC.Share.markFileAsShared($file, false, false);

				checkIcon('filetypes/folder-external');
			});
		});

		describe('displaying the recipoients', function() {
			function checkRecipients(input, output, title) {
				var $action;

				$file.attr('data-share-recipients', input);
				OC.Share.markFileAsShared($file, true);

				$action = $file.find('.action-share>span');
				expect($action.text().trim()).toEqual(output);
				if (_.isString(title)) {
					expect($action.find('.remoteAddress').attr('title')).toEqual(title);
				} else if (_.isArray(title)) {
					var tooltips = $action.find('.remoteAddress');
					expect(tooltips.length).toEqual(title.length);

					tooltips.each(function(i) {
						expect($(this).attr('title')).toEqual(title[i]);
					});
				} else {
						expect($action.find('.remoteAddress').attr('title')).not.toBeDefined();
				}
				expect(tipsyStub.calledOnce).toEqual(true);
				tipsyStub.reset();
			}

			it('displays the local share owner as is', function() {
				checkRecipients('User One', 'Shared with User One', null);
			});
			it('displays the user name part of a remote recipient', function() {
				checkRecipients(
					'User One@someserver.com',
					'Shared with User One@…',
					'User One@someserver.com'
				);
				checkRecipients(
					'User One@someserver.com/',
					'Shared with User One@…',
					'User One@someserver.com'
				);
				checkRecipients(
					'User One@someserver.com/root/of/owncloud',
					'Shared with User One@…',
					'User One@someserver.com'
				);
			});
			it('displays the user name part with domain of a remote share owner', function() {
				checkRecipients(
					'User One@example.com@someserver.com',
					'Shared with User One@example.com',
					'User One@example.com@someserver.com'
				);
				checkRecipients(
					'User One@example.com@someserver.com/',
					'Shared with User One@example.com',
					'User One@example.com@someserver.com'
				);
				checkRecipients(
					'User One@example.com@someserver.com/root/of/owncloud',
					'Shared with User One@example.com',
					'User One@example.com@someserver.com'
				);
			});
			it('display multiple remote recipients', function() {
				checkRecipients(
					'One@someserver.com, two@otherserver.com',
					'Shared with One@…, two@…',
					['One@someserver.com', 'two@otherserver.com']
				);
				checkRecipients(
					'One@someserver.com/, two@otherserver.com',
					'Shared with One@…, two@…',
					['One@someserver.com', 'two@otherserver.com']
				);
				checkRecipients(
					'One@someserver.com/root/of/owncloud, two@otherserver.com',
					'Shared with One@…, two@…',
					['One@someserver.com', 'two@otherserver.com']
				);
			});
			it('display mixed recipients', function() {
				checkRecipients(
					'One, two@otherserver.com',
					'Shared with One, two@…',
					['two@otherserver.com']
				);
			});
		});
	});
});

