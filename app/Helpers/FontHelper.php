<?php

namespace App\Helpers;

class FontHelper
{
    public static function getFontBase64($fontPath)
    {
        if (file_exists($fontPath)) {
            return base64_encode(file_get_contents($fontPath));
        }
        // Log or handle error if font file not found
        error_log("Font file not found: " . $fontPath);
        return null;
    }
}
