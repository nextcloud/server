/**
 * SPDX-FileCopyrightText: 2016-2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * This node module is run by the karma executable to specify its configuration.
 *
 * The list of files from all needed JavaScript files including the ones from the
 * apps to test, and the test specs will be passed as configuration object.
 *
 * Note that it is possible to test a single app by setting the KARMA_TESTSUITE
 * environment variable to the apps name, for example "core" or "files_encryption".
 * Multiple apps can be specified by separating them with space.
 *
 * Setting the environment variable NOCOVERAGE to 1 will disable the coverage
 * preprocessor, which is needed to be able to debug tests properly in a browser.
 */

if (!process.env.CHROMIUM_BIN) {
	const chrome = require('puppeteer').executablePath()
	process.env.CHROMIUM_BIN = chrome
}

/* jshint node: true */
module.exports = function(config) {
	// respect NOCOVERAGE env variable
	// it is useful to disable coverage for debugging
	// because the coverage preprocessor will wrap the JS files somehow
	var enableCoverage = !parseInt(process.env.NOCOVERAGE, 10);
	console.log(
		'Coverage preprocessor: ',
		enableCoverage ? 'enabled' : 'disabled'
	);

	// read core files from core.json,
	// these are required by all apps so always need to be loaded
	// note that the loading order is important that's why they
	// are specified in a separate file
	var corePath = 'dist/';
	var coreModule = require('../core/js/core.json');
	var files = [
		// core mocks
		'core/js/tests/specHelper.js',
	];
	var preprocessors = {};

	var srcFile, i;
	// add core library files
	for (i = 0; i < coreModule.libraries.length; i++) {
		srcFile = corePath + coreModule.libraries[i];
		files.push(srcFile);
	}

	files.push('core/js/tests/html-domparser.js');
	files.push('dist/core-main.js');
	files.push('dist/core-files_fileinfo.js');
	files.push('dist/core-files_client.js');
	files.push('dist/core-systemtags.js');

	// add core modules files
	for (i = 0; i < coreModule.modules.length; i++) {
		srcFile = corePath + coreModule.modules[i];
		files.push(srcFile);
		if (enableCoverage) {
			preprocessors[srcFile] = 'coverage';
		}
	}

	// core tests
	files.push('core/js/tests/specs/**/*.js');
	// serve images to avoid warnings
	files.push({
		pattern: 'core/img/**/*',
		watched: false,
		included: false,
		served: true
	});

	// include core CSS
	files.push({
		pattern: 'core/css/*.css',
		watched: true,
		included: true,
		served: true
	});

	// Allow fonts
	files.push({
		pattern: 'core/fonts/*',
		watched: false,
		included: false,
		served: true
	});

	console.log(files)

	config.set({
		// base path, that will be used to resolve files and exclude
		basePath: '..',

		// frameworks to use
		frameworks: ['jasmine', 'jasmine-sinon', 'viewport'],

		// list of files / patterns to load in the browser
		files,

		// list of files to exclude
		exclude: [],

		proxies: {
			// prevent warnings for images
			'/base/tests/img/': 'http://localhost:9876/base/core/img/',
			'/base/tests/css/': 'http://localhost:9876/base/core/css/',
			'/base/core/css/images/': 'http://localhost:9876/base/core/css/images/',
			'/actions/': 'http://localhost:9876/base/core/img/actions/',
			'/base/core/fonts/': 'http://localhost:9876/base/core/fonts/',
			'/svg/': '../core/img/'
		},

		// test results reporter to use
		// possible values: 'dots', 'progress', 'junit', 'growl', 'coverage'
		reporters: ['spec'],

		specReporter: {
			maxLogLines: 5,
			suppressErrorSummary: false,
			suppressFailed: false,
			suppressPassed: true,
			suppressSkipped: true,
			showSpecTiming: false,
		},

		junitReporter: {
			outputFile: 'tests/autotest-results-js.xml'
		},

		// web server port
		port: 9876,

		preprocessors: preprocessors,

		coverageReporter: {
			dir: 'tests/karma-coverage',
			reporters: [
				{ type: 'html' },
				{ type: 'cobertura' },
				{ type: 'lcovonly' }
			]
		},

		// enable / disable colors in the output (reporters and logs)
		colors: true,

		// level of logging
		// possible values: config.LOG_DISABLE || config.LOG_ERROR || config.LOG_WARN || config.LOG_INFO || config.LOG_DEBUG
		logLevel: config.LOG_INFO,

		// enable / disable watching file and executing tests whenever any file changes
		autoWatch: true,

		// Start these browsers, currently available:
		// - Chrome
		// - ChromeCanary
		// - Firefox
		// - Opera (has to be installed with `npm install karma-opera-launcher`)
		// - Safari (only Mac; has to be installed with `npm install karma-safari-launcher`)
		// - PhantomJS
		// - IE (only Windows; has to be installed with `npm install karma-ie-launcher`)
		// use PhantomJS_debug for extra local debug
		browsers: ['Chrome_without_sandbox'],

		// you can define custom flags
		customLaunchers: {
			PhantomJS_debug: {
				base: 'PhantomJS',
				debug: true
			},
			// fix CI
			Chrome_without_sandbox: {
				base: 'ChromiumHeadless',
				flags: ['--no-sandbox'],
			},
		},

		// If browser does not capture in given timeout [ms], kill it
		captureTimeout: 60000,

		// Continuous Integration mode
		// if true, it capture browsers, run tests and exit
		singleRun: false
	});
};
