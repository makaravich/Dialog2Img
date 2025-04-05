<?php

// Include the class
require_once '../src/Dialog_2_Img.php';

// Test dialog
$dialog = <<<DIALOG
*Before meeting him, my life was black and white
Has it become colorful now?
*No
*Now it’s just black
DIALOG;

// Create an instance of the class
$image_gen = new Dialog_2_Img(['imagesPath' => '../media/output/']);

// Generate a video
$outputPath = $image_gen->createVideo($dialog);

// Output the path to the generated file
if ($outputPath) {
    echo "File has been successfully created: $outputPath";
} else {
    echo "File has not been created.";
}
