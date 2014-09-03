// An example configuration file.
exports.config = {
  // Do not start a Selenium Standalone sever - only run this using chrome.
  chromeOnly: true,
  chromeDriver: './node_modules/protractor/selenium/chromedriver',

  // Capabilities to be passed to the webdriver instance.
  // See https://sites.google.com/a/chromium.org/chromedriver/capabilities
  capabilities: {
    'browserName': 'chrome',
    'chromeOptions': {
      'args': ['show-fps-counter=true', '--test-type', '--ignore-certificate-errors']
    }
  },
  
  // Use on Commmandline:
  // protractor ... --params.login.user=abc --params.login.password=123
  params: {
    baseUrl: "http://127.0.0.1/",
    login: {
      user: 'admin',
      password: 'password'
    }
  },

  suites: {
    install: 'tests/install/**/*_spec.js',
    login:  'tests/login/**/*_spec.js',
    apps:  'tests/apps/**/*_spec.js',
    files: 'tests/files/**/*_spec.js',
    share: 'tests/share/**/*_spec.js',
  },

  // seleniumAddress: 'http://0.0.0.0:4444/wd/hub',

  // Options to be passed to Jasmine-node.
  jasmineNodeOpts: {
    silent: true,
    showColors: true,
    onComplete: null,
    isVerbose: true,
    includeStackTrace: true,
    defaultTimeoutInterval: 180000
  },
  
  onPrepare: function(){
    global.isAngularSite = function(flag){
      browser.ignoreSynchronization = !flag;
    };
    browser.driver.manage().window().setSize(1000, 800);
    browser.driver.manage().window().maximize();
    
    require('jasmine-spec-reporter');
    // add jasmine spec reporter
    var spec_reporter = new jasmine.SpecReporter({
      displayStacktrace: false,     // display stacktrace for each failed assertion
      displayFailuresSummary: false, // display summary of all failures after execution
      displaySuccessfulSpec: true,  // display each successful spec
      displayFailedSpec: true,      // display each failed spec
      displaySkippedSpec: false,    // display each skipped spec
      displaySpecDuration: true,   // display each spec duration
      colors: {
        success: 'green',
        failure: 'red',
        skipped: 'cyan'
      },
      prefixes: {
        success: '✓ ',
        failure: '✗ ',
        skipped: '- '
      }
    });
    global.display = spec_reporter.display;
    jasmine.getEnv().addReporter(spec_reporter);
  }
};


// Headless testing with Phantomjs
// capabilities: {
//   'browserName': 'phantomjs',
//
//   /*
//    * Can be used to specify the phantomjs binary path.
//    * This can generally be ommitted if you installed phantomjs globally.
//    */
//   'phantomjs.binary.path':'./node_modules/phantomjs/bin/phantomjs',
//
//   /*
//    * Command line arugments to pass to phantomjs.
//    * Can be ommitted if no arguments need to be passed.
//    * Acceptable cli arugments: https://github.com/ariya/phantomjs/wiki/API-Reference#wiki-command-line-options
//    */
//   'phantomjs.cli.args':['--logfile=PATH', '--loglevel=DEBUG']
// },

// TODO: Mobile? See: https://github.com/angular/protractor/blob/master/docs/browser-setup.md#setting-up-protractor-with-appium---androidchrome
// multiCapabilities: [{
//   'browserName': 'firefox'
// }, {
//   'browserName': 'chrome'
// }]

