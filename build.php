<?php

$SRC_DIR=__DIR__."/src";
$PACKAGE_DIR=__DIR__."/build";
$PACKAGE_NAME="com_menumodules";

$cwd = getcwd();

echo "\nBuilding ${PACKAGE_NAME} package...\n\n";

if (!is_dir($PACKAGE_DIR)) {
    if (!mkdir($PACKAGE_DIR)) {
        die('Error creating build folder.');
    }
}

// recursively add directories to archive
function addDirectory(&$zip, $dir) {
    foreach(glob($dir . '/*') as $file) {
        if(is_dir($file)) {
            addDirectory($zip, $file);
        } else {
            // remove './' prefix before adding file
            $arr = explode('/', $file);
            unset($arr[0]);
            $file = implode('/', $arr);

            echo "Adding ${file}\n";
            $zip->addFile($file);
        }
    }
}

$zip = new ZipArchive;
echo "Creating ${PACKAGE_DIR}/${PACKAGE_NAME}.zip...\n";
if (!$zip->open("${PACKAGE_DIR}/${PACKAGE_NAME}.zip", ZipArchive::OVERWRITE)) {
    die("Failed to open zip.");
}

chdir($SRC_DIR); // so 'src' is not included in archive paths
addDirectory($zip, '.');
chdir($cwd);

echo "Closing archive...\n\n";
$zip->close();

if (!file_exists($PACKAGE_DIR.'/'.$PACKAGE_NAME.'.zip')) {
    die("There was an error creating the archive.");
}

echo "Done.\n";
echo "Install ${PACKAGE_NAME}.zip using Joomla Extension Manager.\n";

?>