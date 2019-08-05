#!/usr/bin/env bash

# 1. Clone complete SVN repository to separate directory
svn co $SVN_REPOSITORY ../svn

# 2. Update SVN assets/ folder
rsync -r -p ./wordpress_org_assets/* ../svn/assets

# 3. Clean up unnecessary files
rm -rf wordpress_org_assets/ deploy/ .git/

svn stat

# 4. Copy git repository contents to SNV trunk/ directory
rsync -r -p ./* ../svn/trunk/

# 5. Create SVN tag
mkdir ../svn/tags/$TRAVIS_TAG
rsync -r -p $PLUGIN/* ../svn/tags/$TRAVIS_TAG

svn stat

# 6. Add new files to SVN
svn stat | grep '^?' | awk '{print $2}' | xargs -I x svn add x@
# 7. Remove deleted files from SVN
svn stat | grep '^!' | awk '{print $2}' | xargs -I x svn rm --force x@

svn stat svn

# 8. Push SVN tag
#svn ci  --message "Release $TRAVIS_TAG" \
#        --username $SVN_USERNAME \
#        --password $SVN_PASSWORD \
#        --non-interactive
