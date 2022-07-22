all: clean dev-setup build-js-production

# Dev env management
dev-setup: clean npm-init

npm-init:
	npm ci

npm-update:
	npm update

# Building
build-js:
	npm run dev

build-js-production:
	@echo Using node version $(shell node -v) and npm version $(shell npm -v)
	@echo Please make sure you are using the same version as recommended in the README.md
	@echo
	npm run build

watch-js:
	npm run watch

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
