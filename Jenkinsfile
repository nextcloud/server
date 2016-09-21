#!groovy
/*
 * This Jenkinsfile is intended to run on https://ci.owncloud.org and may fail anywhere else.
 * It makes assumptions about plugins being installed, labels mapping to nodes that can build what is needed, etc.
 */

timestampedNode('SLAVE') {
    stage 'Checkout'
        checkout scm
        sh '''git submodule update --init'''

    stage 'JavaScript Testing'
        executeAndReport('tests/autotest-results-js.xml') {
            sh '''./autotest-js.sh'''
        }

    stage 'PHPUnit'
        executeAndReport('tests/autotest-results-sqlite.xml') {
            sh '''
            export NOCOVERAGE=1
            unset USEDOCKER
            phpenv local 5.6
            ./autotest.sh sqlite
            '''
        }
        executeAndReport('tests/autotest-results-mysql.xml') {
            sh '''
            export NOCOVERAGE=1
            unset USEDOCKER
            phpenv local 5.4
            ./autotest.sh mysql
            '''
        }
        executeAndReport('tests/autotest-results-pgsql.xml') {
            sh '''
            export NOCOVERAGE=1
            unset USEDOCKER
            phpenv local 5.6
            ./autotest.sh pgsql
            '''
        }
        executeAndReport('tests/autotest-results-oci.xml') {
            sh '''
            export NOCOVERAGE=1
            unset USEDOCKER
            phpenv local 5.5
            ./autotest.sh oci
            '''
        }

    stage 'Files External Testing'
        executeAndReport('tests/autotest-external-results-sqlite-webdav-ownCloud.xml') {
            sh '''phpenv local 5.6
            export NOCOVERAGE=1
            unset USEDOCKER
            ./autotest-external.sh sqlite webdav-ownCloud
            '''
        }
        executeAndReport('tests/autotest-external-results-sqlite-smb-silvershell.xml') {
            sh '''phpenv local 5.6
            export NOCOVERAGE=1
            unset USEDOCKER
            ./autotest-external.sh sqlite smb-silvershell
            '''
        }
        executeAndReport('tests/autotest-external-results-sqlite-smb-windows.xml') {
            sh '''phpenv local 5.6
            export NOCOVERAGE=1
            unset USEDOCKER
            ./autotest-external.sh sqlite smb-windows
            '''
        }

        step([$class: 'JUnitResultArchiver', testResults: 'tests/autotest-external-results-sqlite.xml'])

    stage 'Integration Testing'
        executeAndReport('build/integration/output/*.xml') {
            sh '''phpenv local 5.6
            rm -rf config/config.php
            ./occ maintenance:install --admin-pass=admin
            rm -rf build/integration/output
            rm -rf build/integration/vendor
            rm -rf build/integration/composer.lock
            cd build/integration
            ./run.sh
           '''
        }
}

void executeAndReport(String testResultLocation, def body) {
    def failed = false
    // We're wrapping this in a timeout - if it takes longer, kill it.
    try {
        timeout(time: 120, unit: 'MINUTES') {
            body.call()
        }
    } catch (Exception e) {
        failed = true
        echo "Test execution failed: ${e}"
    } finally {
        step([$class: 'JUnitResultArchiver', testResults: testResultLocation])
    }

    if (failed) {

        if (env.BRANCH_NAME == 'master' || env.BRANCH_NAME == 'stable9.1' || env.BRANCH_NAME == 'stable9' || env.BRANCH_NAME == 'stable8.2') {
            mail body: "project build error is here: ${env.BUILD_URL}" ,
                subject: "Build on release branch failed: ${env.BRANCH_NAME}",
                to: 'jenkins@owncloud.com'
        }

        error "Test execution failed. Terminating the build"
    }
}

// Runs the given body within a Timestamper wrapper on the given label.
def timestampedNode(String label, Closure body) {
    node(label) {
        wrap([$class: 'TimestamperBuildWrapper']) {
            body.call()
        }
    }
}
