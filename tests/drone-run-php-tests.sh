#!/bin/bash

echo "========================="
echo "= List of changed files ="
echo "========================="
git diff --name-only origin/$DRONE_TARGET_BRANCH...$DRONE_COMMIT_SHA
echo "========================="

[[ $(git diff --name-only origin/$DRONE_TARGET_BRANCH...$DRONE_COMMIT_SHA | wc -l) -eq 0 ]] && echo "No files are modified => merge commit" && exit 0

[[ $(git diff --name-only origin/$DRONE_TARGET_BRANCH...$DRONE_COMMIT_SHA | grep ".json" | grep -v "package.json" | grep -c -v "package-lock.json") -gt 0 ]] && echo "JSON files are modified" && exit 0

[[ $(git diff --name-only origin/$DRONE_TARGET_BRANCH...$DRONE_COMMIT_SHA | grep -c ".sh") -gt 0 ]] && echo "bash files are modified" && exit 0

[[ $(git diff --name-only origin/$DRONE_TARGET_BRANCH...$DRONE_COMMIT_SHA | grep -c ".yml") -gt 0 ]] && echo "YML files are modified" && exit 0

[[ $(git diff --name-only origin/$DRONE_TARGET_BRANCH...$DRONE_COMMIT_SHA | grep -c ".xml") -gt 0 ]] && echo "info.xml files are modified" && exit 0

[[ $(git diff --name-only origin/$DRONE_TARGET_BRANCH...$DRONE_COMMIT_SHA | grep -c ".php$") -gt 0 ]] && echo "PHP files are modified" && exit 0

[[ $(git diff --name-only origin/$DRONE_TARGET_BRANCH...$DRONE_COMMIT_SHA | grep -c "^tests/") -gt 0 ]] && echo "PHP test files are modified" && exit 0

[[ $(git diff --name-only origin/$DRONE_TARGET_BRANCH...$DRONE_COMMIT_SHA | grep -c "/tests/") -gt 0 ]] && echo "PHP test files of an app are modified" && exit 0

[[ $(git diff --name-only origin/$DRONE_TARGET_BRANCH...$DRONE_COMMIT_SHA | grep -c "3rdparty") -gt 0 ]] && echo "3rdparty is modified" && exit 0

[[ $(git diff --name-only origin/$DRONE_TARGET_BRANCH...$DRONE_COMMIT_SHA | grep -c "apps/theming/css") -gt 0 ]] && echo "theming css is modified" && exit 0

exit 1
