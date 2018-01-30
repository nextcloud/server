app_name=notifications

project_dir=$(CURDIR)/../$(app_name)
build_dir=$(CURDIR)/build
source_dir=$(build_dir)/$(app_name)
sign_dir=$(build_dir)/sign

all: package

dev-setup: clean npm-update build-js

npm-update:
	rm -rf node_modules
	npm update

build-js:
	npm run dev

build-js-production:
	npm run build

clean:
	rm -rf $(build_dir)

package: clean build-js-production
	mkdir -p $(source_dir)
	rsync -a \
	--exclude=/build \
	--exclude=/docs \
	--exclude=/js-src \
	--exclude=/l10n/.tx \
	--exclude=/tests \
	--exclude=/.git \
	--exclude=/.github \
	--exclude=/CONTRIBUTING.md \
	--exclude=/issue_template.md \
	--exclude=/README.md \
	--exclude=/.gitignore \
	--exclude=/.scrutinizer.yml \
	--exclude=/.travis.yml \
	--exclude=/.drone.yml \
	--exclude=/node_modules \
	--exclude=/npm-debug.log \
	--exclude=/package.json \
	--exclude=/package-lock.json \
	--exclude=/Makefile \
	$(project_dir)/ $(source_dir)
