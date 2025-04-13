<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

error_log('Received request: ' . print_r($_POST, true));

// Base fabric requirements in meters (for size M/standard size)
$baseRequirements = [
    
    // Children's clothing
    'girls-dress' => ['meter' => 2, 'pieces' => 4],
    'girls-skirt' => ['meter' => 1, 'pieces' => 2],
    'girls-blouse' => ['meter' => 1.5, 'pieces' => 3],
    'girls-pants' => ['meter' => 1.5, 'pieces' => 3],
    'boys-shirt' => ['meter' => 1.5, 'pieces' => 3],
    'boys-pants' => ['meter' => 2, 'pieces' => 4],
    'boys-shorts' => ['meter' => 1, 'pieces' => 2],

    // Adult clothing
    'dress' => ['meter' => 3, 'pieces' => 6],
    'blouse' => ['meter' => 2, 'pieces' => 4],
    'shirt' => ['meter' => 2, 'pieces' => 4],
    'pants' => ['meter' => 2.5, 'pieces' => 5],
    'skirt' => ['meter' => 2, 'pieces' => 4],

    // Curtains and drapes
    'curtains-standard' => ['meter' => 4, 'pieces' => 8],
    'curtains-sheer' => ['meter' => 3, 'pieces' => 6],
    'curtains-blackout' => ['meter' => 4.5, 'pieces' => 9],
    'valance' => ['meter' => 1, 'pieces' => 2],

    // Upholstery
    'chair-cushion' => ['meter' => 1, 'pieces' => 2],
    'sofa-cushion' => ['meter' => 2, 'pieces' => 4],
    'armchair' => ['meter' => 3, 'pieces' => 6],
    'sofa-small' => ['meter' => 5, 'pieces' => 10],
    'sofa-large' => ['meter' => 8, 'pieces' => 16],
    'ottoman' => ['meter' => 1.5, 'pieces' => 3],
    'dining-chair' => ['meter' => 0.75, 'pieces' => 2],
    'headboard' => ['meter' => 2, 'pieces' => 4]
];

$sizeMultipliers = [
    'XS' => 0.8,
    'S' => 0.9,
    'M' => 1,
    'L' => 1.1,
    'XL' => 1.2,
    'XXL' => 1.3
];

