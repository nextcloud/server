ownCloud Acceptance Tests
=========================


Setup
-----

Install node.js and run the following to install the dependencies

```
npm install
```

Install the webdriver
```
./node_modules/protractor/bin/webdriver-manager update
```

Install protractor as global command ( optional )
```
npm install -g protractor
```

Run
---

Run the tests with protractor
```
protractor protractor.conf.js
```

Run only a specific test suite or spec
```
protractor protractor.conf.js --suite install
protractor protractor_conf.js --params.baseUrl="http://127.0.0.1/ownClouds/test-community-7.0.1/" --suite=login
protractor protractor_conf.js --params.baseUrl="http://127.0.0.1/ownClouds/test-community-7.0.1/" --specs tests/login/newUser_spec.js
```

More Test Suits
---------------

You can find and define suites in ```protractor_conf.js```

Install suite: Run this suite on a not yet installed ownCloud, it will install during the tests

After installation tests should run without the "First Run Wizard" app because of timing issues.
Disable the app on the server with 

```
php occ app:disable firstrunwizard
```

Page Objects
------------

The ```tests/pages``` folder contains page objects.
A page object describes a webpage, gathers selectors and provides functions for actions on the page. 

In the specs these higher level functionality can be reused and the tests become nice and readable.

Development
-----------

A good starting point is the login suite in the login folder and the login page object.

If you want to start only a single test (it) or collection of tests (describe) use:

* iit to run a single test
* ddescribe to run only this collection

You can also use

* xit to exclude this test
* xdescribe to exclude this collection

For deeper insights and api docs have a look at 

* Protractor, [https://github.com/angular/protractor](https://github.com/angular/protractor)
* Jasmine, [https://github.com/pivotal/jasmine](https://github.com/pivotal/jasmine)

