#!/usr/bin/env bash

# NAME
#     install.sh - Install Travis CI dependencies
#
# SYNOPSIS
#     install.sh
#
# DESCRIPTION
#     Creates the test fixture.

cd "$(dirname "$0")"

# Reuse ORCA's own includes.
source ../../../orca/bin/travis/_includes.sh

[[ -d "$ORCA_FIXTURE_DIR" ]] && composer require drupal/inline_entity_form drupal/panelizer --working-dir "$ORCA_FIXTURE_DIR"
exit 0
