/**
 * @copyright 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2016 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

describe('Undo handler', function() {
	var execute, undo, cmd;
	var clock;

	beforeEach(function() {
		jasmine.clock().install();
		clock = jasmine.clock();

		// Reset the handler
		OC.Undo.execute();

		execute = jasmine.createSpy('execute');
		undo = jasmine.createSpy('undo');
		cmd = new OC.Undo.Command(execute, undo);
	});

	afterEach(function() {
		jasmine.clock().uninstall();
	});

	it('shows a notification when a command is added', function() {
		spyOn(OC.Notification, 'showHtml').and.callThrough();

		OC.Undo.push(cmd, 'My action');

		expect(OC.Notification.showHtml.calls.count()).toBe(1);
		expect(OC.Notification.showHtml).toHaveBeenCalledWith('<span>My action</span> <a>Click to undo</a>');
	});

	it('hides the notification after 7sec and executes the command', function() {
		var $row = $('<div/>');
		spyOn(OC.Notification, 'showHtml').and.callFake(function() {
			return $row;
		});
		spyOn(OC.Notification, 'hide');

		OC.Undo.push(cmd, 'My action');
		clock.tick(3000);
		expect(execute).not.toHaveBeenCalled();
		expect(undo).not.toHaveBeenCalled();

		clock.tick(4200);
		expect(execute).toHaveBeenCalled();
		expect(undo).not.toHaveBeenCalled();
		expect(OC.Notification.hide).toHaveBeenCalledWith($row);
	});

	it('executes undo if the user clicks the notification', function() {
		var $row = $('<div/>');
		spyOn(OC.Notification, 'showHtml').and.callFake(function() {
			return $row;
		});
		spyOn(OC.Notification, 'hide');

		OC.Undo.push(cmd, 'My action');
		clock.tick(3000);
		expect(execute).not.toHaveBeenCalled();
		expect(undo).not.toHaveBeenCalled();

		$row.trigger('click');
		expect(execute).not.toHaveBeenCalled();
		expect(undo).toHaveBeenCalled();
		expect(OC.Notification.hide).toHaveBeenCalledWith($row);
	});

	it('executes the previous command if a second one is added', function() {
		spyOn(OC.Notification, 'showHtml').and.callThrough();

		OC.Undo.push(cmd, 'My action');
		clock.tick(3000);
		expect(execute).not.toHaveBeenCalled();
		expect(undo).not.toHaveBeenCalled();

		var cmd2 = new OC.Undo.Command(function() {}, function() {});
		OC.Undo.push(cmd2, 'My second action');

		expect(execute).toHaveBeenCalled();
		expect(undo).not.toHaveBeenCalled();
	});

	it('executes the added command', function() {
		OC.Undo.push(cmd, 'My action');
		clock.tick(3000);
		OC.Undo.execute();

		expect(execute).toHaveBeenCalled();
		expect(undo).not.toHaveBeenCalled();
	});
});

describe('undoable command', function() {
	var execute, undo;

	beforeEach(function() {
		execute = jasmine.createSpy('execute');
		undo = jasmine.createSpy('undo');
	});

	it('constructs the command correctly', function() {
		var cmd = new OC.Undo.Command(execute, undo);

		expect(cmd._executed).toBe(false);
		expect(cmd._undone).toBe(false);
	});

	it('does not execute a command twice', function() {
		var cmd = new OC.Undo.Command(execute, undo);

		cmd.execute();

		expect(execute.calls.count()).toBe(1);
		expect(cmd._executed).toBe(true);

		cmd.execute();

		expect(execute.calls.count()).toBe(1);
		expect(cmd._executed).toBe(true);
	});

	it('does not undo a command twice', function() {
		var cmd = new OC.Undo.Command(execute, undo);

		cmd.undo();

		expect(undo.calls.count()).toBe(1);
		expect(cmd._undone).toBe(true);

		cmd.execute();

		expect(undo.calls.count()).toBe(1);
		expect(cmd._undone).toBe(true);
	});
});
