# Dialog2Img

`Dialog2Img` is a PHP class designed to generate images that simulate chat screenshots from a messenger. The class takes a text-based dialogue as input, where each message starts on a new line, and the other party's messages start with an asterisk (`*`). The output is an image with rounded-corner message bubbles styled for both users, saved with a unique file name.

## Features
- Generates images with chat-like appearance.
- Automatically wraps text to fit within message bubbles.
- Differentiates between user and other party messages with distinct colors and alignment.
- Saves the image to the `/img` folder with a randomly generated name.

## Installation
1. Clone the repository or download the class file.
2. Ensure the `DejaVuSans.ttf` font file is available in the same directory as the script or modify the font path in the class.
3. Make sure the `/img` directory is writable.

## Usage

You can use the `Dialog2Img` class to create images from text-based dialogues as follows:

```php
<?php
require_once 'Dialog2Img.php';

$dialog = "Hello!\nHow are you?\n*Hello! Everything is fine, and you?\nEverything is great too!";
$dialog2Img = new Dialog2Img();
$imagePath = $dialog2Img->create($dialog);
echo "Image saved at: " . $imagePath;
?>
```
## Example Output
The script will generate an image that simulates the appearance of a chat between two users, saving it in the /img directory.

## Customization
You can customize the following properties in the Dialog2Img class:

`$width`: Width of the generated image.
`$height`: Height of the generated image.
`$padding`: Padding around the edges of the image.
`$font`: Path to the TTF font used for the text.
`$fontSize`: Font size for the text.
`$lineHeight`: Vertical spacing between messages.

## License
This project is licensed under the GNU License.
