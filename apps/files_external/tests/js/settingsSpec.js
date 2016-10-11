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
			'<option value="\\OC\\InputsTestBackend">Inputs test backend</option>' +
			'</select>' +
			'</td>' +
			'<td class="authentication"></td>' +
			'<td class="configuration"></td>' +
			'<td class="applicable">' +
			'<input type="hidden" class="applicableUsers">' +
			'</td>' +
			'<td class="mountOptionsToggle"><input type="hidden" class="mountOptions"/><img class="svg action"/></td>' +
			'<td><img alt="Delete" title="Delete" class="svg action"/></td>' +
			'</tr>' +
			'</tbody>' +
			'</table>'
		);
		// these are usually appended into the data attribute
		// within the DOM by the server template
		$('#externalStorage .selectBackend:first').data('configurations', {
				'\\OC\\TestBackend': {
					'identifier': '\\OC\\TestBackend',
					'name': 'Test Backend',
					'configuration': {
						'field1': {
							'value': 'Display Name 1'
						},
						'field2': {
							'value': 'Display Name 2',
							'flags': 1
						}
					},
					'authSchemes': {
						'builtin': true,
					},
					'priority': 11
				},
				'\\OC\\AnotherTestBackend': {
					'identifier': '\\OC\\AnotherTestBackend',
					'name': 'Another Test Backend',
					'configuration': {
						'field1': {
							'value': 'Display Name 1'
						},
						'field2': {
							'value': 'Display Name 2',
							'flags': 1
						}
					},
					'authSchemes': {
						'builtin': true,
					},
					'priority': 12
				},
				'\\OC\\InputsTestBackend': {
					'identifier': '\\OC\\InputsTestBackend',
					'name': 'Inputs test backend',
					'configuration': {
						'field_text': {
							'value': 'Text field'
						},
						'field_password': {
							'value': ',Password field',
							'type': 2
						},
						'field_bool': {
							'value': 'Boolean field',
							'type': 1
						},
						'field_hidden': {
							'value': 'Hidden field',
							'type': 3
						},
						'field_text_optional': {
							'value': 'Text field optional',
							'flags': 1
						},
						'field_password_optional': {
							'value': 'Password field optional',
							'flags': 1,
							'type': 2
						}
					},
					'authSchemes': {
						'builtin': true,
					},
					'priority': 13
				}
			}
		);

		$('#externalStorage #addMountPoint .authentication:first').data('mechanisms', {
			'mechanism1': {
				'identifier': 'mechanism1',
				'name': 'Mechanism 1',
				'configuration': {
				},
				'scheme': 'builtin',
				'visibility': 3
			},
		});

	});
	afterEach(function() {
		select2Stub.restore();
		clock.restore();
	});

	describe('storage configuration', function() {
		var view;

		function selectBackend(backendName) {
			view.$el.find('.selectBackend:first').val(backendName).trigger('change');
		}

		beforeEach(function() {
			var $el = $('#externalStorage');
			view = new OCA.External.Settings.MountConfigListView($el, {encryptionEnabled: false});
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
			it('shows row even if selection row is hidden', function() {
				view.$el.find('tr#addMountPoint').hide();
				selectBackend('\\OC\\TestBackend');
				expect(view.$el.find('tr:first').is(':visible')).toBe(true);
				expect(view.$el.find('tr#addMountPoint').is(':visible')).toBe(false);
			});
			// TODO: test with personal mounts (no applicable fields)
			// TODO: test suggested mount point logic
		});
		describe('saving storages', function() {
			var $tr;

			beforeEach(function() {
				$tr = view.$el.find('tr:first');
				selectBackend('\\OC\\TestBackend');
			});
			it('saves storage after editing config', function() {
				var $field1 = $tr.find('input[data-parameter=field1]');
				expect($field1.length).toEqual(1);
				$field1.val('test');
				$field1.trigger(new $.Event('keyup', {keyCode: 97}));

				var $mountOptionsField = $tr.find('input.mountOptions');
				expect($mountOptionsField.length).toEqual(1);
				$mountOptionsField.val(JSON.stringify({previews:true}));

				clock.tick(4000);

				expect(fakeServer.requests.length).toEqual(1);
				var request = fakeServer.requests[0];
				expect(request.url).toEqual(OC.webroot + '/index.php/apps/files_external/globalstorages');
				expect(JSON.parse(request.requestBody)).toEqual({
					backend: '\\OC\\TestBackend',
					authMechanism: 'mechanism1',
					backendOptions: {
						'field1': 'test',
						'field2': ''
					},
					mountPoint: 'TestBackend',
					priority: 11,
					applicableUsers: [],
					applicableGroups: [],
					mountOptions: {
						'previews': true
					},
					testOnly: true
				});

				// TODO: respond and check data-id
			});
			it('saves storage after closing mount options dropdown', function() {
				$tr.find('.mountOptionsToggle img').click();
				$tr.find('[name=previews]').trigger(new $.Event('keyup', {keyCode: 97}));
				$tr.find('input[data-parameter=field1]').val('test');

				// does not save inside the dropdown
				expect(fakeServer.requests.length).toEqual(0);

				$('body').mouseup();

				// but after closing the dropdown
				expect(fakeServer.requests.length).toEqual(1);
			});
			// TODO: tests with "applicableUsers" and "applicableGroups"
			// TODO: test with missing mount point value
			// TODO: test with personal mounts (no applicable fields)
			// TODO: test save triggers: paste, keyup, checkbox
			// TODO: test "custom" field with addScript
			// TODO: status indicator
		});
		describe('validate storage configuration', function() {
			var $tr;

			beforeEach(function() {
				$tr = view.$el.find('tr:first');
				selectBackend('\\OC\\InputsTestBackend');
			});

			it('lists missing fields in storage errors', function() {
				var storage = view.getStorageConfig($tr);

				expect(storage.errors).toEqual({
					backendOptions: ['field_text', 'field_password']
				});
			});

			it('highlights missing non-optional fields', function() {
				_.each([
					'field_text',
					'field_password'
				], function(param) {
					expect($tr.find('input[data-parameter='+param+']').hasClass('warning-input')).toBe(true);
				});
				_.each([
					'field_bool',
					'field_hidden',
					'field_text_optional',
					'field_password_optional'
				], function(param) {
					expect($tr.find('input[data-parameter='+param+']').hasClass('warning-input')).toBe(false);
				});
			});

			it('validates correct storage', function() {
				$tr.find('[name=mountPoint]').val('mountpoint');

				$tr.find('input[data-parameter=field_text]').val('foo');
				$tr.find('input[data-parameter=field_password]').val('bar');
				$tr.find('input[data-parameter=field_text_optional]').val('foobar');
				// don't set field_password_optional
				$tr.find('input[data-parameter=field_hidden]').val('baz');

				var storage = view.getStorageConfig($tr);

				expect(storage.validate()).toBe(true);
			});

			it('checks missing mount point', function() {
				$tr.find('[name=mountPoint]').val('');

				$tr.find('input[data-parameter=field_text]').val('foo');
				$tr.find('input[data-parameter=field_password]').val('bar');

				var storage = view.getStorageConfig($tr);

				expect(storage.validate()).toBe(false);
			});
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
		describe('mount options dropdown', function() {
			var $tr;
			var $td;

			beforeEach(function() {
				$tr = view.$el.find('tr:first');
				$td = $tr.find('.mountOptionsToggle');
				selectBackend('\\OC\\TestBackend');
			});

			it('shows dropdown when clicking on toggle button, hides when clicking outside', function() {
				$td.find('img').click();

				expect($td.find('.dropdown').length).toEqual(1);

				$('body').mouseup();

				expect($td.find('.dropdown').length).toEqual(0);
			});

			it('doesnt show the encryption option when encryption is disabled', function () {
				view._encryptionEnabled = false;
				$td.find('img').click();

				expect($td.find('.dropdown [name=encrypt]:visible').length).toEqual(0);

				$('body').mouseup();

				expect($td.find('.dropdown').length).toEqual(0);
			});

			it('reads config from mountOptions field', function() {
				$tr.find('input.mountOptions').val(JSON.stringify({previews:false}));

				$td.find('img').click();
				expect($td.find('.dropdown [name=previews]').prop('checked')).toEqual(false);
				$('body').mouseup();

				$tr.find('input.mountOptions').val(JSON.stringify({previews:true}));
				$td.find('img').click();
				expect($td.find('.dropdown [name=previews]').prop('checked')).toEqual(true);
			});

			it('writes config into mountOptions field', function() {
				$td.find('img').click();
				// defaults to true
				var $field = $td.find('.dropdown [name=previews]');
				expect($field.prop('checked')).toEqual(true);
				$td.find('.dropdown [name=filesystem_check_changes]').val(0);
				$('body').mouseup();

				expect(JSON.parse($tr.find('input.mountOptions').val())).toEqual({
					encrypt: true,
					previews: true,
					enable_sharing: false,
					filesystem_check_changes: 0,
					encoding_compatibility: false
				});
			});
		});
	});
	describe('applicable user list', function() {
		// TODO: test select2 retrieval logic
	});
	describe('allow user mounts section', function() {
		// TODO: test allowUserMounting section
	});
});
