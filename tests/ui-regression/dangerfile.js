const {danger, fail, markdown, message, warn} = require('danger');
const {readFileSync} = require('fs');
const includes = require('lodash.includes');
const minimatch = require('minimatch');

// Now do work against our lists of files
const currentBuildResults = JSON.parse(
	readFileSync('./out/install.json')
);

let failingTests = currentBuildResults.failures;
if (failingTests.length > 0) {
	warn(`There are some ui comparison tests failing. Please have a look at` +
	     `https://ci-assets.nextcloud.com/nextcloud-ui-regression/nextcloud/server/${danger.github.pr.number}/index.html to check if your PR introduces an UI regression.`)
}
failingTests.forEach(test => {
	warn(test.fullTitle)
})



