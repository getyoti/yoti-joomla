#!/bin/bash

SDK_TAG=$1

NAME="yoti-joomla-extension-edge.zip"
SDK_RELATIVE_PATH="sdk"

./checkout-sdk.sh "$SDK_TAG"

echo "Packing plugin ..."

rm -f ./yoti/admin/com_yoti.xml

cp -R "$SDK_RELATIVE_PATH" "./yoti/site/sdk"
cd yoti && zip -r "$NAME" . -i "*" && mv "$NAME" .. && cd ..
rm -rf "./yoti/site/sdk"

rm -rf sdk

echo "Plugin packed. File $NAME created."
echo ""
