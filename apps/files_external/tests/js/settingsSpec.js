/*
 * Copyright (c) 2015 Vincent Petry <pvince81@owncloud.com>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

describe('OCA.External.Settings tests', function() {
	var clock;
	var select2Stub;
	var select2ApplicableUsers;

	beforeEach(function() {
		clock = sinon.useFakeTimers();
		select2Stub = sinon.stub($.fn, 'select2', function(args) {
			if (args === 'val') {
				return select2ApplicableUsers;
			}
			return {
				on: function() {}
			};
		});

		// view still requires an existing DOM table
		$('#testArea').append(
			'<table id="externalStorage" data-admin="true">' +
			'<thead></thead>' +
			'<tbody>' +
			'<tr id="addMountPoint" data-id="">' +
			'<td class="status"></td>' +
			'<td class="mountPoint"><input type="text" name="mountPoint"/></td>' +
			'<td class="backend">' +
			'<select class="selectBackend">' +
			'<option disable selected>Add storage</option>' +
			'<option value="\\OC\\TestBackend">Test Backend</option>' +
			'<option value="\\OC\\AnotherTestBackend">Another Test Backend</option>' +
			'</select>' +
			'</td>' +
			'<td class="configuration"></td>' +
			'<td class="applicable">' +
			'<input type="hidden" class="applicableUsers">' +
			'</td>' +
			'<td><img alt="Delete" title="Delete" class="svg action"/></td>' +
			'</tr>' +
			'</tbody>' +
			'</table>'
		);
		// these are usually appended into the data attribute
		// within the DOM by the server template
		$('#externalStorage .selectBackend:first').data('configurations', {
				'\\OC\\TestBackend': {
					'backend': 'Test Backend Name',
					'configuration': {
						'field1': 'Display Name 1',
						'field2': '&Display Name 2'
					}
				},
				'\\OC\\AnotherTestBackend': {
					'backend': 'Another Test Backend Name',
					'configuration': {
						'field1': 'Display Name 1',
						'field2': '&Display Name 2'
					}
				}
			}
		);
	});
	afterEach(function() {
		select2Stub.restore();
		clock.restore();
	});

	describe('storage configuration', function() {
		var view;

		function selectBackend(backendName) {
			view.$el.find('.selectBackend:first').val('\\OC\\TestBackend').trigger('change');
		}

		beforeEach(function() {
			var $el = $('#externalStorage');
			view = new OCA.External.Settings.MountConfigListView($el);
		});
		afterEach(function() {
			view = null;
		});
		describe('selecting backend', function() {
			it('populates the row and creates a new empty one', function() {
				var $firstRow = view.$el.find('tr:first');
				selectBackend('\\OC\\TestBackend');
				expect($firstRow.find('.backend').text()).toEqual('Test Backend');
				expect($firstRow.find('.selectBackend').length).toEqual(0);

				// TODO: check "remove" button visibility

				// the suggested mount point name
				expect($firstRow.find('[name=mountPoint]').val()).toEqual('TestBackend');

				// TODO: check that the options have been created

				// TODO: check select2 call on the ".applicableUsers" element

				var $emptyRow = $firstRow.next('tr');
				expect($emptyRow.length).toEqual(1);
				expect($emptyRow.find('.selectBackend').length).toEqual(1);
				expect($emptyRow.find('.applicable select').length).toEqual(0);

				// TODO: check "remove" button visibility
			});
			// TODO: test with personal mounts (no applicable fields)
			// TODO: test suggested mount point logic
		});
		describe('saving storages', function() {
			it('saves storage after editing config', function() {
				var $tr = view.$el.find('tr:first');
				selectBackend('\\OC\\TestBackend');

				var $field1 = $tr.find('input[data-parameter=field1]');
				expect($field1.length).toEqual(1);
				$field1.val('test');
				$field1.trigger(new $.Event('keyup', {keyCode: 97}));

				clock.tick(4000);

				expect(fakeServer.requests.length).toEqual(1);
				var request = fakeServer.requests[0];
				expect(request.url).toEqual(OC.webroot + '/index.php/apps/files_external/globalstorages');
				expect(OC.parseQueryString(request.requestBody)).toEqual({
					backendClass: '\\OC\\TestBackend',
					'backendOptions[field1]': 'test',
					'backendOptions[field2]': '',
					mountPoint: 'TestBackend'
				});

				// TODO: respond and check data-id
			});
			// TODO: tests with "applicableUsers" and "applicableGroups"
			// TODO: test with non-optional config parameters
			// TODO: test with missing mount point value
			// TODO: test with personal mounts (no applicable fields)
			// TODO: test save triggers: paste, keyup, checkbox
			// TODO: test "custom" field with addScript
			// TODO: status indicator
		});
		describe('update storage', function() {
			// TODO
		});
		describe('delete storage', function() {
			// TODO
		});
		describe('recheck storages', function() {
			// TODO
		});
	});
	describe('applicable user list', function() {
		// TODO: test select2 retrieval logic
	});
	describe('allow user mounts section', function() {
		// TODO: test allowUserMounting section
	});
});
