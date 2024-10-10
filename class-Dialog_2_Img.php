<?php

/**
 * It is a PHP class designed to generate images that simulate chat screenshots from a messenger.
 * The class takes a text-based dialogue as input, where each message starts on a new line,
 * and the other party's messages start with an asterisk (*).
 * The output is an image with rounded-corner message bubbles styled for both users, saved with a unique file name.
 */

class Dialog_2_Img {
    private int $width = 810;
    private int $height = 1080;
    private int $padding = 40;  // Message margins from edges
    private string $font;
    private int $fontSize = 40;
    private int $textPadding = 20;
    private int $lineHeight = 30;  // distance between messages
    private $image;
    private int|false $myMessageColor;
    private int|false $otherMessageColor;
    private int|false $textColor;
    private int|false $backgroundColor;

    public function __construct() {
        $this->font = 'arialn.ttf';  // path to the font DejaVu Sans

        // Initial creating image
        $this->image = imagecreatetruecolor($this->width, $this->height);

        // Set colors
        $this->backgroundColor = imagecolorallocate($this->image, 240, 240, 240); // Light-grey background
        $this->myMessageColor = imagecolorallocate($this->image, 173, 216, 230);  // blue message (from me)
        $this->otherMessageColor = imagecolorallocate($this->image, 255, 255, 255); // white message (from another)
        $this->textColor = imagecolorallocate($this->image, 0, 0, 0);  // black text

        // Set background
        imagefill($this->image, 0, 0, $this->backgroundColor);
    }

    public function create($dialog): string {
        $y = 50;  // start Y coordinate
        $messageMaxWidth = $this->width - 2 * $this->padding; // maximum message width including indents

        // Convert the dialog into an array of messages
        $lines = explode("\n", $dialog);
        $messages = [];
        foreach ($lines as $line) {
            if (str_starts_with($line, '*')) {
                $messages[] = ["user" => "other", "text" => trim(substr($line, 1))];
            } else {
                $messages[] = ["user" => "me", "text" => trim($line)];
            }
        }

        // Rendering of messages
        foreach ($messages as $message) {
            $text = $this->wrapText($message['text'], $messageMaxWidth - 2 * $this->textPadding);  // Breaking text into lines
            $box = imagettfbbox($this->fontSize, 0, $this->font, $text);  // calculate text size

            // Message coordinates and dimensions
            $textWidth = $box[2] - $box[0];
            $textHeight = $box[1] - $box[7];

            $messageWidth = $textWidth + 2 * $this->textPadding;
            $messageHeight = $textHeight + 2 * $this->textPadding;

            // We define the color and position for each message
            if ($message['user'] == 'me') {
                $x = $this->width - $messageWidth - $this->padding;  // on the right for messages from me with an indent
                $color = $this->myMessageColor;
            } else {
                $x = $this->padding;  // on the left for messages from another user
                $color = $this->otherMessageColor;
            }

            // Rendering a message with rounded corners
            $this->drawRoundedRectangle($x, $y, $x + $messageWidth, $y + $messageHeight, 10, $color);

            // Outputting text to an image, taking into account line breaks
            $lineY = $y + $this->textPadding + $this->fontSize;
            foreach (explode("\n", $text) as $line) {
                imagettftext($this->image, $this->fontSize, 0, $x + $this->textPadding, $lineY, $this->textColor, $this->font, $line);
                $lineY += $this->fontSize + 5;  // add space between strings
            }

            // Shifting the Y coordinate for the next message
            $y += $messageHeight + $this->lineHeight;
        }

        // Generate random image name
        $randomName = uniqid() . '_' . substr(md5(mt_rand()), 0, 5) . '.png';
        $filePath = __DIR__ . '/img/' . $randomName;

        // Save image to file
        imagepng($this->image, $filePath);

        // Free memory
        imagedestroy($this->image);

        return $filePath;  // Return the path to the created image
    }

