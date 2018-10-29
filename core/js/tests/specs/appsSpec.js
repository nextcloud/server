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

describe('Apps base tests', function() {
	describe('Sidebar utility functions', function() {
		beforeEach(function() {
			$('#testArea').append('<div id="content"><div id="app-content">Content</div><div id="app-sidebar">The sidebar</div></div>');
			jQuery.fx.off = true;
		});
		afterEach(function() {
			jQuery.fx.off = false;
		});
		it('shows sidebar', function() {
			var $el = $('#app-sidebar');
			OC.Apps.showAppSidebar();
			expect($el.hasClass('disappear')).toEqual(false);
		});
		it('hides sidebar', function() {
			var $el = $('#app-sidebar');
			OC.Apps.showAppSidebar();
			OC.Apps.hideAppSidebar();
			expect($el.hasClass('disappear')).toEqual(true);
		});
		it('triggers appresize event when visibility changed', function() {
			var eventStub = sinon.stub();
			$('#content').on('appresized', eventStub);
			OC.Apps.showAppSidebar();
			expect(eventStub.calledOnce).toEqual(true);
			OC.Apps.hideAppSidebar();
			expect(eventStub.calledTwice).toEqual(true);
		});
	});
});

