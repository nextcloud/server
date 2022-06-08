all: clean dev-setup build-js-production

# Dev env management
dev-setup: clean npm-init

npm-init:
	npm ci

npm-update:
	npm update

# Building
build-js: build-css
	npm run dev

build-js-production:
	npm run build

watch-js: build-css
	npm run watch &
	npm run sass:watch

build-css:
	npm run sass:icons

# Linting
lint-fix:
	npm run lint:fix

lint-fix-watch:
	npm run lint:fix-watch

# Cleaning
clean:
	rm -rf dist

clean-git: clean
	git checkout -- dist
