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

// Download placeholder images for cities
foreach ($cities as $city) {
    $fileName = strtolower(str_replace(' ', '_', $city)) . ".jpg";
    $filePath = "$cityImagesDir/$fileName";
    
    // Skip if file already exists
    if (file_exists($filePath)) {
        echo "Image for $city already exists at $filePath\n";
        continue;
    }
    
    // Create a placeholder image with city name
    $url = "https://via.placeholder.com/800x600/4a90e2/ffffff?text=" . urlencode("$city");
    
    echo "Downloading image for $city from $url\n";
    
    // Try to download the image
    $imageContent = @file_get_contents($url);
    if ($imageContent === false) {
        echo "Failed to download image for $city\n";
        continue;
    }
    
    // Save the image
    if (file_put_contents($filePath, $imageContent)) {
        echo "Created placeholder image for $city at $filePath\n";
    } else {
        echo "Failed to save image for $city\n";
    }
}

echo "Done!\n";
?> 