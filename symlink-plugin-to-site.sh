#!/bin/bash
#####################
# this script symlinks to the plugin dirs to the correct places in joomla in order to build
#####################
BASE=$1

if [ ! -d "$BASE" ]; then
    echo "$BASE not found."
    echo "$0 <joomla site>"
    exit
fi

declare -A dirs=(
    ["admin"]="administrator/components/com_yoti"
    ["modules/mod_yoti"]="modules/mod_yoti"
    ["plugins/yotiprofile"]="plugins/user/yotiprofile"
    ["site"]="components/com_yoti"
)
for i in ${!dirs[@]}
do
    target="$PWD/yoti/$i"
    link="$BASE/${dirs[$i]}"

    # if link already exists then don't create
    if [ -L "$link" ]; then continue; fi

    # if already installed plugin then move old dir
    if [ -d "$link" ]; then mv "$link" "$link.old"; fi

    # create link
    ln -s "$target" "$link"
done

# add sdk
target=$(realpath ../../src)
link="$BASE/${dirs[site]}/sdk"
if [ ! -L "$link" ]; then
    ln -s "$target" "$link"
fi
