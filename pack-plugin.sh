#!/bin/bash
#!/bin/bash
NAME="yoti-joomla-extension-edge.zip"
SDK_RELATIVE_PATH="sdk"
curl https://github.com/getyoti/yoti-php-sdk/archive/master.zip -O -L
unzip master.zip -d sdk
mv sdk/yoti-php-sdk-master/src/* sdk
rm -rf sdk/yoti-php-sdk-master


if [ ! -d "./yoti" ]; then
    echo "ERROR: Must be in directory containing ./yoti folder"
    exit
fi

if [ ! -d "$SDK_RELATIVE_PATH" ]; then
    "ERROR: Could not find SDK in $SDK_RELATIVE_PATH"
    exit
fi

echo "Packing plugin ..."

# move sdk symlink (used in symlink-plugin-to-site.sh)
sym_exist=0
if [ -L "./yoti/site/sdk" ]; then
    mv "./yoti/site/sdk" "./__sdk-sym";
    sym_exist=1
fi

cp -R "$SDK_RELATIVE_PATH" "./yoti/site/sdk"
cd yoti && zip -r "$NAME" . -i "*" && mv "$NAME" .. && cd ..
rm -rf "./yoti/site/sdk"

# move symlink back
if [ $sym_exist ]; then
    mv "./__sdk-sym" "./yoti/site/sdk"
fi

rm -rf sdk

echo "Plugin packed. File $NAME created."
echo ""