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
	rm -rf apps/systemtags/js/systemtags.*
	rm -rf apps/twofactor_backupcodes/js
	rm -rf apps/updatenotification/js/updatenotification.*
	rm -rf apps/workflowengine/js/
	rm -rf core/js/dist
	rm -rf settings/js/vue-*

clean-git: clean
	git checkout -- apps/accessibility/js/
	git checkout -- apps/comments/js/
	git checkout -- apps/files_sharing/js/dist/
	git checkout -- apps/files_trashbin/js/
	git checkout -- apps/files_versions/js/
	git checkout -- apps/oauth2/js/
	git checkout -- apps/systemtags/js/systemtags.*
	git checkout -- apps/twofactor_backupcodes/js
	git checkout -- apps/updatenotification/js/updatenotification.*
	git checkout -- apps/workflowengine/js/
	git checkout -- core/js/dist
	git checkout -- settings/js/vue-*
