const core = require('./core/webpack');
const accessibility = require('./apps/accessibility/webpack')
const files_sharing = require('./apps/files_sharing/webpack')
const files_trashbin = require('./apps/files_trashbin/webpack')
const files_versions = require('./apps/files_versions/webpack')
const oauth2 = require('./apps/oauth2/webpack')
const systemtags = require('./apps/systemtags/webpack')
const updatenotifications = require('./apps/updatenotification/webpack')

module.exports = [].concat(
	core,
	accessibility,
	files_sharing,
	files_trashbin,
	files_versions,
	oauth2,
	systemtags,
	updatenotifications
);
