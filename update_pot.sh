#!/bin/sh
# quick and dirty script to update the .pot file
l10n_tools="../civi_l10n_tools"

# create a copy of the files to replace localise() with ts()
rm -rf /tmp/remoteevent_l10n_tmp
cp -rp . /tmp/remoteevent_l10n_tmp

# go there and replace localise strings, extract pot and copy
cwd=$(pwd)
cd /tmp/remoteevent_l10n_tmp
find ./ -type f -exec sed -i 's/[$][[:alnum:]]\+->localise(/ts(/g' {} +
${cwd}/${l10n_tools}/bin/create-pot-files-extensions.sh de.systopia.remoteevent ./ l10n
cp -p l10n/de.systopia.remoteevent.pot ${cwd}/l10n/de.systopia.remoteevent.pot

# cleanup
cd ${cwd}
rm -rf /tmp/remoteevent_l10n_tmp
