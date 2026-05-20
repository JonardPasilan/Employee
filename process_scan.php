<?php
header('Content-Type: application/json');

// Simple OCR simulation - In production, use Tesseract OCR or cloud OCR API
function extractTextFromImage($filePath) {
    // This is a placeholder. In production, integrate with:
    // - Tesseract OCR (tesseract-ocr/tesseract)
    // - Google Cloud Vision API
    // - AWS Textract
    // - Azure Computer Vision
    
    // For now, return sample extracted data
    return [
        'success' => true,
        'extracted' => [
            'name' => 'Sample Name (Edit Me)',
            'sex' => 'Male',
            'age' => '30',
            'birthdate' => '1994-01-01',
            'civil_status' => 'Single',
            'phone' => '09123456789',
            'office' => 'IT Department',
            'address' => 'Sample Address',
            'blood_pressure' => '120/80',
            'heart_rate' => '72',
            'respiratory_rate' => '16',
            'o2_saturation' => '98',
            'temperature' => '36.5',
            'height' => '170',
            'weight' => '65',
            'chief_complaint' => 'Sample complaint',
            'diagnosis' => 'Sample diagnosis',
            'notes' => 'Sample notes'
        ]
    ];
}

function extractTextFromPDF($filePath) {
    // Placeholder for PDF text extraction
    // In production, use libraries like:
    // - smalot/pdfparser
    // - spatie/pdf-to-text
    
    return extractTextFromImage($filePath);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['scan_file'])) {
    $file = $_FILES['scan_file'];
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'error' => 'Invalid file type. Only images and PDFs are allowed.']);
        exit;
    }
    
    // Check file size (max 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'File too large. Maximum size is 10MB.']);
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/uploads/scans/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('scan_') . '.' . $extension;
    $filePath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode(['success' => false, 'error' => 'Failed to upload file.']);
        exit;
    }
    
    // Extract text based on file type
    if ($file['type'] === 'application/pdf') {
        $result = extractTextFromPDF($filePath);
    } else {
        $result = extractTextFromImage($filePath);
    }
    
    // Clean up uploaded file (optional - keep for records or delete)
    // unlink($filePath);
    
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'error' => 'No file uploaded.']);
}
