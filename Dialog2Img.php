<?php

class Dialog2Img {
    private int $width = 400;
    private int $height = 600;
    private int $padding = 20;  // Message margins from edges
    private string $font;
    private int $fontSize = 14;
    private int $textPadding = 10;
    private int $lineHeight = 20;  // distance between messages
    private $image;
    private int|false $myMessageColor;
    private int|false $otherMessageColor;
    private int|false $textColor;
    private int|false $backgroundColor;

    public function __construct() {
        $this->font = 'DejaVuSans.ttf';  // path to the font DejaVu Sans

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
        $filePath = '/img/' . $randomName;

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
}