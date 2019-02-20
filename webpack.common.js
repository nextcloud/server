const core = require('./core/webpack')
const settings = require('./settings/webpack')

const accessibility = require('./apps/accessibility/webpack')
const files_sharing = require('./apps/files_sharing/webpack')
const files_trashbin = require('./apps/files_trashbin/webpack')
const files_versions = require('./apps/files_versions/webpack')
const oauth2 = require('./apps/oauth2/webpack')
const systemtags = require('./apps/systemtags/webpack')
const twofactor_backupscodes = require('./apps/twofactor_backupcodes/webpack')
const updatenotifications = require('./apps/updatenotification/webpack')

module.exports = [].concat(
	core,
	settings,
	accessibility,
	files_sharing,
	files_trashbin,
	files_versions,
	oauth2,
	systemtags,
	twofactor_backupscodes,
	updatenotifications
);
