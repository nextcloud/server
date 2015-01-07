/**
 * Copyright (c) 2014, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

/**
 * takes care of deleting things represented by an ID
 *
 * @class
 * @param {string} endpoint the corresponding ajax PHP script. Currently limited
 * to settings - ajax path.
 * @param {string} paramID the by the script expected parameter name holding the
 * ID of the object to delete
 * @param {markCallback} markCallback function to be called after successfully
 * marking the object for deletion.
 * @param {removeCallback} removeCallback the function to be called after
 * successful delete.
 */
function DeleteHandler(endpoint, paramID, markCallback, removeCallback) {
	this.oidToDelete = false;
	this.canceled = false;

	this.ajaxEndpoint = endpoint;
	this.ajaxParamID = paramID;

	this.markCallback = markCallback;
	this.removeCallback = removeCallback;
	this.undoCallback = false;

	this.notifier = false;
	this.notificationDataID = false;
	this.notificationMessage = false;
	this.notificationPlaceholder = '%oid';
}

/**
 * Number of milliseconds after which the operation is performed.
 */
DeleteHandler.TIMEOUT_MS = 7000;

/**
 * Timer after which the action will be performed anyway.
 */
DeleteHandler.prototype._timeout = null;

/**
 * The function to be called after successfully marking the object for deletion
 * @callback markCallback
 * @param {string} oid the ID of the specific user or group
 */

/**
 * The function to be called after successful delete. The id of the object will
 * be passed as argument. Unsuccessful operations will display an error using
 * OC.dialogs, no callback is fired.
 * @callback removeCallback
 * @param {string} oid the ID of the specific user or group
 */

/**
 * This callback is fired after "undo" was clicked so the consumer can update
 * the web interface
 * @callback undoCallback
 * @param {string} oid the ID of the specific user or group
 */

/**
 * enabled the notification system. Required for undo UI.
 *
 * @param {object} notifier Usually OC.Notification
 * @param {string} dataID an identifier for the notifier, e.g. 'deleteuser'
 * @param {string} message the message that should be shown upon delete. %oid
 * will be replaced with the affected id of the item to be deleted
 * @param {undoCallback} undoCallback called after "undo" was clicked
 */
DeleteHandler.prototype.setNotification = function(notifier, dataID, message, undoCallback) {
	this.notifier = notifier;
	this.notificationDataID = dataID;
	this.notificationMessage = message;
	this.undoCallback = undoCallback;

	var dh = this;

	$('#notification')
		.off('click.deleteHandler_' + dataID)
		.on('click.deleteHandler_' + dataID, '.undo', function () {
		if ($('#notification').data(dh.notificationDataID)) {
			var oid = dh.oidToDelete;
			dh.cancel();
			if(typeof dh.undoCallback !== 'undefined') {
				dh.undoCallback(oid);
			}
		}
		dh.notifier.hide();
	});
};

/**
 * shows the Undo Notification (if configured)
 */
DeleteHandler.prototype.showNotification = function() {
	if(this.notifier !== false) {
		if(!this.notifier.isHidden()) {
			this.hideNotification();
		}
		$('#notification').data(this.notificationDataID, true);
		var msg = this.notificationMessage.replace(
			this.notificationPlaceholder, escapeHTML(this.oidToDelete));
		this.notifier.showHtml(msg);
	}
};

/**
 * hides the Undo Notification
 */
DeleteHandler.prototype.hideNotification = function() {
	if(this.notifier !== false) {
		$('#notification').removeData(this.notificationDataID);
		this.notifier.hide();
	}
};

/**
 * initializes the delete operation for a given object id
 *
 * @param {string} oid the object id
 */
DeleteHandler.prototype.mark = function(oid) {
	if(this.oidToDelete !== false) {
		// passing true to avoid hiding the notification
		// twice and causing the second notification
		// to disappear immediately
		this.deleteEntry(true);
	}
	this.oidToDelete = oid;
	this.canceled = false;
	this.markCallback(oid);
	this.showNotification();
	if (this._timeout) {
		clearTimeout(this._timeout);
		this._timeout = null;
	}
	if (DeleteHandler.TIMEOUT_MS > 0) {
		this._timeout = window.setTimeout(
				_.bind(this.deleteEntry, this),
			   	DeleteHandler.TIMEOUT_MS
		);
	}
};

/**
 * cancels a delete operation
 */
DeleteHandler.prototype.cancel = function() {
	if (this._timeout) {
		clearTimeout(this._timeout);
		this._timeout = null;
	}

	this.canceled = true;
	this.oidToDelete = false;
};

/**
 * executes a delete operation. Requires that the operation has been
 * initialized by mark(). On error, it will show a message via
 * OC.dialogs.alert. On success, a callback is fired so that the client can
 * update the web interface accordingly.
 *
 * @param {boolean} [keepNotification] true to keep the notification, false to hide
 * it, defaults to false
 */
DeleteHandler.prototype.deleteEntry = function(keepNotification) {
	if(this.canceled || this.oidToDelete === false) {
		return false;
	}

	var dh = this;
	if(!keepNotification && $('#notification').data(this.notificationDataID) === true) {
		dh.hideNotification();
	}

	if (this._timeout) {
		clearTimeout(this._timeout);
		this._timeout = null;
	}

	var payload = {};
	payload[dh.ajaxParamID] = dh.oidToDelete;
	$.ajax({
		type: 'DELETE',
		url: OC.generateUrl(dh.ajaxEndpoint+'/'+this.oidToDelete),
		// FIXME: do not use synchronous ajax calls as they block the browser !
		async: false,
		success: function (result) {
			if (result.status === 'success') {
				// Remove undo option, & remove user from table

				//TODO: following line
				dh.removeCallback(dh.oidToDelete);
				dh.canceled = true;
			} else {
				OC.dialogs.alert(result.data.message, t('settings', 'Unable to delete {objName}', {objName: dh.oidToDelete}));
				dh.undoCallback(dh.oidToDelete);
			}
		}
	});
};
