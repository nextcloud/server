all: clean dev-setup build-js-production

dev-setup: clean-dev npm-init

npm-init:
	npm install

npm-update:
	npm update

build-js:
	npm run dev

build-js-production:
	npm run build

watch-js:
	npm run watch

clean-dev:
	rm -rf node_modules

clean:
	rm -rf apps/accessibility/js/
	rm -rf apps/comments/js/
	rm -rf apps/files_sharing/js/dist/
	rm -rf apps/files_trashbin/js/
	rm -rf apps/files_versions/js/
	rm -rf apps/oauth2/js/
	rm -rf apps/systemtags/js/systemtags.js
	rm -rf apps/systemtags/js/systemtags.map
	rm -rf apps/twofactor_backupcodes/js
	rm -rf apps/updatenotification/js/updatenotification.js
	rm -rf apps/updatenotification/js/updatenotification.map
	rm -rf apps/workflowengine/js/
	rm -rf core/js/dist
	rm -rf settings/js/vue-*
