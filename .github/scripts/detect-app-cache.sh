#!/bin/bash

# SPDX-FileCopyrightText: 2025 STRATO AG
# SPDX-License-Identifier: AGPL-3.0-or-later

# Script to detect which apps need building vs. can be restored from cache
# Supports multiple cache sources: GitHub Actions cache and JFrog Artifactory
# Outputs JSON arrays for apps to build and apps to restore

set -e  # Exit on error
set -u  # Exit on undefined variable
set -o pipefail  # Exit if any command in pipeline fails

# Required environment variables
: "${GH_TOKEN:?GH_TOKEN not set}"
: "${CACHE_VERSION:?CACHE_VERSION not set}"
: "${FORCE_REBUILD:?FORCE_REBUILD not set}"
: "${ARTIFACTORY_REPOSITORY_SNAPSHOT:?ARTIFACTORY_REPOSITORY_SNAPSHOT not set}"

# Optional JFrog variables
JF_URL="${JF_URL:-}"
JF_USER="${JF_USER:-}"
JF_ACCESS_TOKEN="${JF_ACCESS_TOKEN:-}"

# Input: MATRIX (JSON array of app configurations)
# Input: GITHUB_REF (current GitHub ref)
# Input: GITHUB_STEP_SUMMARY (path to step summary file)

# Outputs to $GITHUB_OUTPUT:
# - apps_to_build: JSON array of apps that need building
# - apps_to_restore: JSON array of apps that can be restored from cache
# - apps_sha_map: JSON object mapping app names to their SHAs
# - has_apps_to_build: boolean flag
# - has_apps_to_restore: boolean flag

echo "Collecting app SHAs and checking cache status..."
echo "Force rebuild mode: $FORCE_REBUILD"
echo ""

# Setup JFrog CLI if credentials are available
JFROG_AVAILABLE="false"
echo "=== JFrog Setup ==="
echo "JF_URL present: $([ -n "$JF_URL" ] && echo 'YES' || echo 'NO')"
echo "JF_USER present: $([ -n "$JF_USER" ] && echo 'YES' || echo 'NO')"
echo "JF_ACCESS_TOKEN present: $([ -n "$JF_ACCESS_TOKEN" ] && echo 'YES' || echo 'NO')"

if [ -n "$JF_URL" ] && [ -n "$JF_USER" ] && [ -n "$JF_ACCESS_TOKEN" ]; then
  echo "✓ All JFrog credentials available"
  echo "Installing JFrog CLI..."
  # Install JFrog CLI
  curl -fL https://install-cli.jfrog.io | sh
  export PATH=$PATH:$PWD
  echo "JFrog CLI version: $(jf --version)"

  # Configure JFrog
  echo "Configuring JFrog server: $JF_URL"
  jf config add jfrog-server --url="$JF_URL" --user="$JF_USER" --access-token="$JF_ACCESS_TOKEN" --interactive=false

  # Test connection with verbose output
  echo "Testing JFrog connection..."
  if jf rt ping; then
    JFROG_AVAILABLE="true"
    echo "✓ JFrog connection successful"
    echo "Repository: $ARTIFACTORY_REPOSITORY_SNAPSHOT"
  else
    echo "⚠ JFrog ping failed, will fall back to GitHub cache"
    echo "Ping output was unsuccessful"
  fi
else
  echo "⚠ JFrog credentials not available, using GitHub cache only"
  [ -z "$JF_URL" ] && echo "  - Missing: JF_URL"
  [ -z "$JF_USER" ] && echo "  - Missing: JF_USER"
  [ -z "$JF_ACCESS_TOKEN" ] && echo "  - Missing: JF_ACCESS_TOKEN"
fi
echo "JFROG_AVAILABLE=$JFROG_AVAILABLE"
echo "==================="
echo ""

# Get the matrix from input (passed as argument)
MATRIX="$1"

# Build JSON arrays for apps that need building/restoring
APPS_TO_BUILD="[]"
APPS_TO_RESTORE="[]"
APPS_CHECKED=0
APPS_CACHED=0
APPS_IN_JFROG=0
APPS_TO_BUILD_COUNT=0
APPS_TO_RESTORE_COUNT=0
APPS_SHA_MAP="{}"
echo ""

