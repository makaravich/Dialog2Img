<?php

/**
 * Class Dialog_2_Img
 *
 * This PHP class generates images that simulate chat screenshots from a messenger.
 * It takes a text-based dialogue as input, where each line represents a message.
 * Messages from other users start with an asterisk (*), which the class uses to distinguish
 * them from messages from the main user.
 *
 * The output is an image with message bubbles styled for both users, saved as a PNG file.
 * The appearance and layout can be customized through an array of parameters passed to the constructor.
 */
class Dialog_2_Img
{
    private int $width; // Width of the image
    private int $height; // Height of the image
    private int $padding; // Padding from the image edges
    private string $font; // Path to the font file
    private int $fontSize; // Font size for message text
    private int $textPadding; // Padding around the text within message bubbles
    private int $lineHeight; // Space between messages
    private $image; // The image resource
    private int|false $myMessageColor; // Color for user's messages
    private int|false $otherMessageColor; // Color for other user's messages
    private int|false $textColor; // Color for text
    private string $imagesPath; // Path to save images
    private string $debugMode; // Path to save images

    /**
     * Constructor for Dialog_2_Img
     *
     * Initializes the class properties based on provided configuration parameters or defaults,
     * and sets up the image canvas with a background color and necessary resources.
     *
     * @param array $config Configuration parameters for customizing the image (default: empty array)
     */
    public function __construct(array $config = [])
    {
        // Set properties with values from config array or defaults
        $this->debugMode = $config['debugMode'] ?? false;
        $this->width = $config['width'] ?? 1080;
        $this->height = $config['height'] ?? 1920;
        $this->padding = $config['padding'] ?? 80;
        $this->font = $config['font'] ?? dirname(__DIR__) . '/fonts/DejaVuSans.ttf';
        $this->fontSize = $config['fontSize'] ?? 40;
        $this->textPadding = $config['textPadding'] ?? 50;
        $this->lineHeight = $config['lineHeight'] ?? 20;
        $this->imagesPath = !empty($config['imagesPath']) ?
            rtrim($config['imagesPath'], '/\\') . DIRECTORY_SEPARATOR :
            realpath('.' . DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if ($this->debugMode) {
            error_log("Font Path: $this->font");
        }

        // Create an initial blank image
        $this->image = imagecreatetruecolor($this->width, $this->height);

        // Create alpha-channel
        imagealphablending($this->image, false);
        imagesavealpha($this->image, true);

        // Create transparent background
        $transparentColor = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
        imagefill($this->image, 0, 0, $transparentColor);

        $this->setColors($config);
    }

    /**
     * Sets colors used in the image based on configuration values or defaults.
     *
     * @param array $config Configuration array with color values
     */
    private function setColors(array $config): void
    {
        $this->myMessageColor = $this->allocateColor($config['myMessageColor'] ?? [173, 216, 230]);
        $this->otherMessageColor = $this->allocateColor($config['otherMessageColor'] ?? [255, 255, 255]);
        $this->textColor = $this->allocateColor($config['textColor'] ?? [0, 0, 0]);
    }

    /**
     * Allocates color in the image from RGB values.
     *
     * @param array $rgb Array with red, green, and blue values
     * @return int|false Color identifier or false on failure
     */
    private function allocateColor(array $rgb): int|false
    {
        return imagecolorallocate($this->image, $rgb[0], $rgb[1], $rgb[2]);
    }


    /**
     * Creates the chat image based on dialog text and optional background image.
     *
     * @param string $dialog Text representing the conversation, with each line as a message
     * @param string $backgroundImagePath Optional path to a background image
     * @return string Path to the saved image file
     */
    public function create(string $dialog, string $backgroundImagePath = ''): string
    {
        // Load background if specified
        if ($backgroundImagePath && file_exists($backgroundImagePath)) {
            $this->loadBackground($backgroundImagePath);
        }

        // Parse the dialog text into structured messages
        $messages = $this->parseDialog($dialog);

        // Render each message in the parsed dialog
        $this->renderMessages($messages);

        // Save the generated image and return the file path
        return $this->saveImage();
    }

    /**
     * Loads and applies a background image if available.
     *
     * @param string $backgroundImagePath Path to the background image file
     */
    private function loadBackground(string $backgroundImagePath): void
    {
        $background = imagecreatefromjpeg($backgroundImagePath);
        $this->width = imagesx($background);
        $this->height = imagesy($background);
        $this->image = imagecreatetruecolor($this->width, $this->height);

        // Add an alpha-channel for the background
        imagealphablending($this->image, false);
        imagesavealpha($this->image, true);

        // Create transparent background for the bg-image
        $transparentColor = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
        imagefill($this->image, 0, 0, $transparentColor);

        imagecopy($this->image, $background, 0, 0, 0, 0, $this->width, $this->height);
        imagedestroy($background);
    }

    /**
     * Parses the dialog string into an array of message objects.
     *
     * @param string $dialog Dialog text with each message on a new line
     * @return array Array of structured messages with user type and text
     */
    private function parseDialog(string $dialog): array
    {
        $lines = explode("\n", $dialog);
        $messages = [];
        foreach ($lines as $line) {
            $isOther = str_starts_with($line, '*');
            $messages[] = [
                'user' => $isOther ? 'other' : 'me',
                'text' => trim($isOther ? substr($line, 1) : $line)
            ];
        }
        return $messages;
    }

    /**
     * Renders all messages on the image canvas.
     *
     * @param array $messages Array of parsed messages
     */
    private function renderMessages(array $messages): void
    {
        $y = $this->padding;
        $messageMaxWidth = $this->width - 2 * $this->padding;

        foreach ($messages as $message) {
            $text = $this->wrapText($message['text'], $messageMaxWidth - 2 * $this->textPadding);
            [$textWidth, $textHeight] = $this->getTextDimensions($text);
            $messageWidth = $textWidth + 2 * $this->textPadding;
            $messageHeight = $textHeight + 2 * $this->textPadding;

            $x = ($message['user'] === 'me')
                ? $this->width - $messageWidth - $this->padding
                : $this->padding;
            $color = ($message['user'] === 'me') ? $this->myMessageColor : $this->otherMessageColor;

            // Draw message bubble with rounded corners
            $this->drawRoundedRectangle($x, $y, $x + $messageWidth, $y + $messageHeight, 30, $color);

            // Add the "tail" to the message bubble
            $this->drawMessageTail($x, $y, $messageWidth, $messageHeight, $message['user'], $color);

            // Draw the text inside the bubble
            $this->drawText($text, $x, $y, $messageHeight);
            $y += $messageHeight + $this->lineHeight;
        }
    }

    /**
     * Calculates the width and height of the text block.
     *
     * @param string $text The wrapped text
     * @return array Array with width and height of the text block
     */
    private function getTextDimensions(string $text): array
    {
        $box = imagettfbbox($this->fontSize, 0, $this->font, $text);
        return [$box[2] - $box[0], $box[1] - $box[7]];
    }

    /**
     * Draws a rounded rectangle for a message bubble.
     *
     * @param int $x1 Starting x-coordinate
     * @param int $y1 Starting y-coordinate
     * @param int $x2 Ending x-coordinate
     * @param int $y2 Ending y-coordinate
     * @param int $radius Radius of the rounded corners
     * @param int $color Color identifier for the rectangle
     */
    private function drawRoundedRectangle(int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        imagefilledrectangle($this->image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($this->image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledellipse($this->image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($this->image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($this->image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($this->image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }

    /**
     * Draws text within a message bubble.
     *
     * @param string $text Text to be drawn
     * @param int $x x-coordinate of the text
     * @param int $y y-coordinate of the text
     * @param int $messageHeight Height of the message bubble
     */
    private function drawText(string $text, int $x, int $y, int $messageHeight): void
    {
        $lineY = $y + $this->textPadding + $this->fontSize;
        foreach (explode("\n", $text) as $line) {
            imagettftext($this->image, $this->fontSize, 0, $x + $this->textPadding, $lineY, $this->textColor, $this->font, $line);
            $lineY += $this->fontSize * 1.75;
        }
    }

    /**
     * Wraps text to fit within a specified width.
     *
     * @param string $text Original text to wrap
     * @param int $maxWidth Maximum width for each line
     * @return string Wrapped text
     */
    private function wrapText(string $text, int $maxWidth): string
    {
        $words = explode(' ', $text);
        $wrappedText = '';
        $line = '';
        foreach ($words as $word) {
            $testLine = $line . ' ' . $word;
            $lineWidth = $this->getTextWidth($testLine);
            if ($lineWidth > $maxWidth && $line) {
                $wrappedText .= trim($line) . "\n";
                $line = $word;
            } else {
                $line = $testLine;
            }
        }
        return $wrappedText . trim($line);
    }

    /**
     * Gets the width of a single line of text.
     *
     * @param string $text The text line
     * @return int Width of the text line
     */
    private function getTextWidth(string $text): int
    {
        $box = imagettfbbox($this->fontSize, 0, $this->font, trim($text));
        return $box[2] - $box[0];
    }

    /**
     * Saves the generated image to a file and returns the file path.
     *
     * @return string File path of the saved image
     */
    private function saveImage(): string
    {
        $filePath = $this->generateRandomFileName('png');

        imagepng($this->image, $filePath);
        imagedestroy($this->image);
        return $filePath;
    }

    /**
     * Returns random file name based on its extension
     *
     * @param $extension
     * @return string
     */
    private function generateRandomFileName($extension): string
    {
        $randomName = uniqid() . '_' . substr(md5(mt_rand()), 0, 5) . '.' . $extension;

        // Create directory if doesn't exist
        if (!is_dir($this->imagesPath)) {
            mkdir($this->imagesPath, 0777, true);
        }

        return $this->imagesPath . $randomName;
    }

    /**
     * Draws the "tail" of the message bubble to indicate the speaker.
     *
     * @param int $x x-coordinate of the message bubble
     * @param int $y y-coordinate of the message bubble
     * @param int $messageWidth Width of the message bubble
     * @param int $messageHeight Height of the message bubble
     * @param string $userType Type of user ('me' or 'other')
     * @param int $color Color identifier for the tail
     */
    private function drawMessageTail(int $x, int $y, int $messageWidth, int $messageHeight, string $userType, int $color): void
    {
        if ($userType === 'me') {
            // Right tail for "me" messages
            $tail = [
                $x + $messageWidth - 20, $y + $messageHeight - 40,  // Top point of the tail
                $x + $messageWidth + 30, $y + $messageHeight - 20,  // Bottom right point
                $x + $messageWidth - 20, $y + $messageHeight,       // Bottom left point
            ];
        } else {
            // Left tail for "other" messages
            $tail = [
                $x + 20, $y + $messageHeight - 40,                   // Top point of the tail
                $x - 30, $y + $messageHeight - 20,                   // Bottom left point
                $x + 20, $y + $messageHeight                         // Bottom right point
            ];
        }
        imagefilledpolygon($this->image, $tail, $color);  // Draw the tail
    }

    /**
     * Overlays image over video
     *
     * @param string $videoUrl
     * @param string $imageUrl
     * @return string|bool
     */
    public function overlayImageOnVideo(string $videoUrl, string $imageUrl): string|bool
    {
        // Temporary files
        $videoTmp = tempnam(sys_get_temp_dir(), 'vid_') . '.mp4';
        $imageTmp = tempnam(sys_get_temp_dir(), 'img_') . '.png';

        // Download the video
        file_put_contents($videoTmp, file_get_contents($videoUrl));
        // Download the image

        file_put_contents($imageTmp, file_get_contents($imageUrl));

        // Check: if the files loaded
        if (filesize($videoTmp) === 0 || filesize($imageTmp) === 0) {
            echo "Error: Video or image haven't been loaded.\n";
            echo "Video File: $videoUrl \n";
            echo "Image File: $imageUrl \n";
            return false;
        }

        $outputPath = $this->generateRandomFileName('mp4');

        // FFmpeg command for overlaying the image
        // overlay=0:0 — coordinates for overlay (10px offset from the left and top)
        $cmd = "ffmpeg -i {$videoTmp} -i {$imageTmp} -filter_complex \"[1:v][0:v] overlay=0:0\" -codec:a copy -y ";

        // Debug mode
        if ($this->debugMode) {
            $logPath = '../tests/ffmpeg_debug.log';
            $cmd .= escapeshellarg($outputPath) . " > " . escapeshellarg($logPath) . " 2>&1";
        }

        // Execute the command
        exec($cmd, $output, $returnVar);

        // Delete temporary files
        unlink($videoTmp);
        unlink($imageTmp);

        if ($returnVar !== 0) {
            echo "FFmpeg Error. See ffmpeg_debug.log\n";
            return false;
        } else {
            return $outputPath;
        }
    }

    /**
     * Creates a video with chat meme
     *
     * @param string $dialog
     * @return string
     */
    public function createVideo(string $dialog): string
    {
        $image = $this->create($dialog);
        $video_path = '..\media\video.mp4';

        if ($result = $this->overlayImageOnVideo($image, $video_path)) {
            return $result;
        } else {
            return '';
        }
    }

}
