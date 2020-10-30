#!/usr/bin/env bash

set -e


if [[ ${TRAVIS_REPO_SLUG} != 'vimeo/psalm'  &&  -z ${PHAR_REPO_SLUG} ]]; then
    echo 'Not attempting phar deployment, as this is not vimeo/psalm, and $PHAR_REPO_SLUG is unset or empty'
    exit 0;
fi;

PHAR_REPO_SLUG=${PHAR_REPO_SLUG:=psalm/phar}

git clone https://${GITHUB_TOKEN}@github.com/${PHAR_REPO_SLUG}.git phar > /dev/null 2>&1

set -x # don't do set x above this point to protect the GITHUB_TOKEN

cd phar
rm -rf *
cp ../build/psalm.phar ../assets/psalm-phar/* .
cp ../build/psalm.phar.asc || true # not all users have GPG keys
mv dot-gitignore .gitignore
git config user.email "travis@travis-ci.org"
git config user.name "Travis CI"
git add --all .
git commit -m "Updated Psalm phar to commit ${TRAVIS_COMMIT}"
git push --quiet origin master > /dev/null 2>&1

if [[ "$TRAVIS_TAG" != '' ]] ; then
    git tag "$TRAVIS_TAG"
    git push origin "$TRAVIS_TAG"
fi