echo "### 📦 Cache Status Report for ($GITHUB_REF)" >> "$GITHUB_STEP_SUMMARY"
echo "" >> "$GITHUB_STEP_SUMMARY"
if [ "$FORCE_REBUILD" == "true" ]; then
  echo "**🔄 FORCE REBUILD MODE ENABLED** - All caches bypassed" >> "$GITHUB_STEP_SUMMARY"
  echo "" >> "$GITHUB_STEP_SUMMARY"
fi
if [ "$JFROG_AVAILABLE" == "true" ]; then
  echo "**🎯 JFrog Artifact Cache**: Enabled for all branches" >> "$GITHUB_STEP_SUMMARY"
  echo "" >> "$GITHUB_STEP_SUMMARY"
fi
echo "| App | SHA | Cache Key | Status |" >> "$GITHUB_STEP_SUMMARY"
echo "|-----|-----|-----------|--------|" >> "$GITHUB_STEP_SUMMARY"

# Iterate through each app in the matrix
while IFS= read -r app_json; do
  APP_NAME=$(echo "$app_json" | jq -r '.name')
  APP_PATH=$(echo "$app_json" | jq -r '.path')

  APPS_CHECKED=$((APPS_CHECKED + 1))

  # Get current submodule SHA
  if [ -d "$APP_PATH" ]; then
    CURRENT_SHA=$(git -C "$APP_PATH" rev-parse HEAD 2>/dev/null || echo "")
  else
    echo "⊘ $APP_NAME - directory not found, will build"
    echo "| $APP_NAME | N/A | N/A | ⊘ Directory not found |" >> "$GITHUB_STEP_SUMMARY"
    APPS_TO_BUILD=$(echo "$APPS_TO_BUILD" | jq -c --arg app "$APP_NAME" --arg sha "unknown" '. + [{name: $app, sha: $sha}]')
    APPS_TO_BUILD_COUNT=$((APPS_TO_BUILD_COUNT + 1))
    continue
  fi

  if [ -z "$CURRENT_SHA" ]; then
    echo "⊘ $APP_NAME - not a git repo, will build"
    echo "| $APP_NAME | N/A | N/A | ⊘ Not a git repo |" >> "$GITHUB_STEP_SUMMARY"
    APPS_TO_BUILD=$(echo "$APPS_TO_BUILD" | jq -c --arg app "$APP_NAME" --arg sha "unknown" '. + [{name: $app, sha: $sha}]')
    APPS_TO_BUILD_COUNT=$((APPS_TO_BUILD_COUNT + 1))
    continue
  fi

  # Add SHA to the map for all apps (regardless of cache status)
  APPS_SHA_MAP=$(echo "$APPS_SHA_MAP" | jq -c --arg app "$APP_NAME" --arg sha "$CURRENT_SHA" '.[$app] = $sha')

  # Cache key that would be used for this app
  # Format: <version>-app-build-<app-name>-<sha>
  CACHE_KEY="${CACHE_VERSION}-app-build-${APP_NAME}-${CURRENT_SHA}"
  SHORT_SHA="${CURRENT_SHA:0:8}"

  echo -n "  Checking $APP_NAME (SHA: $SHORT_SHA)... "

  # If force rebuild is enabled, skip cache check and rebuild everything
  if [ "$FORCE_REBUILD" == "true" ]; then
    echo "🔄 force rebuild"
    echo "| $APP_NAME | \`$SHORT_SHA\` | \`$CACHE_KEY\` | 🔄 Force rebuild |" >> "$GITHUB_STEP_SUMMARY"
    APPS_TO_BUILD=$(echo "$APPS_TO_BUILD" | jq -c --arg app "$APP_NAME" --arg sha "$CURRENT_SHA" '. + [{name: $app, sha: $sha}]')
    APPS_TO_BUILD_COUNT=$((APPS_TO_BUILD_COUNT + 1))
    continue
  fi

  # Check JFrog first before GitHub cache (available for all branches)
  if [ "$JFROG_AVAILABLE" == "true" ]; then
    JFROG_PATH="${ARTIFACTORY_REPOSITORY_SNAPSHOT}/dev-poc/apps/${APP_NAME}/${APP_NAME}-${CURRENT_SHA}.tar.gz"

    echo ""
    echo "  🔍 Checking JFrog for $APP_NAME..."
    echo "     Path: $JFROG_PATH"
    echo "     Full SHA: $CURRENT_SHA"

    # Check if artifact exists in JFrog with verbose output
    echo "     Running: jf rt s \"$JFROG_PATH\""
    SEARCH_OUTPUT=$(jf rt s "$JFROG_PATH" 2>&1)
    SEARCH_EXIT_CODE=$?

    echo "     Search exit code: $SEARCH_EXIT_CODE"
    if [ $SEARCH_EXIT_CODE -eq 0 ]; then
      echo "     Search output:"
      echo "$SEARCH_OUTPUT" | sed 's/^/       /'

      if echo "$SEARCH_OUTPUT" | grep -q "$JFROG_PATH"; then
        echo "     ✓ Artifact found in JFrog!"
        echo "✓ in JFrog"
        echo "| $APP_NAME | \`$SHORT_SHA\` | \`$JFROG_PATH\` | 📦 In JFrog |" >> "$GITHUB_STEP_SUMMARY"
        APPS_IN_JFROG=$((APPS_IN_JFROG + 1))
        APPS_TO_RESTORE_COUNT=$((APPS_TO_RESTORE_COUNT + 1))
        # Add to restore list with JFrog source
        APPS_TO_RESTORE=$(echo "$APPS_TO_RESTORE" | jq -c --argjson app "$app_json" --arg sha "$CURRENT_SHA" --arg jfrog_path "$JFROG_PATH" --arg source "jfrog" '. + [($app + {sha: $sha, jfrog_path: $jfrog_path, source: $source})]')
        continue
      else
        echo "     ✗ Artifact not found in search results"
      fi
    else
      echo "     ✗ Search failed with error:"
      echo "$SEARCH_OUTPUT" | sed 's/^/       /'
    fi
    echo "     → Falling back to GitHub cache check"
  fi

  # Check if cache exists using GitHub CLI
  # Include --ref to access caches from the current ref (branch, PR, etc.)
  CACHE_EXISTS="false"
  if ! CACHE_LIST=$(gh cache list --ref "$GITHUB_REF" --key "$CACHE_KEY" --json key --jq ".[].key" 2>&1); then
    echo "⚠️ Warning: Failed to query cache for $APP_NAME: $CACHE_LIST"
    echo "| $APP_NAME | \`$SHORT_SHA\` | \`$CACHE_KEY\` | ⚠️ Cache check failed - will build |" >> "$GITHUB_STEP_SUMMARY"
    APPS_TO_BUILD=$(echo "$APPS_TO_BUILD" | jq -c --arg app "$APP_NAME" --arg sha "$CURRENT_SHA" '. + [{name: $app, sha: $sha}]')
    APPS_TO_BUILD_COUNT=$((APPS_TO_BUILD_COUNT + 1))
    continue
  fi
  if echo "$CACHE_LIST" | grep -q "^${CACHE_KEY}$"; then
    CACHE_EXISTS="true"
    APPS_CACHED=$((APPS_CACHED + 1))
    APPS_TO_RESTORE_COUNT=$((APPS_TO_RESTORE_COUNT + 1))
    echo "✓ cached"
    echo "| $APP_NAME | \`$SHORT_SHA\` | \`$CACHE_KEY\` | ✅ Cached |" >> "$GITHUB_STEP_SUMMARY"
    # Add to restore list with GitHub cache source
    APPS_TO_RESTORE=$(echo "$APPS_TO_RESTORE" | jq -c --argjson app "$app_json" --arg sha "$CURRENT_SHA" --arg cache_key "$CACHE_KEY" --arg source "github-cache" '. + [($app + {sha: $sha, cache_key: $cache_key, source: $source})]')
  else
    echo "⚡ needs build"
    echo "| $APP_NAME | \`$SHORT_SHA\` | \`$CACHE_KEY\` | 🔨 Needs build |" >> "$GITHUB_STEP_SUMMARY"
    APPS_TO_BUILD=$(echo "$APPS_TO_BUILD" | jq -c --arg app "$APP_NAME" --arg sha "$CURRENT_SHA" '. + [{name: $app, sha: $sha}]')
    APPS_TO_BUILD_COUNT=$((APPS_TO_BUILD_COUNT + 1))
  fi

