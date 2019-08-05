#!/usr/bin/env bash

# 1. Clone complete SVN repository to separate directory
svn co $SVN_REPOSITORY ../svn

# 2. Move assets/ to SVN /assets/
mv ./wordpress_org_assets/ ../svn/assets/

# 3. Copy git repository contents to SNV trunk/ directory
cp -R ./* ../svn/trunk/

# 4. Switch to SVN repository
cd ../svn/trunk/

# 5. Clean up unnecessary files
rm -rf .git/
rm -rf deploy/

# 6. Go to SVN repository root
cd ../

# 7. Create SVN tag
svn cp trunk tags/$TRAVIS_TAG

svn stat

# Add new files to SVN
svn stat | grep '^?' | awk '{print $2}' | xargs -I x svn add x@
# Remove deleted files from SVN
svn stat | grep '^!' | awk '{print $2}' | xargs -I x svn rm --force x@

svn stat svn

# 8. Push SVN tag
#svn ci  --message "Release $TRAVIS_TAG" \
#        --username $SVN_USERNAME \
#        --password $SVN_PASSWORD \
#        --non-interactive