    /**
     * Function for drawing rounded corners
     *
     * @param $x1
     * @param $y1
     * @param $x2
     * @param $y2
     * @param $radius
     * @param $color
     * @return void
     */
    private function drawRoundedRectangle($x1, $y1, $x2, $y2, $radius, $color): void {
        imagefilledrectangle($this->image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($this->image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
        imagefilledellipse($this->image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($this->image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($this->image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($this->image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }

    /**
     * Function for wrapping text to fit the width of the message
     *
     * @param $text
     * @param $maxWidth
     * @return string
     */
    private function wrapText($text, $maxWidth): string {
        $words = explode(' ', $text);
        $wrappedText = '';
        $line = '';
        foreach ($words as $word) {
            $testLine = $line . ' ' . $word;
            $box = imagettfbbox($this->fontSize, 0, $this->font, trim($testLine));
            $lineWidth = $box[2] - $box[0];
            if ($lineWidth > $maxWidth && !empty($line)) {
                $wrappedText .= trim($line) . "\n";
                $line = $word;
            } else {
                $line = $testLine;
            }
        }
        $wrappedText .= trim($line);
        return $wrappedText;
    }

    /**
     * Creates GIF animated dialog
     *
     * @param $dialog
     * @return string
     * @throws ImagickException
     */
    public function create_gif($dialog): string {
        $imagick = new Imagick();
        $imagick->setFormat('gif');

        $lines = explode("\n", $dialog);
        $messages = [];
        foreach ($lines as $line) {
            if (str_starts_with($line, '*')) {
                $messages[] = ["user" => "other", "text" => trim(substr($line, 1))];
            } else {
                $messages[] = ["user" => "me", "text" => trim($line)];
            }
        }

        $inputBoxHeight = 50;  // Height of the input box at the bottom
        $textSpeed = 2;  // Number of characters per frame
        $typingDelay = 10;  // Delay between typing frames

        // Generate frames for each message
        for ($i = 1; $i <= count($messages); $i++) {
            $partialDialog = implode("\n", array_slice($lines, 0, $i));
            $isUserMessage = $messages[$i - 1]['user'] == 'me';

            // If it's the user's message, simulate typing
            if ($isUserMessage) {
                $text = $messages[$i - 1]['text'];
                $currentText = '';

                // Typing simulation by adding one character at a time
                for ($j = 0; $j <= mb_strlen($text); $j += $textSpeed) {
                    $currentText = mb_substr($text, 0, $j);

                    // Add cursor (|) at the end while typing
                    $displayText = rtrim($currentText) . '|';

                    // Generate frame with the current state of the input box
                    $framePath = $this->create_with_input_box(implode("\n", array_slice($lines, 0, $i - 1)), $displayText, $inputBoxHeight);
                    $frame = new Imagick($framePath);
                    $frame->setImageFormat('gif');
                    $frame->setImageDelay($typingDelay);
                    $imagick->addImage($frame);
                    unlink($framePath);  // Remove intermediate files
                }

                // After typing is done, display the full message
                $framePath = $this->create_with_input_box($partialDialog, '', $inputBoxHeight);  // Empty input box
                $frame = new Imagick($framePath);
                $frame->setImageFormat('gif');
                $frame->setImageDelay($typingDelay);
                $imagick->addImage($frame);
                unlink($framePath);  // Remove intermediate files

                // Add a 1.5 second delay after the author's message
                $imagick->setImageDelay(150);  // 1.5 seconds delay
                $imagick->addImage($frame);
            } else {
                // If it's the other user's message, just display it as usual
                $framePath = $this->create($partialDialog);
                $frame = new Imagick($framePath);
                $frame->setImageFormat('gif');
                $frame->setImageDelay(100);  // Delay between frames
                $imagick->addImage($frame);
                unlink($framePath);  // Remove intermediate files
            }
        }

        // Add a final frame with a 5-second delay for reading
        $frame = $imagick->getImage();
        $frame->setImageDelay(500);  // 5 seconds delay
        $imagick->addImage($frame);

        // Generate random file name
        $randomName = uniqid() . '_' . substr(md5(mt_rand()), 0, 5) . '.gif';
        $filePath = __DIR__ . '/img/' . $randomName;

        // Save animation to file
        $imagick->writeImages($filePath, true);
        $imagick->clear();
        $imagick->destroy();

        return $filePath;  // Return the path to the created gif file
    }


    private function create_with_input_box($dialog, $typingText, $inputBoxHeight): string {
        $this->image = imagecreatetruecolor($this->width, $this->height + $inputBoxHeight);  // Adjust height for input box
        imagefill($this->image, 0, 0, $this->backgroundColor);

        // Render dialog messages
        $y = 50;
        $this->render_messages($dialog, $y);

        // Draw the rounded input box at the bottom
        $inputBoxY = $this->height;
        $inputBoxColor = imagecolorallocate($this->image, 255, 255, 255);  // White background for input box
        $borderColor = imagecolorallocate($this->image, 200, 200, 200);  // Light grey border for the input box

        // Draw the rounded rectangle for the input box
        $this->drawRoundedRectangle($this->padding, $inputBoxY + 10, $this->width - $this->padding, $inputBoxY + $inputBoxHeight - 10, 15, $inputBoxColor);

        // Add a border for realism
        imagerectangle($this->image, $this->padding, $inputBoxY + 10, $this->width - $this->padding, $inputBoxY + $inputBoxHeight - 10, $borderColor);

        // Measure text width and adjust if necessary (implement text overflow handling)
        $bbox = imagettfbbox($this->fontSize, 0, $this->font, $typingText);
        $textWidth = $bbox[2] - $bbox[0];
        $inputBoxWidth = $this->width - 2 * $this->padding - 20;  // Padding for the input box edges

        if ($textWidth > $inputBoxWidth) {
            // Trim the text from the start so that the last part is visible
            while ($textWidth > $inputBoxWidth) {
                $typingText = mb_substr($typingText, 1);  // Remove the first character
                $bbox = imagettfbbox($this->fontSize, 0, $this->font, $typingText);
                $textWidth = $bbox[2] - $bbox[0];
            }
        }

        // Calculate the vertical position of the text inside the input box
        $textHeight = $bbox[1] - $bbox[7];  // Height of the text based on bounding box
        $textY = $inputBoxY + (($inputBoxHeight - $textHeight) / 2) + $textHeight;  // Center text vertically inside the input box

        // Render the typing text without the extra cursor
        imagettftext($this->image, $this->fontSize, 0, round($this->padding + 15), round($textY), $this->textColor, $this->font, $typingText);

        // Save the frame
        $randomName = uniqid() . '_' . substr(md5(mt_rand()), 0, 5) . '.png';
        $filePath = __DIR__ . '/img/' . $randomName;
        imagepng($this->image, $filePath);
        imagedestroy($this->image);

        return $filePath;
    }

    private function render_messages($dialog, &$y) {
        $messageMaxWidth = $this->width - 2 * $this->padding; // maximum message width including indents

        // Convert the dialog into an array of messages
        $lines = explode("\n", $dialog);
        $messages = [];
        foreach ($lines as $line) {
            if (str_starts_with($line, '*')) {
                $messages[] = ["user" => "other", "text" => trim(substr($line, 1))];
            } else {
                $messages[] = ["user" => "me", "text" => trim($line)];
            }
        }

        // Rendering of messages
        foreach ($messages as $message) {
            $text = $this->wrapText($message['text'], $messageMaxWidth - 2 * $this->textPadding);  // Breaking text into lines
            $box = imagettfbbox($this->fontSize, 0, $this->font, $text);  // calculate text size

            // Message coordinates and dimensions
            $textWidth = $box[2] - $box[0];
            $textHeight = $box[1] - $box[7];

            $messageWidth = $textWidth + 2 * $this->textPadding;
            $messageHeight = $textHeight + 2 * $this->textPadding;

            // We define the color and position for each message
            if ($message['user'] == 'me') {
                $x = $this->width - $messageWidth - $this->padding;  // on the right for messages from me with an indent
                $color = $this->myMessageColor;
            } else {
                $x = $this->padding;  // on the left for messages from another user
                $color = $this->otherMessageColor;
            }

            // Rendering a message with rounded corners
            $this->drawRoundedRectangle($x, $y, $x + $messageWidth, $y + $messageHeight, 10, $color);

            // Outputting text to an image, taking into account line breaks
            $lineY = $y + $this->textPadding + $this->fontSize;
            foreach (explode("\n", $text) as $line) {
                imagettftext($this->image, $this->fontSize, 0, $x + $this->textPadding, $lineY, $this->textColor, $this->font, $line);
                $lineY += $this->fontSize + 5;  // add space between strings
            }

            // Shifting the Y coordinate for the next message
            $y += $messageHeight + $this->lineHeight;
        }
    }


}