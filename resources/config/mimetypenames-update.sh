#!/bin/bash

# SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

set -e

# Paths
MIME_JSON="mimetypemapping.dist.json"
XML_FILE="freedesktop.org.xml"
OUTPUT_JSON="mimetypenames.dist.json"

echo "1/ Extracting MIME types from $MIME_JSON"

# Extract all unique MIME types (excluding _comment keys)
MIME_TYPES=$(jq -r 'with_entries(select(.key | startswith("_") | not)) | to_entries | map(.value[]) | unique | .[]' "$MIME_JSON")

echo "Found $(echo "$MIME_TYPES" | wc -l) unique MIME types"

echo "2/ Downloading freedesktop.org XML file"
curl -sSL "https://gitlab.freedesktop.org/xdg/shared-mime-info/-/raw/03cb97596e90feda547c9b6a2addd656b14d1598/data/freedesktop.org.xml.in" > "$XML_FILE"
echo "Downloaded XML file to $XML_FILE"

echo "3/ Creating or updating MIME name mapping"

# Start from existing output if it exists, or create an empty one
if [ -f "$OUTPUT_JSON" ]; then
    cp "$OUTPUT_JSON" "$OUTPUT_JSON.tmp"
else
    echo "{}" > "$OUTPUT_JSON.tmp"
fi

# Track stats
MATCHED_COUNT=0
MISSING_COUNT=0

# Process each MIME type
while read -r MIME; do
    echo "Processing: $MIME"

    # Extract comment with XML namespace handling
    COMMENT=$(xmlstarlet sel -N x="http://www.freedesktop.org/standards/shared-mime-info" \
        -t -m "//x:mime-type[@type='${MIME}']" -v "x:comment" -n "$XML_FILE" | head -n 1)

    if [ -n "$COMMENT" ]; then
        ESCAPED_COMMENT=$(echo "$COMMENT" | sed 's/"/\\"/g')
        jq --arg key "$MIME" --arg value "$ESCAPED_COMMENT" '. + {($key): $value}' "$OUTPUT_JSON.tmp" > "$OUTPUT_JSON.tmp2" && mv "$OUTPUT_JSON.tmp2" "$OUTPUT_JSON.tmp"
        MATCHED_COUNT=$((MATCHED_COUNT + 1))
    else
        echo " > Warning: No description found for MIME type $MIME ⚠️"
        jq --arg key "$MIME" --arg value "" '. + {($key): $value}' "$OUTPUT_JSON.tmp" > "$OUTPUT_JSON.tmp2" && mv "$OUTPUT_JSON.tmp2" "$OUTPUT_JSON.tmp"
        MISSING_COUNT=$((MISSING_COUNT + 1))
    fi
done <<< "$MIME_TYPES"

# Final formatting and sorting by keys
jq -S . "$OUTPUT_JSON.tmp" > "$OUTPUT_JSON" && rm "$OUTPUT_JSON.tmp"

echo "✅ Done!"
echo "✔️  Descriptions found for $MATCHED_COUNT MIME types"
echo "⚠️  Descriptions missing for $MISSING_COUNT MIME types"
echo "📄 Output written to $OUTPUT_JSON"
