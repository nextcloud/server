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

/* global OC */
describe('Core base tests', function() {
	describe('Base values', function() {
		it('Sets webroots', function() {
			expect(OC.webroot).toBeDefined();
			expect(OC.appswebroots).toBeDefined();
		});
	});
	describe('basename', function() {
		it('Returns the nothing if no file name given', function() {
			expect(OC.basename('')).toEqual('');
		});
		it('Returns the nothing if dir is root', function() {
			expect(OC.basename('/')).toEqual('');
		});
		it('Returns the same name if no path given', function() {
			expect(OC.basename('some name.txt')).toEqual('some name.txt');
		});
		it('Returns the base name if root path given', function() {
			expect(OC.basename('/some name.txt')).toEqual('some name.txt');
		});
		it('Returns the base name if double root path given', function() {
			expect(OC.basename('//some name.txt')).toEqual('some name.txt');
		});
		it('Returns the base name if subdir given without root', function() {
			expect(OC.basename('subdir/some name.txt')).toEqual('some name.txt');
		});
		it('Returns the base name if subdir given with root', function() {
			expect(OC.basename('/subdir/some name.txt')).toEqual('some name.txt');
		});
		it('Returns the base name if subdir given with double root', function() {
			expect(OC.basename('//subdir/some name.txt')).toEqual('some name.txt');
		});
		it('Returns the base name if subdir has dot', function() {
			expect(OC.basename('/subdir.dat/some name.txt')).toEqual('some name.txt');
		});
		it('Returns dot if file name is dot', function() {
			expect(OC.basename('/subdir/.')).toEqual('.');
		});
		// TODO: fix the source to make it work like PHP's basename
		it('Returns the dir itself if no file name given', function() {
			// TODO: fix the source to make it work like PHP's dirname
			// expect(OC.basename('subdir/')).toEqual('subdir');
			expect(OC.basename('subdir/')).toEqual('');
		});
		it('Returns the dir itself if no file name given with root', function() {
			// TODO: fix the source to make it work like PHP's dirname
			// expect(OC.basename('/subdir/')).toEqual('subdir');
			expect(OC.basename('/subdir/')).toEqual('');
		});
	});
	describe('dirname', function() {
		it('Returns the nothing if no file name given', function() {
			expect(OC.dirname('')).toEqual('');
		});
		it('Returns the root if dir is root', function() {
			// TODO: fix the source to make it work like PHP's dirname
			// expect(OC.dirname('/')).toEqual('/');
			expect(OC.dirname('/')).toEqual('');
		});
		it('Returns the root if dir is double root', function() {
			// TODO: fix the source to make it work like PHP's dirname
			// expect(OC.dirname('//')).toEqual('/');
			expect(OC.dirname('//')).toEqual('/'); // oh no...
		});
		it('Returns dot if dir is dot', function() {
			expect(OC.dirname('.')).toEqual('.');
		});
		it('Returns dot if no root given', function() {
			// TODO: fix the source to make it work like PHP's dirname
			// expect(OC.dirname('some dir')).toEqual('.');
			expect(OC.dirname('some dir')).toEqual('some dir'); // oh no...
		});
		it('Returns the dir name if file name and root path given', function() {
			// TODO: fix the source to make it work like PHP's dirname
			// expect(OC.dirname('/some name.txt')).toEqual('/');
			expect(OC.dirname('/some name.txt')).toEqual('');
		});
		it('Returns the dir name if double root path given', function() {
			expect(OC.dirname('//some name.txt')).toEqual('/'); // how lucky...
		});
		it('Returns the dir name if subdir given without root', function() {
			expect(OC.dirname('subdir/some name.txt')).toEqual('subdir');
		});
		it('Returns the dir name if subdir given with root', function() {
			expect(OC.dirname('/subdir/some name.txt')).toEqual('/subdir');
		});
		it('Returns the dir name if subdir given with double root', function() {
			// TODO: fix the source to make it work like PHP's dirname
			// expect(OC.dirname('//subdir/some name.txt')).toEqual('/subdir');
			expect(OC.dirname('//subdir/some name.txt')).toEqual('//subdir'); // oh...
		});
		it('Returns the dir name if subdir has dot', function() {
			expect(OC.dirname('/subdir.dat/some name.txt')).toEqual('/subdir.dat');
		});
		it('Returns the dir name if file name is dot', function() {
			expect(OC.dirname('/subdir/.')).toEqual('/subdir');
		});
		it('Returns the dir name if no file name given', function() {
			expect(OC.dirname('subdir/')).toEqual('subdir');
		});
		it('Returns the dir name if no file name given with root', function() {
			expect(OC.dirname('/subdir/')).toEqual('/subdir');
		});
	});
	describe('Link functions', function() {
		var TESTAPP = 'testapp';
		var TESTAPP_ROOT = OC.webroot + '/appsx/testapp';

		beforeEach(function() {
			OC.appswebroots[TESTAPP] = TESTAPP_ROOT;
		});
		afterEach(function() {
			// restore original array
			delete OC.appswebroots[TESTAPP];
		});
		it('Generates correct links for core apps', function() {
			expect(OC.linkTo('core', 'somefile.php')).toEqual(OC.webroot + '/core/somefile.php');
			expect(OC.linkTo('admin', 'somefile.php')).toEqual(OC.webroot + '/admin/somefile.php');
		});
		it('Generates correct links for regular apps', function() {
			expect(OC.linkTo(TESTAPP, 'somefile.php')).toEqual(OC.webroot + '/index.php/apps/' + TESTAPP + '/somefile.php');
		});
		it('Generates correct remote links', function() {
			expect(OC.linkToRemote('webdav')).toEqual(window.location.protocol + '//' + window.location.host + OC.webroot + '/remote.php/webdav');
		});
		describe('Images', function() {
			it('Generates image path with given extension', function() {
				var svgSupportStub = sinon.stub(window, 'SVGSupport', function() { return true; });
				expect(OC.imagePath('core', 'somefile.jpg')).toEqual(OC.webroot + '/core/img/somefile.jpg');
				expect(OC.imagePath(TESTAPP, 'somefile.jpg')).toEqual(TESTAPP_ROOT + '/img/somefile.jpg');
				svgSupportStub.restore();
			});
			it('Generates image path with svg extension when svg support exists', function() {
				var svgSupportStub = sinon.stub(window, 'SVGSupport', function() { return true; });
				expect(OC.imagePath('core', 'somefile')).toEqual(OC.webroot + '/core/img/somefile.svg');
				expect(OC.imagePath(TESTAPP, 'somefile')).toEqual(TESTAPP_ROOT + '/img/somefile.svg');
				svgSupportStub.restore();
			});
			it('Generates image path with png ext when svg support is not available', function() {
				var svgSupportStub = sinon.stub(window, 'SVGSupport', function() { return false; });
				expect(OC.imagePath('core', 'somefile')).toEqual(OC.webroot + '/core/img/somefile.png');
				expect(OC.imagePath(TESTAPP, 'somefile')).toEqual(TESTAPP_ROOT + '/img/somefile.png');
				svgSupportStub.restore();
			});
		});
	});
	describe('Query string building', function() {
		it('Returns empty string when empty params', function() {
			expect(OC.buildQueryString()).toEqual('');
			expect(OC.buildQueryString({})).toEqual('');
		});
		it('Encodes regular query strings', function() {
			expect(OC.buildQueryString({
				a: 'abc',
				b: 'def'
			})).toEqual('a=abc&b=def');
		});
		it('Encodes special characters', function() {
			expect(OC.buildQueryString({
				unicode: '汉字'
			})).toEqual('unicode=%E6%B1%89%E5%AD%97');
			expect(OC.buildQueryString({
			   	b: 'spaace value',
			   	'space key': 'normalvalue',
			   	'slash/this': 'amp&ersand'
			})).toEqual('b=spaace%20value&space%20key=normalvalue&slash%2Fthis=amp%26ersand');
		});
		it('Encodes data types and empty values', function() {
			expect(OC.buildQueryString({
				'keywithemptystring': '',
			   	'keywithnull': null,
			   	'keywithundefined': null,
				something: 'else'
			})).toEqual('keywithemptystring=&keywithnull&keywithundefined&something=else');
			expect(OC.buildQueryString({
			   	'booleanfalse': false,
				'booleantrue': true
			})).toEqual('booleanfalse=false&booleantrue=true');
			expect(OC.buildQueryString({
			   	'number': 123
			})).toEqual('number=123');
		});
	});
	describe('Session heartbeat', function() {
		var clock,
			oldConfig,
			routeStub,
			counter;

		beforeEach(function() {
			clock = sinon.useFakeTimers();
			oldConfig = window.oc_config;
			routeStub = sinon.stub(OC, 'generateUrl').returns('/heartbeat');
			counter = 0;

			fakeServer.autoRespond = true;
			fakeServer.autoRespondAfter = 0;
			fakeServer.respondWith(/\/heartbeat/, function(xhr) {
				counter++;
				xhr.respond(200, {'Content-Type': 'application/json'}, '{}');
			});
		});
		afterEach(function() {
			clock.restore();
			window.oc_config = oldConfig;
			routeStub.restore();
		});
		it('sends heartbeat half the session lifetime when heartbeat enabled', function() {
			window.oc_config = {
				session_keepalive: true,
				session_lifetime: 300
			};
			window.initCore();
			expect(routeStub.calledWith('/heartbeat')).toEqual(true);

			expect(counter).toEqual(0);

			// less than half, still nothing
			clock.tick(100 * 1000);
			expect(counter).toEqual(0);

			// reach past half (160), one call
			clock.tick(55 * 1000);
			expect(counter).toEqual(1);

			// almost there to the next, still one
			clock.tick(140 * 1000);
			expect(counter).toEqual(1);

			// past it, second call
			clock.tick(20 * 1000);
			expect(counter).toEqual(2);
		});
		it('does no send heartbeat when heartbeat disabled', function() {
			window.oc_config = {
				session_keepalive: false,
				session_lifetime: 300
			};
			window.initCore();
			expect(routeStub.notCalled).toEqual(true);

			expect(counter).toEqual(0);

			clock.tick(1000000);

			// still nothing
			expect(counter).toEqual(0);
		});
	});
	describe('Parse query string', function() {
		it('Parses query string from full URL', function() {
			var query = OC.parseQueryString('http://localhost/stuff.php?q=a&b=x');
			expect(query).toEqual({q: 'a', b: 'x'});
		});
		it('Parses query string from query part alone', function() {
			var query = OC.parseQueryString('q=a&b=x');
			expect(query).toEqual({q: 'a', b: 'x'});
		});
		it('Returns null hash when empty query', function() {
			var query = OC.parseQueryString('');
			expect(query).toEqual(null);
		});
		it('Returns empty hash when empty query with question mark', function() {
			var query = OC.parseQueryString('?');
			expect(query).toEqual({});
		});
		it('Decodes regular query strings', function() {
			var query = OC.parseQueryString('a=abc&b=def');
			expect(query).toEqual({
				a: 'abc',
				b: 'def'
			});
		});
		it('Ignores empty parts', function() {
			var query = OC.parseQueryString('&q=a&&b=x&');
			expect(query).toEqual({q: 'a', b: 'x'});
		});
		it('Ignores lone equal signs', function() {
			var query = OC.parseQueryString('&q=a&=&b=x&');
			expect(query).toEqual({q: 'a', b: 'x'});
		});
		it('Includes extra equal signs in value', function() {
			var query = OC.parseQueryString('u=a=x&q=a=b');
			expect(query).toEqual({u: 'a=x', q: 'a=b'});
		});
		it('Decodes plus as space', function() {
			var query = OC.parseQueryString('space+key=space+value');
			expect(query).toEqual({'space key': 'space value'});
		});
		it('Decodes special characters', function() {
			var query = OC.parseQueryString('unicode=%E6%B1%89%E5%AD%97');
			expect(query).toEqual({unicode: '汉字'});
			query = OC.parseQueryString('b=spaace%20value&space%20key=normalvalue&slash%2Fthis=amp%26ersand');
			expect(query).toEqual({
				b: 'spaace value',
				'space key': 'normalvalue',
				'slash/this': 'amp&ersand'
			});
		});
		it('Decodes empty values', function() {
			var query = OC.parseQueryString('keywithemptystring=&keywithnostring');
			expect(query).toEqual({
				'keywithemptystring': '',
				'keywithnostring': null
			});
		});
		it('Does not interpret data types', function() {
			var query = OC.parseQueryString('booleanfalse=false&booleantrue=true&number=123');
			expect(query).toEqual({
				'booleanfalse': 'false',
				'booleantrue': 'true',
				'number': '123'
			});
		});
	});
	describe('Generate Url', function() {
		it('returns absolute urls', function() {
			expect(OC.generateUrl('heartbeat')).toEqual(OC.webroot + '/index.php/heartbeat');
			expect(OC.generateUrl('/heartbeat')).toEqual(OC.webroot + '/index.php/heartbeat');
		});
		it('substitutes parameters', function() {
			expect(OC.generateUrl('apps/files/download{file}', {file: '/Welcome.txt'})).toEqual(OC.webroot + '/index.php/apps/files/download/Welcome.txt');
		});
	});
	describe('Main menu mobile toggle', function() {
		var oldMatchMedia;
		var $toggle;
		var $navigation;

		beforeEach(function() {
			oldMatchMedia = OC._matchMedia;
			// a separate method was needed because window.matchMedia
			// cannot be stubbed due to a bug in PhantomJS:
			// https://github.com/ariya/phantomjs/issues/12069
			OC._matchMedia = sinon.stub();
			$('#testArea').append('<div id="header">' +
				'<a id="owncloud" href="#"></a>' +
				'</div>' +
				'<div id="navigation"></div>');
			$toggle = $('#owncloud');
			$navigation = $('#navigation');
		});

		afterEach(function() {
			OC._matchMedia = oldMatchMedia;
		});
		it('Sets up menu toggle in mobile mode', function() {
			OC._matchMedia.returns({matches: true});
			window.initCore();
			expect($toggle.hasClass('menutoggle')).toEqual(true);
			expect($navigation.hasClass('menu')).toEqual(true);
		});
		it('Does not set up menu toggle in desktop mode', function() {
			OC._matchMedia.returns({matches: false});
			window.initCore();
			expect($toggle.hasClass('menutoggle')).toEqual(false);
			expect($navigation.hasClass('menu')).toEqual(false);
		});
		it('Switches on menu toggle when mobile mode changes', function() {
			var mq = {matches: false};
			OC._matchMedia.returns(mq);
			window.initCore();
			expect($toggle.hasClass('menutoggle')).toEqual(false);
			mq.matches = true;
			$(window).trigger('resize');
			expect($toggle.hasClass('menutoggle')).toEqual(true);
		});
		it('Switches off menu toggle when mobile mode changes', function() {
			var mq = {matches: true};
			OC._matchMedia.returns(mq);
			window.initCore();
			expect($toggle.hasClass('menutoggle')).toEqual(true);
			mq.matches = false;
			$(window).trigger('resize');
			expect($toggle.hasClass('menutoggle')).toEqual(false);
		});
		it('Clicking menu toggle toggles navigation in mobile mode', function() {
			OC._matchMedia.returns({matches: true});
			window.initCore();
			$navigation.hide(); // normally done through media query triggered CSS
			expect($navigation.is(':visible')).toEqual(false);
			$toggle.click();
			expect($navigation.is(':visible')).toEqual(true);
			$toggle.click();
			expect($navigation.is(':visible')).toEqual(false);
		});
		it('Clicking menu toggle does not toggle navigation in desktop mode', function() {
			OC._matchMedia.returns({matches: false});
			window.initCore();
			expect($navigation.is(':visible')).toEqual(true);
			$toggle.click();
			expect($navigation.is(':visible')).toEqual(true);
		});
		it('Switching to mobile mode hides navigation', function() {
			var mq = {matches: false};
			OC._matchMedia.returns(mq);
			window.initCore();
			expect($navigation.is(':visible')).toEqual(true);
			mq.matches = true;
			$(window).trigger('resize');
			expect($navigation.is(':visible')).toEqual(false);
		});
		it('Switching to desktop mode shows navigation', function() {
			var mq = {matches: true};
			OC._matchMedia.returns(mq);
			window.initCore();
			expect($navigation.is(':visible')).toEqual(false);
			mq.matches = false;
			$(window).trigger('resize');
			expect($navigation.is(':visible')).toEqual(true);
		});
		it('Switch to desktop with opened menu then back to mobile resets toggle', function() {
			var mq = {matches: true};
			OC._matchMedia.returns(mq);
			window.initCore();
			expect($navigation.is(':visible')).toEqual(false);
			$toggle.click();
			expect($navigation.is(':visible')).toEqual(true);
			mq.matches = false;
			$(window).trigger('resize');
			expect($navigation.is(':visible')).toEqual(true);
			mq.matches = true;
			$(window).trigger('resize');
			expect($navigation.is(':visible')).toEqual(false);
			$toggle.click();
			expect($navigation.is(':visible')).toEqual(true);
		});
	});
});