done < <(echo "$MATRIX" | jq -c '.[]')

echo "" >> "$GITHUB_STEP_SUMMARY"
echo "**Summary:**" >> "$GITHUB_STEP_SUMMARY"
echo "- Total apps checked: $APPS_CHECKED" >> "$GITHUB_STEP_SUMMARY"
echo "- 📦 Apps in JFrog: $APPS_IN_JFROG" >> "$GITHUB_STEP_SUMMARY"
echo "- ✅ Apps with cached builds: $APPS_CACHED" >> "$GITHUB_STEP_SUMMARY"
echo "- 🔨 Apps needing build: $APPS_TO_BUILD_COUNT" >> "$GITHUB_STEP_SUMMARY"
echo "" >> "$GITHUB_STEP_SUMMARY"

TOTAL_AVAILABLE=$((APPS_IN_JFROG + APPS_CACHED))
if [ $TOTAL_AVAILABLE -gt 0 ] && [ $APPS_CHECKED -gt 0 ]; then
  CACHE_HIT_PERCENT=$((TOTAL_AVAILABLE * 100 / APPS_CHECKED))
  echo "**Cache hit rate: ${CACHE_HIT_PERCENT}%** 🎯" >> "$GITHUB_STEP_SUMMARY"
  echo "" >> "$GITHUB_STEP_SUMMARY"
