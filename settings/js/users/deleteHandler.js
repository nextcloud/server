/**
 * Copyright (c) 2014, Arthur Schiwon <blizzz@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */


/**
 * @brief takes care of deleting things represented by an ID
 * @param String endpoint: the corresponding ajax PHP script. Currently limited
 * to settings - ajax path.
 * @param String paramID: the by the script expected parameter name holding the
 * ID of the object to delete
 * @param Function markCallback: the function to be called after successfully
 * marking the object for deletion.
 * @param Function removeCallback: the function to be called after successful
 * delete. The id of the object will be passed as argument. Unsuccessful
 * operations will display an error using OC.dialogs, no callback is fired.
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
 * @brief enabled the notification system. Required for undo UI.
 * @param Object notifier: Usually OC.Notification
 * @param String dataID: an identifier for the notifier, e.g. 'deleteuser'
 * @param String message: the message that should be shown upon delete. %oid
 * will be replaced with the affected id of the item to be deleted
 * @param Function undoCb: called after "undo" was clicked so the consumer can
 * update the web interface
 */
DeleteHandler.prototype.setNotification = function(notifier, dataID, message, undoCallback) {
	this.notifier = notifier;
	this.notificationDataID = dataID;
	this.notificationMessage = message;
	this.undoCallback = undoCallback;

	var dh = this;

	$('#notification').on('click', '.undo', function () {
		if ($('#notification').data(dh.notificationDataID)) {
			var oid = dh.oidToDelete;
			UserDeleteHandler.cancel();
			if(typeof dh.undoCallback !== 'undefined') {
				dh.undoCallback(oid);
			}
		}
		dh.notifier.hide();
	});
};

/**
 * @brief shows the Undo Notification (if configured)
 */
DeleteHandler.prototype.showNotification = function() {
	if(this.notifier !== false) {
		if(!this.notifier.isHidden()) {
			this.hideNotification();
		}
		$('#notification').data(this.notificationDataID, true);
		var msg = this.notificationMessage.replace(this.notificationPlaceholder,
											this.oidToDelete);
		console.log('NOTISHOW ' + msg);
		this.notifier.showHtml(msg);
	}
};

/**
 * @brief hides the Undo Notification
 */
DeleteHandler.prototype.hideNotification = function() {
	if(this.notifier !== false) {
		$('#notification').removeData(this.notificationDataID);
		this.notifier.hide();
	}
};

/**
 * @brief initializes the delete operation for a given object id
 * @param String oid: the object id
 */
DeleteHandler.prototype.mark = function(oid) {
	if(this.oidToDelete !== false) {
		this.delete();
	}
	this.oidToDelete = oid;
	this.canceled = false;
	this.markCallback(oid);
	this.showNotification();
};

/**
 * @brief cancels a delete operation
 */
DeleteHandler.prototype.cancel = function() {
	this.canceled = true;
	this.oidToDelete = false;
};

/**
 * @brief executes a delete operation. Requires that the operation has been
 * initialized by mark(). On error, it will show a message via
 * OC.dialogs.alert. On success, a callback is fired so that the client can
 * update the web interface accordingly.
 */
DeleteHandler.prototype.delete = function() {
	if(this.canceled || this.oidToDelete === false) {
		return false;
	}

	var dh = this;
	console.log($('#notification').data(this.notificationDataID));
	if($('#notification').data(this.notificationDataID) === true) {
		dh.hideNotification();
		console.log('HIDDEN NOTI');
	}

	var payload = {};
	payload[dh.ajaxParamID] = dh.oidToDelete;
	$.ajax({
		type: 'POST',
		url: OC.filePath('settings', 'ajax', dh.ajaxEndpoint),
		async: false,
		data: payload,
		success: function (result) {
			if (result.status === 'success') {
				// Remove undo option, & remove user from table

				//TODO: following line
				dh.removeCallback(dh.oidToDelete);
				dh.canceled = true;
				console.log(dh.ajaxEndpoint);
			} else {
				OC.dialogs.alert(result.data.message, t('settings', 'Unable to delete ' + escapeHTML(dh.oidToDelete)));
				dh.undoCallback(dh.oidToDelete);
			}
		}
	});
};
