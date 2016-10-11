/**
 * Copyright (c) 2015 Roeland Jago Douma <roeland@famdouma.nl>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

describe('jquery.avatar tests', function() {
	
	var $div;
	var devicePixelRatio

	beforeEach(function() {
		$('#testArea').append($('<div id="avatardiv">'));
		$div = $('#avatardiv');

		devicePixelRatio = window.devicePixelRatio;
		window.devicePixelRatio = 1;
	});

	afterEach(function() {
		$div.remove();

		window.devicePixelRatio = devicePixelRatio
	});

	describe('size', function() {
		it('undefined', function() {
			$div.avatar('foo');

			expect($div.height()).toEqual(64);
			expect($div.width()).toEqual(64);
		});

		it('undefined but div has height', function() {
			$div.height(9);
			$div.avatar('foo');

			expect($div.height()).toEqual(9);
			expect($div.width()).toEqual(9);
		});

		it('undefined but data size is set', function() {
			$div.data('size', 10);
			$div.avatar('foo');

			expect($div.height()).toEqual(10);
			expect($div.width()).toEqual(10);
		});


		it('defined', function() {
			$div.avatar('foo', 8);

			expect($div.height()).toEqual(8);
			expect($div.width()).toEqual(8);
		});
	});

	it('undefined user', function() {
		spyOn($div, 'imageplaceholder');

		$div.avatar();
		
		expect($div.imageplaceholder).toHaveBeenCalledWith('x');
	});

	describe('no avatar', function() {
		it('show placeholder for existing user', function() {
			spyOn($div, 'imageplaceholder');
			$div.avatar('foo');

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({
					data: {displayname: 'bar'}
				})
			);

			expect($div.imageplaceholder).toHaveBeenCalledWith('foo', 'bar');
		});

		it('show placeholder for non existing user', function() {
			spyOn($div, 'imageplaceholder');
			$div.avatar('foo');

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({
					data: {}
				})
			);

			expect($div.imageplaceholder).toHaveBeenCalledWith('foo', 'X');
		});

		it('show no placeholder', function() {
			spyOn($div, 'imageplaceholder');
			$div.avatar('foo', undefined, undefined, true);

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'application/json' },
				JSON.stringify({
					data: {}
				})
			);

			expect($div.imageplaceholder.calls.any()).toEqual(false);
			expect($div.css('display')).toEqual('none');
		});
	});

	describe('url generation', function() {
		beforeEach(function() {
			window.devicePixelRatio = 1;
		});

		it('default', function() {
			window.devicePixelRatio = 1;
			$div.avatar('foo', 32);

			expect(fakeServer.requests[0].method).toEqual('GET');
			expect(fakeServer.requests[0].url).toEqual('http://localhost/index.php/avatar/foo/32');
		});

		it('high DPI icon', function() {
			window.devicePixelRatio = 4;
			$div.avatar('foo', 32);

			expect(fakeServer.requests[0].method).toEqual('GET');
			expect(fakeServer.requests[0].url).toEqual('http://localhost/index.php/avatar/foo/128');
		});

		it('high DPI icon round up size', function() {
			window.devicePixelRatio = 1.9;
			$div.avatar('foo', 32);

			expect(fakeServer.requests[0].method).toEqual('GET');
			expect(fakeServer.requests[0].url).toEqual('http://localhost/index.php/avatar/foo/61');
		});
	});

	describe('valid avatar', function() {
		beforeEach(function() {
			window.devicePixelRatio = 1;
		});

		it('default (no ie8 fix)', function() {
			$div.avatar('foo', 32);

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'image/jpeg' },
				''
			);

			var img = $div.children('img')[0];

			expect(img.height).toEqual(32);
			expect(img.width).toEqual(32);
			expect(img.src).toEqual('http://localhost/index.php/avatar/foo/32');
		});

		it('default high DPI icon', function() {
			window.devicePixelRatio = 1.9;

			$div.avatar('foo', 32);

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'image/jpeg' },
				''
			);

			var img = $div.children('img')[0];

			expect(img.height).toEqual(32);
			expect(img.width).toEqual(32);
			expect(img.src).toEqual('http://localhost/index.php/avatar/foo/61');
		});

		it('with ie8 fix', function() {
			sinon.stub(Math, 'random', function() {
				return 0.5;
			});

			$div.avatar('foo', 32, true);

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'image/jpeg' },
				''
			);

			var img = $div.children('img')[0];

			expect(img.height).toEqual(32);
			expect(img.width).toEqual(32);
			expect(img.src).toEqual('http://localhost/index.php/avatar/foo/32#500');
		});

		it('unhide div', function() {
			$div.hide();

			$div.avatar('foo', 32);

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'image/jpeg' },
				''
			);

			expect($div.css('display')).toEqual('block');
		});

		it('callback called', function() {
			var observer = {callback: function() { dump("FOO"); }};

			spyOn(observer, 'callback');

			$div.avatar('foo', 32, undefined, undefined, function() {
				observer.callback();
			});

			fakeServer.requests[0].respond(
				200,
				{ 'Content-Type': 'image/jpeg' },
				''
			);

			expect(observer.callback).toHaveBeenCalled();
		});
	});
});