// Handle OPTIONS request for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $rawInput = file_get_contents('php://input');
        error_log('Raw input: ' . $rawInput);
        
        // Get input data
        $itemType = $_POST['itemType'] ?? '';
        $fabricType = $_POST['fabricType'] ?? '';
        $sizeType = $_POST['sizeType'] ?? 'standard';
        $isStretchy = filter_var($_POST['isStretchy'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $itemCategory = $_POST['itemCategory'] ?? 'apparel';

        // Log all POST data for debugging
        error_log('POST data: ' . print_r($_POST, true));
        
        // For connection test
        if (empty($itemType)) {
            echo json_encode(['status' => 'PHP server is running']);
            exit();
        }

        // Validate item type
        if (!isset($baseRequirements[$itemType])) {
            throw new Exception('Invalid item type: ' . $itemType);
        }

        $meters = 0;

        if ($itemCategory === 'apparel') {
            if ($sizeType === 'standard') {
                // Standard size calculation for apparel
                $size = $_POST['size'] ?? '';
                if (empty($size) || !isset($sizeMultipliers[$size])) {
                    throw new Exception('Invalid size: ' . $size);
                }

                $meters = $baseRequirements[$itemType]['meter'] * $sizeMultipliers[$size];
            } else {
                // Custom measurements calculation for apparel
                $measurementsJson = $_POST['measurements'] ?? '{}';
                error_log('Measurements JSON: ' . $measurementsJson);
                $measurements = json_decode($measurementsJson, true);
                
                if (empty($measurements) || !is_array($measurements)) {
                    throw new Exception('Invalid measurements format');
                }

                // Calculate based on measurements
                $height = floatval($measurements['height'] ?? 0);
                $chest = floatval($measurements['chest'] ?? 0);
                $waist = floatval($measurements['waist'] ?? 0);
                $hip = floatval($measurements['hip'] ?? 0);

                if ($height <= 0 || ($chest <= 0 && $waist <= 0 && $hip <= 0)) {
                    throw new Exception('Invalid measurements provided');
                }

                // Base calculation factors
                $heightFactor = 0.01; // 1cm = 0.01m
                $circumferenceFactor = 0.02; // For chest, waist, hip (with seam allowance)

                // Calculate required fabric based on garment type and measurements
                switch ($itemType) {
                    case 'dress':
                        $meters = ($height * $heightFactor * 1.5) + max($chest, $waist, $hip) * $circumferenceFactor;
                        break;
                    case 'blouse':
                    case 'shirt':
                        $meters = ($height * $heightFactor * 0.7) + ($chest * $circumferenceFactor);
                        break;
                    case 'pants':
                    case 'girls-pants':
                    case 'boys-pants':
                        $meters = ($height * $heightFactor * 1.2) + max($waist, $hip) * $circumferenceFactor;
                        break;
                    case 'skirt':
                    case 'girls-skirt':
                        $meters = ($height * $heightFactor * 0.6) + max($waist, $hip) * $circumferenceFactor;
                        break;
                    // Children's clothes
                    case 'girls-dress':
                    case 'boys-shirt':
                    case 'girls-blouse':
                        $meters = ($height * $heightFactor * 1.2) + ($chest * $circumferenceFactor * 0.8);
                        break;
                    case 'boys-shorts':
                        $meters = ($height * $heightFactor * 0.4) + ($waist * $circumferenceFactor * 0.8);
                        break;
                    default:
                        // Use base requirement if no specific calculation
                        $meters = $baseRequirements[$itemType]['meter'];
                }
            }

            // Adjust for stretchy fabric (apparel only)
            if ($isStretchy) {
                $meters *= 0.8;
            }
        } 
        elseif ($itemCategory === 'curtains') {
            // Custom measurements calculation for curtains
            $measurementsJson = $_POST['measurements'] ?? '{}';
            error_log('Curtain Measurements JSON: ' . $measurementsJson);
            $measurements = json_decode($measurementsJson, true);
            
            if (empty($measurements) || !is_array($measurements)) {
                throw new Exception('Invalid curtain measurements format');
            }

            $width = floatval($measurements['width'] ?? 0);
            $height = floatval($measurements['height'] ?? 0);
            $fullness = floatval($measurements['fullness'] ?? 2); // Default fullness factor is 2
            
            if ($width <= 0 || $height <= 0) {
                throw new Exception('Invalid width or height for curtains');
            }
            
            // Convert cm to meters
            $widthMeters = $width * 0.01;
            $heightMeters = $height * 0.01;
            
            // Calculate fabric needed with fullness factor and adding 40cm for hems and headers
            $meters = ($widthMeters * $fullness) + 0.4;
            
            // Multiply by height (plus 40cm for top/bottom hems)
            $meters *= ($heightMeters + 0.4);
            
            // Add extra if pattern matching is required
            if (isset($_POST['patternMatching']) && $_POST['patternMatching'] === 'true') {
                $meters *= 1.15; // Add 15% for pattern matching
            }
            
            // Adjust for curtain type
            switch ($itemType) {
                case 'curtains-sheer':
                    // Sheer curtains might need more fullness
                    $meters *= 1.2;
                    break;
                case 'curtains-blackout':
                    // Blackout curtains might need lining
                    if (isset($_POST['lined']) && $_POST['lined'] === 'true') {
                        $meters *= 1.8; // Almost double fabric for lining
                    }
                    break;
                case 'valance':
                    // Valances are smaller but may need more precise cutting
                    $meters *= 0.5;
                    break;
            }
        }
        elseif ($itemCategory === 'upholstery') {
            // Custom measurements calculation for upholstery
            $measurementsJson = $_POST['measurements'] ?? '{}';
            error_log('Upholstery Measurements JSON: ' . $measurementsJson);
            $measurements = json_decode($measurementsJson, true);
            
            if (empty($measurements) || !is_array($measurements)) {
                throw new Exception('Invalid upholstery measurements format');
            }
            
            // For upholstery, we need different measurements based on item type
            switch ($itemType) {
                case 'chair-cushion':
                case 'sofa-cushion':
                    $width = floatval($measurements['width'] ?? 0);
                    $depth = floatval($measurements['depth'] ?? 0);
                    $height = floatval($measurements['height'] ?? 0);
                    
                    if ($width <= 0 || $depth <= 0) {
                        throw new Exception('Invalid cushion measurements');
                    }
                    
                    // Calculate surface area plus seam allowance (in meters)
                    $meters = (($width * 0.01) + 0.1) * (($depth * 0.01) + 0.1) * 2; // Top and bottom
                    $sideArea = (($width * 0.01) + 0.1) * (($height * 0.01) + 0.1) * 2; // Two sides
                    $frontBackArea = (($depth * 0.01) + 0.1) * (($height * 0.01) + 0.1) * 2; // Front and back
                    $meters += $sideArea + $frontBackArea;
                    break;
                    
                case 'armchair':
                case 'sofa-small':
                case 'sofa-large':
                    // For larger upholstered pieces, use base requirement and adjust by dimensions
                    $width = floatval($measurements['width'] ?? 0);
                    $depth = floatval($measurements['depth'] ?? 0);
                    $height = floatval($measurements['height'] ?? 0);
                    
                    if ($width <= 0 || $depth <= 0 || $height <= 0) {
                        throw new Exception('Invalid furniture measurements');
                    }
                    
                    // Start with base requirement
                    $meters = $baseRequirements[$itemType]['meter'];
                    
                    // Adjust based on actual dimensions, using standard furniture dimensions as reference
                    $standardWidth = ($itemType === 'armchair') ? 80 : (($itemType === 'sofa-small') ? 160 : 220);
                    $standardDepth = 90;
                    $standardHeight = 100;
                    
                    // Calculate adjustment factor based on size difference
                    $widthFactor = $width / $standardWidth;
                    $depthFactor = $depth / $standardDepth;
                    $heightFactor = $height / $standardHeight;
                    
                    // Apply adjustments (surface area scales with width*depth, height affects back and arms)
                    $meters *= ($widthFactor * $depthFactor * 0.7) + ($heightFactor * 0.3);
                    break;
                    
                case 'ottoman':
                case 'dining-chair':
                case 'headboard':
                    // Simpler upholstery items
                    $width = floatval($measurements['width'] ?? 0);
                    $height = floatval($measurements['height'] ?? 0);
                    
                    if ($width <= 0 || $height <= 0) {
                        throw new Exception('Invalid furniture measurements');
                    }
                    
                    // Start with base requirement
                    $meters = $baseRequirements[$itemType]['meter'];
                    
                    // Determine standard dimensions based on item type
                    $standardWidth = 0;
                    $standardHeight = 0;
                    
                    if ($itemType === 'ottoman') {
                        $standardWidth = 60;  // cm
                        $standardHeight = 40; 
                    } elseif ($itemType === 'dining-chair') {
                        $standardWidth = 45;  
                        $standardHeight = 50; 
                    } elseif ($itemType === 'headboard') {
                        $standardWidth = 150; 
                        $standardHeight = 60; 
                    }
                    
                    // Adjust based on actual dimensions
                    $widthFactor = $width / $standardWidth;
                    $heightFactor = $height / $standardHeight;
                    
                    // Apply scaling factor based on area difference
                    $meters *= $widthFactor * $heightFactor;
                    break;
                
                default:
                    // Use base requirement if no specific calculation
                    $meters = $baseRequirements[$itemType]['meter'];
            }
            
            // Add extra fabric for pattern matching or directional fabrics
            if (isset($_POST['patternMatching']) && $_POST['patternMatching'] === 'true') {
                $meters *= 1.25; // Add 25% for pattern matching in upholstery
            }
        }
        else {
            throw new Exception('Invalid item category: ' . $itemCategory);
        }

        // Calculate pieces (50cm each)
        $pieces = ceil($meters / 0.5);

        // Return result
        echo json_encode([
            'meters' => number_format($meters, 2),
            'pieces' => $pieces,
            'debug_info' => [
                'item_type' => $itemType,
                'item_category' => $itemCategory,
                'size_type' => $sizeType,
                'is_stretchy' => $isStretchy
            ]
        ]);

    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'error' => $e->getMessage(),
            'debug_info' => [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'post_data' => $_POST
            ]
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>