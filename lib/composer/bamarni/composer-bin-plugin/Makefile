# See https://tech.davis-hansson.com/p/make/
MAKEFLAGS += --warn-undefined-variables
MAKEFLAGS += --no-builtin-rules

# General variables
TOUCH = bash .makefile/touch.sh

# PHP variables
COMPOSER=composer
COVERAGE_DIR = dist/coverage
INFECTION_BIN = tools/infection
INFECTION = php -d zend.enable_gc=0 $(INFECTION_BIN) --skip-initial-tests --coverage=$(COVERAGE_DIR) --only-covered --threads=4 --min-msi=100 --min-covered-msi=100 --ansi
PHPUNIT_BIN = vendor/bin/phpunit
PHPUNIT = php -d zend.enable_gc=0 $(PHPUNIT_BIN)
PHPUNIT_COVERAGE = XDEBUG_MODE=coverage $(PHPUNIT) --group default --coverage-xml=$(COVERAGE_DIR)/coverage-xml --log-junit=$(COVERAGE_DIR)/phpunit.junit.xml
PHPSTAN_BIN = vendor/bin/phpstan
PHPSTAN = $(PHPSTAN_BIN) analyse --level=5 src tests
PHP_CS_FIXER_BIN = tools/php-cs-fixer
PHP_CS_FIXER = $(PHP_CS_FIXER_BIN) fix --ansi --verbose --config=.php-cs-fixer.php
COMPOSER_NORMALIZE_BIN=tools/composer-normalize
COMPOSER_NORMALIZE = ./$(COMPOSER_NORMALIZE_BIN)


.DEFAULT_GOAL := default


#
# Command
#---------------------------------------------------------------------------

.PHONY: help
help:	  	  ## Shows the help
help:
	@printf "\033[33mUsage:\033[0m\n  make TARGET\n\n\033[32m#\n# Commands\n#---------------------------------------------------------------------------\033[0m\n"
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//' | awk 'BEGIN {FS = ":"}; {printf "\033[33m%s:\033[0m%s\n", $$1, $$2}'


.PHONY: default
default:	  ## Runs the default task: CS fix and all the tests
default: cs test


.PHONY: cs
cs:		  ## Runs PHP-CS-Fixer
cs: $(PHP_CS_FIXER_BIN) $(COMPOSER_NORMALIZE_BIN)
	$(PHP_CS_FIXER)
	$(COMPOSER_NORMALIZE)


.PHONY: phpstan
phpstan:	## Runs PHPStan
phpstan:
	$(PHPSTAN)


.PHONY: infection
infection: 	  ## Runs infection
infection: $(INFECTION_BIN) $(COVERAGE_DIR) vendor
	if [ -d $(COVERAGE_DIR)/coverage-xml ]; then $(INFECTION); fi


.PHONY: test
test: 	  	  ## Runs all the tests
test: validate-package phpstan $(COVERAGE_DIR) e2e #infection include infection later


.PHONY: validate-package
validate-package: ## Validates the Composer package
validate-package: vendor
	$(COMPOSER) validate --strict


.PHONY: coverage
coverage: 	  ## Runs PHPUnit with code coverage
coverage: $(PHPUNIT_BIN) vendor
	$(PHPUNIT_COVERAGE)


.PHONY: unit-test
unit-test: 	  ## Runs PHPUnit (default group)
unit-test: $(PHPUNIT_BIN) vendor
	$(PHPUNIT) --group default


.PHONY: e2e
e2e: 	  ## Runs PHPUnit end-to-end tests
e2e: $(PHPUNIT_BIN) vendor
	$(PHPUNIT) --group e2e


#
# Rules
#---------------------------------------------------------------------------

# Vendor does not depend on the composer.lock since the later is not tracked
# or committed.
vendor: composer.json
	$(COMPOSER) update
	$(TOUCH) "$@"

$(PHPUNIT_BIN): vendor
	$(TOUCH) "$@"

$(INFECTION_BIN): ./.phive/phars.xml
	phive install infection
	$(TOUCH) "$@"

$(COMPOSER_NORMALIZE_BIN): ./.phive/phars.xml
	phive install composer-normalize
	$(TOUCH) "$@"

$(COVERAGE_DIR): $(PHPUNIT_BIN) src tests phpunit.xml.dist
	$(PHPUNIT_COVERAGE)
	$(TOUCH) "$@"

$(PHP_CS_FIXER_BIN): vendor
	phive install php-cs-fixer
	$(TOUCH) "$@"

$(PHPSTAN_BIN): vendor
	$(TOUCH) "$@"
