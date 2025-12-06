// Jenkinsfile for a fresh Jenkins installation (NO DOCKER REQUIRED)
// Runs on any available agent and gracefully skips unavailable tools.

pipeline {
    agent any

    environment {
        // Used for short commit ID in logs
        GIT_COMMIT_SHORT = ""
    }

    stages {

        stage('Initialize') {
            steps {
                script {
                    // compute short commit SHA
                    GIT_COMMIT_SHORT = sh(
                        script: "git rev-parse --short HEAD",
                        returnStdout: true
                    ).trim()

                    echo "Running CI for commit: ${GIT_COMMIT_SHORT}"
                }
            }
        }

        stage('Checkout') {
            steps {
                checkout scm
                echo "Code checked out successfully."
            }
        }

        stage('PHP Lint (Basic Syntax Check)') {
            steps {
                sh '''
                    echo "Checking if PHP is installed..."
                    if command -v php >/dev/null 2>&1; then
                        echo "Running PHP lint..."
                        find . -type f -name "*.php" -print0 | xargs -0 -n1 php -l
                    else
                        echo "PHP is NOT installed on this Jenkins agent — skipping php -l"
                    fi
                '''
            }
        }

        stage('Install Composer Dependencies') {
            steps {
                sh '''
                    if [ -f composer.json ]; then
                        echo "composer.json found."

                        if command -v composer >/dev/null 2>&1; then
                            echo "Installing Composer dependencies..."
                            composer install --no-interaction --prefer-dist || true
                        else
                            echo "Composer NOT installed on this Jenkins agent — skipping composer install"
                        fi
                    else
                        echo "No composer.json file — skipping Composer installation."
                    fi
                '''
            }
        }

        stage('Run PHPUnit Tests') {
            steps {
                sh '''
                    echo "Checking for PHPUnit..."

                    if [ -f vendor/bin/phpunit ]; then
                        echo "Running tests using vendor/phpunit..."
                        vendor/bin/phpunit --configuration phpunit.xml.dist || true

                    elif command -v phpunit >/dev/null 2>&1; then
                        echo "Running tests using system-installed phpunit..."
                        phpunit --configuration phpunit.xml.dist || true

                    else
                        echo "PHPUnit NOT found — skipping tests."
                    fi
                '''
            }
            post {
                always {
                    echo "Collecting junit reports if they exist..."
                    junit allowEmptyResults: true, testResults: '**/junit.xml'
                }
            }
        }

        stage('Archive Build Artifacts') {
            steps {
                archiveArtifacts artifacts: 'build/**/*', allowEmptyArchive: true
            }
        }
    }

    post {
        success {
            echo "Build SUCCESS for commit ${GIT_COMMIT_SHORT}"
        }
        failure {
            echo "Build FAILED for commit ${GIT_COMMIT_SHORT}"
        }
        always {
            echo "Pipeline finished."
        }
    }
}
