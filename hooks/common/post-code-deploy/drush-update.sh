#!/bin/sh
#
# Cloud Hook: post-code-deploy
#
# The post-code-deploy hook is run whenever you use the Workflow page to
# deploy new code to an environment, either via drag-drop or by selecting
# an existing branch or tag from the Code drop-down list. See
# ../README.md for details.
#
# Usage: post-code-deploy site target-env source-branch deployed-tag repo-url
#                         repo-type

site="$1"
target_env="$2"
source_branch="$3"
deployed_tag="$4"
repo_url="$5"
repo_type="$6"
drush_alias=${site}'.'${target_env}

echo "$site.$target_env: Deployed branch $source_branch as $deployed_tag."
cd /var/www/html/${drush_alias}
./vendor/bin/drush @${drush_alias} cr
./vendor/bin/drush @${drush_alias} updb -y --strict=0
./vendor/bin/drush @${drush_alias} cim --source=../config/default -y
./vendor/bin/drush @${drush_alias} cr
echo "cache clear and config-split import done"