fi

echo ""
echo "Summary:"
echo "  Total apps: $APPS_CHECKED"
echo "  In JFrog: $APPS_IN_JFROG"
echo "  Cached: $APPS_CACHED"
echo "  To build: $APPS_TO_BUILD_COUNT"

# Validate no duplicate apps in build and restore lists
BUILD_APPS=$(echo "$APPS_TO_BUILD" | jq -r '.[].name' | sort)
RESTORE_APPS=$(echo "$APPS_TO_RESTORE" | jq -r '.[].name' | sort)
DUPLICATE_APPS=$(comm -12 <(echo "$BUILD_APPS") <(echo "$RESTORE_APPS"))

if [ -n "$DUPLICATE_APPS" ]; then
  echo "ERROR: Apps appear in both build and restore lists:"
  echo "$DUPLICATE_APPS"
  exit 1
fi

# Validate that we built valid JSON
if ! echo "$APPS_TO_BUILD" | jq empty 2>/dev/null; then
  echo "ERROR: Failed to build valid JSON for apps_to_build"
  echo "Content: $APPS_TO_BUILD"
  exit 1
fi

if ! echo "$APPS_TO_RESTORE" | jq empty 2>/dev/null; then
  echo "ERROR: Failed to build valid JSON for apps_to_restore"
  echo "Content: $APPS_TO_RESTORE"
  exit 1
fi

# Output app list with SHAs for the build job to use
# Use proper multiline output format for GitHub Actions
echo "apps_to_build<<APPS_TO_BUILD_JSON_EOF" >> "$GITHUB_OUTPUT"
echo "$APPS_TO_BUILD" >> "$GITHUB_OUTPUT"
echo "APPS_TO_BUILD_JSON_EOF" >> "$GITHUB_OUTPUT"

# Output the unified list of apps to restore (from either GitHub cache or JFrog)
echo "apps_to_restore<<APPS_TO_RESTORE_JSON_EOF" >> "$GITHUB_OUTPUT"
echo "$APPS_TO_RESTORE" >> "$GITHUB_OUTPUT"
echo "APPS_TO_RESTORE_JSON_EOF" >> "$GITHUB_OUTPUT"

# Output the SHA map for all apps
echo "apps_sha_map<<APPS_SHA_MAP_JSON_EOF" >> "$GITHUB_OUTPUT"
echo "$APPS_SHA_MAP" >> "$GITHUB_OUTPUT"
echo "APPS_SHA_MAP_JSON_EOF" >> "$GITHUB_OUTPUT"

# Output flags for conditional job execution
if [ $APPS_TO_BUILD_COUNT -gt 0 ]; then
  echo "has_apps_to_build=true" >> "$GITHUB_OUTPUT"
else
  echo "has_apps_to_build=false" >> "$GITHUB_OUTPUT"
fi

if [ $APPS_TO_RESTORE_COUNT -gt 0 ]; then
  echo "has_apps_to_restore=true" >> "$GITHUB_OUTPUT"
else
  echo "has_apps_to_restore=false" >> "$GITHUB_OUTPUT"
fi

echo ""
if [ $APPS_TO_BUILD_COUNT -eq 0 ]; then
  echo "🎉 All apps are cached! No builds needed."
else
  echo "✓ Will build $APPS_TO_BUILD_COUNT app(s)"
fi
