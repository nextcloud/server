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

Install as global commands ( optional )
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
