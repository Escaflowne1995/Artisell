<?php
// List of cities to create placeholder images for
$cities = [
    'Mandaue City',
    'Lapu-Lapu City',
    'Talisay City',
    'Naga City',
    'Carcar City',
    'Danao City',
    'Toledo City',
    'Bogo City'
];

// Directory to save images
$cityImagesDir = 'images/cities';
if (!file_exists($cityImagesDir)) {
    mkdir($cityImagesDir, 0777, true);
    echo "Created directory: $cityImagesDir\n";
}

// Generate placeholder images for cities
foreach ($cities as $city) {
    $fileName = strtolower(str_replace(' ', '_', $city)) . ".jpg";
    $filePath = "$cityImagesDir/$fileName";
    
    // Skip if file already exists
    if (file_exists($filePath)) {
        echo "Image for $city already exists at $filePath\n";
        continue;
    }
    
    echo "Creating image for $city at $filePath\n";
    
    // Create a simple image
    $width = 800;
    $height = 600;
    $image = imagecreatetruecolor($width, $height);
    
    // Colors
    $background = imagecolorallocate($image, 74, 144, 226); // Blue background
    $textColor = imagecolorallocate($image, 255, 255, 255); // White text
    
    // Fill background
    imagefilledrectangle($image, 0, 0, $width, $height, $background);
    
    // Add text
    $font = 5; // Built-in font
    $text = $city;
    
    // Calculate position to center the text
    $textWidth = imagefontwidth($font) * strlen($text);
    $textHeight = imagefontheight($font);
    $centerX = ($width - $textWidth) / 2;
    $centerY = ($height - $textHeight) / 2;
    
    // Draw text
    imagestring($image, $font, $centerX, $centerY, $text, $textColor);
    
    // Save the image
    if (imagejpeg($image, $filePath, 90)) {
        echo "Created placeholder image for $city\n";
    } else {
        echo "Failed to save image for $city\n";
    }
    
    // Free memory
    imagedestroy($image);
}

echo "Done!\n";
?> 