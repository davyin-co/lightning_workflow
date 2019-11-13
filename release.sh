#!/bin/bash

# Tags and pushes a release to drupal.org's git remote.
# Example usage: ./release.sh 2.2.0 8.x-2.2

SEMANTIC_VERSION=$1
DRUPAL_VERSION=$2

PROJECT=${PWD##*/}
RELEASE_BRANCH=release/$SEMANTIC_VERSION

MARKDOWN=`command -v markdown`

# Ensure we are on a mainline release branch.
BRANCH=$(git rev-parse --abbrev-ref HEAD)

if [[ $BRANCH =~ ^8\.x\-[0-9]+\.x$ ]]; then
  git remote add drupal-org git@git.drupal.org:project/$PROJECT.git

  git merge --squash $RELEASE_BRANCH
  git commit --all --message "$SEMANTIC_VERSION Release"
  git push origin
  git push drupal-org

  git branch -D $RELEASE_BRANCH
  git push origin :$RELEASE_BRANCH
  git tag $DRUPAL_VERSION
  git push drupal-org $DRUPAL_VERSION

  git remote remove drupal-org
  echo "The tag $DRUPAL_VERSION has been pushed to drupal.org."

  if [[ $MARKDOWN ]]; then
    markdown logs/$DRUPAL_VERSION.md | pbcopy
    echo "The change log has been copied to the clipboard as HTML."
  else
    pbcopy < logs/$DRUPAL_VERSION.md
    echo "The change log has been copied to the clipboard as Markdown. You will need to manually convert it to HTML when creating the release."
    echo "If you want to skip this step next time, install markdown-to-html from https://github.com/cwjohan/markdown-to-html."
  fi

  echo "Go ahead and create the release at https://drupal.org/project/$PROJECT"
  exit 0
else
  echo "This can only be done from a mainline release branch, e.g. 8.x-4.x."
  exit 1
fi
