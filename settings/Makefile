all: clean npm-update build-js-production

dev-setup: clean npm-update build-js

npm-update:
	rm -rf node_modules
	npm update

build-js:
	npm run dev

build-js-production:
	npm run build

watch-js:
	npm run watch

clean:
	rm js/main.js
	rm js/main.js.map

