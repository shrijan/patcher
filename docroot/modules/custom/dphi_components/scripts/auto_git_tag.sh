#!/bin/bash

# Get the current date in YY_MM format
CURRENT_DATE=$(date +%Y.%m)

# Get the latest tag
LATEST_TAG=$(git describe --tags $(git rev-list --tags --max-count=1))

# Extract the date and patch part from the latest tag
if [[ $LATEST_TAG =~ ^v([0-9]{4}\.[0-9]{2})\.(.*)$ ]]; then
    LATEST_DATE=${BASH_REMATCH[1]}
    LATEST_PATCH=${BASH_REMATCH[2]}
else
    LATEST_DATE="0000.00"
    LATEST_PATCH="0"
fi

# Determine the new version
if [[ $CURRENT_DATE == $LATEST_DATE ]]; then
    # Increment the patch version
    NEW_PATCH=$((LATEST_PATCH + 1))
else
    # Reset the patch version
    NEW_PATCH=0
fi

# Create the new version tag
NEW_VERSION="v${CURRENT_DATE}.${NEW_PATCH}"
echo "tagging $NEW_VERSION"

# Create a new tag
git tag $NEW_VERSION
