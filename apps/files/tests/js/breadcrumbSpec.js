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

describe('OCA.Files.BreadCrumb tests', function() {
	var BreadCrumb = OCA.Files.BreadCrumb;

	describe('Rendering', function() {
		var bc;
		beforeEach(function() {
			bc = new BreadCrumb({
				getCrumbUrl: function(part, index) {
					// for testing purposes
					return part.dir + '#' + index;
				}
			});
		});
		afterEach(function() {
			bc = null;
		});
		it('Renders its own container', function() {
			bc.render();
			expect(bc.$el.hasClass('breadcrumb')).toEqual(true);
		});
		it('Renders root by default', function() {
			var $crumbs;
			bc.render();
			$crumbs = bc.$el.find('.crumb');
			// menu and home
			expect($crumbs.length).toEqual(2);
			expect($crumbs.eq(0).find('a').hasClass('icon-more')).toEqual(true);
			expect($crumbs.eq(0).find('div.popovermenu').length).toEqual(1);
			expect($crumbs.eq(0).data('dir')).not.toBeDefined();
			expect($crumbs.eq(1).find('a').attr('href')).toEqual('/#1');
			expect($crumbs.eq(1).find('a').hasClass('icon-home')).toEqual(true);
			expect($crumbs.eq(1).data('dir')).toEqual('/');
		});
		it('Renders root when switching to root', function() {
			var $crumbs;
			bc.setDirectory('/somedir');
			bc.setDirectory('/');
			$crumbs = bc.$el.find('.crumb');
			expect($crumbs.length).toEqual(2);
			expect($crumbs.eq(1).data('dir')).toEqual('/');
		});
		it('Renders single path section', function() {
			var $crumbs;
			bc.setDirectory('/somedir');
			$crumbs = bc.$el.find('.crumb');
			expect($crumbs.length).toEqual(3);
			expect($crumbs.eq(0).find('a').hasClass('icon-more')).toEqual(true);
			expect($crumbs.eq(0).find('div.popovermenu').length).toEqual(1);
			expect($crumbs.eq(0).data('dir')).not.toBeDefined();

			expect($crumbs.eq(1).find('a').attr('href')).toEqual('/#1');
			expect($crumbs.eq(1).find('a').hasClass('icon-home')).toEqual(true);
			expect($crumbs.eq(1).data('dir')).toEqual('/');

			expect($crumbs.eq(2).find('a').attr('href')).toEqual('/somedir#2');
			expect($crumbs.eq(2).find('img').length).toEqual(0);
			expect($crumbs.eq(2).data('dir')).toEqual('/somedir');
		});
		it('Renders multiple path sections and special chars', function() {
			var $crumbs;
			bc.setDirectory('/somedir/with space/abc');
			$crumbs = bc.$el.find('.crumb');
			expect($crumbs.length).toEqual(5);
			expect($crumbs.eq(0).find('a').hasClass('icon-more')).toEqual(true);
			expect($crumbs.eq(0).find('div.popovermenu').length).toEqual(1);
			expect($crumbs.eq(0).data('dir')).not.toBeDefined();

			expect($crumbs.eq(1).find('a').attr('href')).toEqual('/#1');
			expect($crumbs.eq(1).find('a').hasClass('icon-home')).toEqual(true);
			expect($crumbs.eq(1).data('dir')).toEqual('/');

			expect($crumbs.eq(2).find('a').attr('href')).toEqual('/somedir#2');
			expect($crumbs.eq(2).find('img').length).toEqual(0);
			expect($crumbs.eq(2).data('dir')).toEqual('/somedir');

			expect($crumbs.eq(3).find('a').attr('href')).toEqual('/somedir/with space#3');
			expect($crumbs.eq(3).find('img').length).toEqual(0);
			expect($crumbs.eq(3).data('dir')).toEqual('/somedir/with space');

			expect($crumbs.eq(4).find('a').attr('href')).toEqual('/somedir/with space/abc#4');
			expect($crumbs.eq(4).find('img').length).toEqual(0);
			expect($crumbs.eq(4).data('dir')).toEqual('/somedir/with space/abc');
		});
		it('Renders backslashes as regular directory separator', function() {
			var $crumbs;
			bc.setDirectory('/somedir\\with/mixed\\separators');
			$crumbs = bc.$el.find('.crumb');
			expect($crumbs.length).toEqual(6);
			expect($crumbs.eq(0).find('a').hasClass('icon-more')).toEqual(true);
			expect($crumbs.eq(0).find('div.popovermenu').length).toEqual(1);
			expect($crumbs.eq(0).data('dir')).not.toBeDefined();

			expect($crumbs.eq(1).find('a').attr('href')).toEqual('/#1');
			expect($crumbs.eq(1).find('a').hasClass('icon-home')).toEqual(true);
			expect($crumbs.eq(1).data('dir')).toEqual('/');

			expect($crumbs.eq(2).find('a').attr('href')).toEqual('/somedir#2');
			expect($crumbs.eq(2).find('img').length).toEqual(0);
			expect($crumbs.eq(2).data('dir')).toEqual('/somedir');

			expect($crumbs.eq(3).find('a').attr('href')).toEqual('/somedir/with#3');
			expect($crumbs.eq(3).find('img').length).toEqual(0);
			expect($crumbs.eq(3).data('dir')).toEqual('/somedir/with');

			expect($crumbs.eq(4).find('a').attr('href')).toEqual('/somedir/with/mixed#4');
			expect($crumbs.eq(4).find('img').length).toEqual(0);
			expect($crumbs.eq(4).data('dir')).toEqual('/somedir/with/mixed');

			expect($crumbs.eq(5).find('a').attr('href')).toEqual('/somedir/with/mixed/separators#5');
			expect($crumbs.eq(5).find('img').length).toEqual(0);
			expect($crumbs.eq(5).data('dir')).toEqual('/somedir/with/mixed/separators');
		});
	});
	describe('Events', function() {
		it('Calls onClick handler when clicking on a crumb', function() {
			var handler = sinon.stub();
			var bc = new BreadCrumb({
				onClick: handler
			});
			bc.setDirectory('/one/two/three/four');
			// Click on crumb does not work, only link
			bc.$el.find('.crumb:eq(4)').click();
			expect(handler.calledOnce).toEqual(false);

			handler.reset();
			// Click on crumb link works
			bc.$el.find('.crumb:eq(1) a').click();
			expect(handler.calledOnce).toEqual(true);
			expect(handler.getCall(0).thisValue).toEqual(bc.$el.find('.crumb > a').get(1));
		});
		it('Calls onDrop handler when dropping on a crumb', function() {
			var droppableStub = sinon.stub($.fn, 'droppable');
			var handler = sinon.stub();
			var bc = new BreadCrumb({
				onDrop: handler
			});
			bc.setDirectory('/one/two/three/four');
			expect(droppableStub.calledOnce).toEqual(true);

			expect(droppableStub.getCall(0).args[0].drop).toBeDefined();
			// simulate drop
			droppableStub.getCall(0).args[0].drop({dummy: true});

			expect(handler.calledOnce).toEqual(true);
			expect(handler.getCall(0).args[0]).toEqual({dummy: true});

			droppableStub.restore();
		});
	});

	describe('Menu tests', function() {
		var bc, dummyDir, $crumbmenuLink, $popovermenu;

		beforeEach(function() {
			dummyDir = '/one/two/three/four/five'

			$('div.crumb').each(function(index){
				$(this).css('width', 50);
			});

			bc = new BreadCrumb();
			// append dummy navigation and controls
			// as they are currently used for measurements
			$('#testArea').append(
				'<div id="controls"></div>'
			);
			$('#controls').append(bc.$el);

			// Shrink to show popovermenu
			bc.setMaxWidth(300);

			// triggers resize implicitly
			bc.setDirectory(dummyDir);

			$crumbmenuLink = bc.$el.find('.crumbmenu > a');
			$popovermenu = $crumbmenuLink.next('.popovermenu');
		});
		afterEach(function() {
			bc = null;
		});

		it('Opens and closes the menu on click', function() {
			// Menu exists
			expect($popovermenu.length).toEqual(1);

			// Disable jQuery delay
			jQuery.fx.off = true

			// Click on menu
			$crumbmenuLink.click();
			expect($popovermenu.is(':visible')).toEqual(true);

			// Click on home
			$(document).mouseup();
			expect($popovermenu.is(':visible')).toEqual(false);

			// Change directory and reset elements
			bc.setDirectory('/one/two/three/four/five/six/seven/eight/nine/ten');
			$crumbmenuLink = bc.$el.find('.crumbmenu > a');
			$popovermenu = $crumbmenuLink.next('.popovermenu');

			// Click on menu again
			$crumbmenuLink.click();
			expect($popovermenu.is(':visible')).toEqual(true);

			// Click on home again
			$(document).mouseup();
			expect($popovermenu.is(':visible')).toEqual(false);

		});
		it('Shows only items not in the breadcrumb', function() {
			var hiddenCrumbs = bc.$el.find('.crumb:not(.crumbmenu).hidden');
			expect($popovermenu.find('li:not(.in-breadcrumb)').length).toEqual(hiddenCrumbs.length);
		});
	});

	describe('Resizing', function() {
		var bc, dummyDir, widths;

		beforeEach(function() {
			dummyDir = '/short name/longer name/looooooooooooonger/' +
				'even longer long long long longer long/aaaaaaaaaaaaaaaaaaaaaaaaaaaaaa/last one';

			// using hard-coded widths (pre-measured) to avoid getting different
			// results on different browsers due to font engine differences
			// 51px is default size for menu and home
			widths = [51, 51, 106, 112, 160, 257, 251, 91];

			$('div.crumb').each(function(index){
				$(this).css('width', widths[index]);
			});

			bc = new BreadCrumb();
			// append dummy navigation and controls
			// as they are currently used for measurements
			$('#testArea').append(
				'<div id="controls"></div>'
			);
			$('#controls').append(bc.$el);
		});
		afterEach(function() {
			bc = null;
		});
		it('Hides breadcrumbs to fit max allowed width', function() {
			var $crumbs;

			bc.setMaxWidth(500);

			// triggers resize implicitly
			bc.setDirectory(dummyDir);
			$crumbs = bc.$el.find('.crumb');

			// Menu and home are always visible
			expect($crumbs.eq(0).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(1).hasClass('hidden')).toEqual(false);

			expect($crumbs.eq(2).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(3).hasClass('hidden')).toEqual(true);
			expect($crumbs.eq(4).hasClass('hidden')).toEqual(true);
			expect($crumbs.eq(5).hasClass('hidden')).toEqual(true);
			expect($crumbs.eq(6).hasClass('hidden')).toEqual(true);
			expect($crumbs.eq(7).hasClass('hidden')).toEqual(false);
		});
		it('Updates the breadcrumbs when reducing max allowed width', function() {
			var $crumbs;

			// enough space
			bc.setMaxWidth(1800);
			$crumbs = bc.$el.find('.crumb');

			// Menu is hidden
			expect($crumbs.eq(0).hasClass('hidden')).toEqual(false);

			// triggers resize implicitly
			bc.setDirectory(dummyDir);

			// simulate decrease
			bc.setMaxWidth(950);

			// Menu and home are always visible
			expect($crumbs.eq(0).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(1).hasClass('hidden')).toEqual(false);

			expect($crumbs.eq(2).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(3).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(4).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(5).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(6).hasClass('hidden')).toEqual(false);
			expect($crumbs.eq(7).hasClass('hidden')).toEqual(false);
		});
	});
});
