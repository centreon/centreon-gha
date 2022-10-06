#!/bin/sh

set -ex

PROJECT="centreon-plugin-packs"
VERSION=$1
COMMIT=$2
now=`date +%s`

export RELEASE="$now.$COMMIT"

if [ -d /build ]; then
    rm -rf /build
fi
mkdir -p /build

mkdir -p /build/debian/source
echo "3.0 (quilt)" > /build/debian/source/format
cat <<EOF > /build/debian/copyright
Format: https://www.debian.org/doc/packaging-manuals/copyright-format/1.0/
Upstream-Name: $PROJECT
Upstream-Contact: Luiz Costa <me@luizgustavo.pro.br>
Source: https://www.centreon.com

Files: *
Copyright: 2022 Centreon
License: Apache-2.0

Files: debian/*
Copyright: 2022 Centreon
License: Apache-2.0

License: Apache-2.0
 Licensed under the Apache License, Version 2.0 (the "License");
 you may not use this file except in compliance with the License.
 You may obtain a copy of the License at
 .
 https://www.apache.org/licenses/LICENSE-2.0
 .
 Unless required by applicable law or agreed to in writing, software
 distributed under the License is distributed on an "AS IS" BASIS,
 WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 See the License for the specific language governing permissions and
 limitations under the License.
 .
 On Debian systems, the complete text of the Apache version 2.0 license
 can be found in "/usr/share/common-licenses/Apache-2.0".

EOF
cat <<EOF > /build/debian/rules
#!/usr/bin/make -f

export DEB_BUILD_MAINT_OPTIONS = hardening=+all

%:
	dh \$@

EOF

# Make configuration package files
cd /src
if [ -d $PROJECT/packs ]; then
    rm -rf $PROJECT/packs
fi
mkdir -p $PROJECT/packs
python3 << EOF
import json
from os import listdir

output = """Source: $PROJECT
Section: net
Priority: optional
Maintainer: Luiz Costa <me@luizgustavo.pro.br>
Build-Depends: debhelper-compat (= 12)
Standards-Version: 4.5.0
Homepage: https://www.centreon.com
"""

for pack in listdir('$PROJECT/src'):
    with open('$PROJECT/src/%s/pack.json' % pack) as jfile:
        data = json.loads(jfile.read())

    # Set package names to lowercase (Debian case)

    if len(data['information']['plugins_requirements']) > 0:
        for ppr in data['information']['plugins_requirements']:
            ppr['package_name'] = ppr['package_name'].lower()

    if len(data['information']['requirement']) > 0:
        for ppr in data['information']['requirement']:
            ppr['name'] = ppr['name'].lower()

    output += 'Package: centreon-pack-%s\n' % pack
    output += 'Architecture: amd64\n'
    output += 'Depends: centreon-pp-manager\n'
    output += 'Description: centreon-pack-%s\n' % pack
    output += '\n\n'

    with open(
        '/build/debian/centreon-pack-%s.install' % (
            pack
        ), 'w+'
    ) as installFile:
        installFile.write(
            'packs/pluginpack_%s-%s.json    usr/share/centreon-packs\n' % (
                pack,
                data['information']['version']
            )
        )

    with open(
        '$PROJECT/packs/pluginpack_%s-%s.json' % (
            pack,
            data['information']['version']
        ), 'w+'
    ) as wjson:
        json.dump(data, wjson, indent=4)
        wjson.write("\n")

    print('Package %s processed!' % pack)

with open('/build/debian/control', 'w+') as DebianControl:
    DebianControl.write(output)
    print('File debian/control processed!')

EOF

mkdir -p /build/$PROJECT
(cd /src && tar czpf - $PROJECT/packs) | dd of=/build/$PROJECT-$VERSION.tar.gz
cp -rv /src/$PROJECT/packs /build/$PROJECT
cp -rv /build/debian /build/$PROJECT/

cd /build/$PROJECT
debmake -f "${AUTHOR}" -e "${AUTHOR_EMAIL}" -u "$VERSION" -y -r "$RELEASE"
debuild-pbuilder
cd /build

if [ -d "$RELEASE" ] ; then
    rm -rf "$RELEASE"
fi
mkdir $RELEASE
mv /build/*.deb $RELEASE/
mv /build/$RELEASE/*.deb /src

find /src -iname '*.deb'

exit 0
