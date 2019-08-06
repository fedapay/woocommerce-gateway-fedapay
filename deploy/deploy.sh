#!/usr/bin/env bash

# 1. Clone complete SVN repository to separate directory
SVN_DIR=../svn
svn co $SVN_REPOSITORY $SVN_DIR

# 2. Update SVN assets/ folder
rsync -r -p ./wordpress_org_assets/* $SVN_DIR/assets

# 3. Clean up unnecessary files
rm -rf wordpress_org_assets/ deploy/

# 4. Copy git repository contents to SNV trunk/ directory
rsync -r -p ./* $SVN_DIR/trunk/

# 5. Create SVN tag
mkdir -p $SVN_DIR/tags/$TRAVIS_TAG
rsync -r -p ./* $SVN_DIR/tags/$TRAVIS_TAG

svn stat $SVN_DIR

# 6. Add new files to SVN
svn stat $SVN_DIR | grep '^?' | awk '{print $2}' | xargs -I x svn add x@
# 7. Remove deleted files from SVN
svn stat $SVN_DIR | grep '^!' | awk '{print $2}' | xargs -I x svn rm --force x@

svn stat $SVN_DIR

# 8. Push SVN tag
svn ci  --message "Release $TRAVIS_TAG" \
        --username $SVN_USERNAME \
        --password $SVN_PASSWORD \
        --non-interactive $SVN_DIR
