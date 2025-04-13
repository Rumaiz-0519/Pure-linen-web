<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fabric Size Helper - Pure Linen</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="Style.css">
    
    <style>
        .fc-container label {
            position: static !important;
            color: #333 !important;
            margin-bottom: 8px !important;
            font-size: 14px !important;
            display: block !important;
        }
        
        .fc-container {
            max-width: 600px;
            margin: 80px auto 30px;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Center the title */
        .fc-title {
            margin-bottom: 30px;
            color: #333;
            font-size: 28px;
            font-weight: normal;
            text-align: center;
        }
        
        .fc-form-group {
            margin-bottom: 20px;
        }
        
        .fc-select {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 15px;
            background-color: white;
        }
        
        .fc-checkbox-group {
            margin: 20px 0;
            display: flex;
            align-items: center;
        }
        
        .fc-checkbox-group label {
            display: inline-block !important;
            margin-left: 10px !important;
            position: static !important;
        }
        
        .fc-checkbox {
            width: 18px;
            height: 18px;
        }
        
        .fc-radio-group {
            margin: 15px 0;
            display: flex;
            gap: 20px;
            justify-content: center;
        }
        
        .fc-radio-label {
            display: flex !important;
            align-items: center !important;
            gap: 8px;
            margin: 0 !important;
            cursor: pointer;
        }
        
        .fc-radio {
            width: 18px;
            height: 18px;
        }
        
        .fc-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 15px;
        }
        
        .fc-measurements-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            max-width: 90%;
            margin: 0 auto;
        }
        
        .fc-custom-measurements {
            display: none;
        }
        
        /* Center the button */
        .fc-btn {
            background-color: #1B365D;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            max-width: 300px;
            margin: 20px auto;
            display: block;
            text-transform: uppercase;
        }
        
        .fc-btn:hover {
            background-color: #152c4d;
        }
        
        /* Center the result area */
        .fc-result {
            margin: 25px auto;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
            display: none;
            text-align: center;
            max-width: 90%;
        }
        
        /* Center tabs */
        .fc-tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 1px solid #e0e0e0;
            justify-content: center;
        }
        
        .fc-tab {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .fc-tab.active {
            color: #1B365D;
            border-bottom-color: #1B365D;
        }
        
        .fc-tab-content {
            display: none;
        }
        
        .fc-tab-content.active {
            display: block;
        }
        
        /* Center additional options section */
        .fc-additional-options {
            margin: 15px auto;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
            border-left: 3px solid #1B365D;
            max-width: 90%;
        }
        
        @media (max-width: 768px) {
            .fc-measurements-grid {
                grid-template-columns: 1fr;
            }
            
            .fc-tabs {
                flex-direction: column;
                border-bottom: none;
            }
            
            .fc-tab {
                border-bottom: 1px solid #e0e0e0;
                border-left: 3px solid transparent;
            }
            
            .fc-tab.active {
                border-bottom-color: #e0e0e0;
                border-left-color: #1B365D;
            }
            
            .fc-container {
                margin: 60px 20px 30px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!--NAVIGATION-->
    <nav class="navbar navbar-expand-lg navbar-light bg-light py-2 fixed-top">
        <div class="container">
            <img src="img/logo.png" alt="Pure Linen Logo" class="navbar-brand">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent">
                <i class="fas fa-bars"></i>
            </button>

            <div class="collapse navbar-collapse justify-content-center" id="navbarSupportedContent">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="Index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shop.php">Linen Fabric</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="swatch.php">Swatch Book</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">Orders</a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    <li class="nav-item">
                    <a class="nav-link" href="search.php" id="searchToggle" aria-label="Search">
                        <i class="fas fa-search"></i>
                    </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php">
                            <i class="fas fa-cart-plus"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-badge"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="nav-item dropdown">
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                                <li><p class="dropdown-item mb-0">Welcome, <?php echo $_SESSION['firstName']; ?></p></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="profile.php">Update Profile</a></li>
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        <?php else: ?>
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </a>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container-fluid mt-5 pt-5">
        <div class="row justify-content-center">
            <div class="col-12 col-md-10 col-lg-8">
                <div class="fc-container">
                    <h2 class="fc-title">Fabric Size Helper</h2>
                    
                    <!-- Category Tabs -->
                    <div class="fc-tabs">
                        <div class="fc-tab active" data-category="apparel">Apparel</div>
                        <div class="fc-tab" data-category="curtains">Curtains & Drapes</div>
                        <div class="fc-tab" data-category="upholstery">Upholstery</div>
                    </div>
                    
                    <form id="fabricForm">
                        <input type="hidden" id="itemCategory" name="itemCategory" value="apparel">
                        
                        <!-- Apparel Tab Content -->
                        <div class="fc-tab-content active" id="apparelContent">
                            <div class="fc-form-group">
                                <label for="apparelType">Garment Type</label>
                                <select id="apparelType" class="fc-select form-select">
                                    <option value="">Select Garment</option>
                                    <optgroup label="Girls">
                                        <option value="girls-dress">Girl's Dress</option>
                                        <option value="girls-skirt">Girl's Skirt</option>
                                        <option value="girls-blouse">Girl's Blouse</option>
                                        <option value="girls-pants">Girl's Pants</option>
                                    </optgroup>
                                    <optgroup label="Boys">
                                        <option value="boys-shirt">Boy's Shirt</option>
                                        <option value="boys-pants">Boy's Pants</option>
                                        <option value="boys-shorts">Boy's Shorts</option>
                                    </optgroup>
                                    <optgroup label="Adults">
                                        <option value="dress">Dress</option>
                                        <option value="blouse">Blouse</option>
                                        <option value="shirt">Shirt</option>
                                        <option value="pants">Pants</option>
                                        <option value="skirt">Skirt</option>
                                    </optgroup>
                                </select>
                            </div>
                            
                            <div class="fc-checkbox-group">
                                <input type="checkbox" id="stretchy" class="fc-checkbox">
                                <label for="stretchy">Is this for stretchy fabric?</label>
                            </div>
                            
                            <div class="fc-form-group">
                                <label>Size Type</label>
                                <div class="fc-radio-group">
                                    <label class="fc-radio-label">
                                        <input type="radio" name="sizeType" value="standard" checked class="fc-radio">
                                        Standard Sizes (XS-XXL)
                                    </label>
                                    <label class="fc-radio-label">
                                        <input type="radio" name="sizeType" value="custom" class="fc-radio">
                                        Custom Measurements
                                    </label>
                                </div>
                            </div>
                            
                            <div class="fc-form-group" id="standardSizes">
                                <label for="size">Size</label>
                                <select id="size" class="fc-select form-select">
                                    <option value="">Select Size</option>
                                    <option value="XS">XS</option>
                                    <option value="S">S</option>
                                    <option value="M">M</option>
                                    <option value="L">L</option>
                                    <option value="XL">XL</option>
                                    <option value="XXL">XXL</option>
                                </select>
                            </div>
                            
                            <div class="fc-form-group fc-custom-measurements" id="customMeasurements">
                                <label>Measurements (in cm)</label>
                                <div class="fc-measurements-grid">
                                    <div>
                                        <label for="height">Height</label>
                                        <input type="number" id="height" name="height" min="1" step="0.1" class="fc-input">
                                    </div>
                                    <div>
                                        <label for="chest">Chest</label>
                                        <input type="number" id="chest" name="chest" min="1" step="0.1" class="fc-input">
                                    </div>
                                    <div>
                                        <label for="waist">Waist</label>
                                        <input type="number" id="waist" name="waist" min="1" step="0.1" class="fc-input">
                                    </div>
                                    <div>
                                        <label for="hip">Hip</label>
                                        <input type="number" id="hip" name="hip" min="1" step="0.1" class="fc-input">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Curtains Tab Content -->
                        <div class="fc-tab-content" id="curtainsContent">
                            <div class="fc-form-group">
                                <label for="curtainType">Curtain Type</label>
                                <select id="curtainType" class="fc-select form-select">
                                    <option value="">Select Curtain Type</option>
                                    <option value="curtains-standard">Standard Curtains</option>
                                    <option value="curtains-sheer">Sheer Curtains</option>
                                    <option value="curtains-blackout">Blackout Curtains</option>
                                    <option value="valance">Valance</option>
                                </select>
                            </div>
                            
                            <div class="fc-form-group">
                                <label>Measurements (in cm)</label>
                                <div class="fc-measurements-grid">
                                    <div>
                                        <label for="curtainWidth">Window Width</label>
                                        <input type="number" id="curtainWidth" name="width" min="1" step="0.1" class="fc-input">
                                    </div>
                                    <div>
                                        <label for="curtainHeight">Curtain Length</label>
                                        <input type="number" id="curtainHeight" name="height" min="1" step="0.1" class="fc-input">
                                    </div>
                                    <div>
                                        <label for="fullness">Fullness (1.5-3)</label>
                                        <input type="number" id="fullness" name="fullness" min="1.5" max="3" step="0.1" value="2" class="fc-input">
                                        <small class="text-muted">Higher fullness creates more drape</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="fc-additional-options">
                                <h5>Additional Options</h5>
                                <div class="fc-checkbox-group">
                                    <input type="checkbox" id="patternMatchingCurtain" class="fc-checkbox">
                                    <label for="patternMatchingCurtain">Pattern matching required</label>
                                </div>
                                <div class="fc-checkbox-group">
                                    <input type="checkbox" id="lined" class="fc-checkbox">
                                    <label for="lined">Lined curtains</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Upholstery Tab Content -->
                        <div class="fc-tab-content" id="upholsteryContent">
                            <div class="fc-form-group">
                                <label for="upholsteryType">Upholstery Item</label>
                                <select id="upholsteryType" class="fc-select form-select">
                                    <option value="">Select Item Type</option>
                                    <option value="chair-cushion">Chair Cushion</option>
                                    <option value="sofa-cushion">Sofa Cushion</option>
                                    <option value="armchair">Armchair</option>
                                    <option value="sofa-small">Small Sofa (2-Seater)</option>
                                    <option value="sofa-large">Large Sofa (3-Seater)</option>
                                    <option value="ottoman">Ottoman</option>
                                    <option value="dining-chair">Dining Chair</option>
                                    <option value="headboard">Headboard</option>
                                </select>
                            </div>
                            
                            <div class="fc-form-group">
                                <label>Measurements (in cm)</label>
                                <div class="fc-measurements-grid" id="upholsteryMeasurements">
                                    <!-- Measurement fields will be dynamically updated based on selection -->
                                    <div>
                                        <label for="upholsteryWidth">Width</label>
                                        <input type="number" id="upholsteryWidth" name="width" min="1" step="0.1" class="fc-input">
                                    </div>
                                    <div>
                                        <label for="upholsteryHeight">Height</label>
                                        <input type="number" id="upholsteryHeight" name="height" min="1" step="0.1" class="fc-input">
                                    </div>
                                    <div>
                                        <label for="upholsteryDepth">Depth</label>
                                        <input type="number" id="upholsteryDepth" name="depth" min="1" step="0.1" class="fc-input">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="fc-additional-options">
                                <h5>Additional Options</h5>
                                <div class="fc-checkbox-group">
                                    <input type="checkbox" id="patternMatchingUpholstery" class="fc-checkbox">
                                    <label for="patternMatchingUpholstery">Pattern matching required</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center">
                            <button type="button" class="fc-btn" onclick="calculateFabric()">CALCULATE FABRIC</button>
                        </div>
                        
                        <div class="fc-result" id="result">
                            <h4>Recommended Fabric Amount:</h4>
                            <p id="metersResult">Length: 0 meters</p>
                            <p id="piecesResult">Or 0 pieces of 50cm</p>
                            <p><em>Note: This is an estimate. Actual requirements may vary based on pattern and design.</em></p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Cache DOM elements for better performance
            const standardSizes = document.getElementById('standardSizes');
            const customMeasurements = document.getElementById('customMeasurements');
            const sizeTypeRadios = document.getElementsByName('sizeType');
            const tabElements = document.querySelectorAll('.fc-tab');
            const tabContents = document.querySelectorAll('.fc-tab-content');
            const itemCategoryInput = document.getElementById('itemCategory');
            const apparelType = document.getElementById('apparelType');
            const curtainType = document.getElementById('curtainType');
            const upholsteryType = document.getElementById('upholsteryType');
            const upholsteryMeasurements = document.getElementById('upholsteryMeasurements');
            const resultArea = document.getElementById('result');
            const metersResult = document.getElementById('metersResult');
            const piecesResult = document.getElementById('piecesResult');
            
            // Handle category tabs
            tabElements.forEach(tab => {
                tab.addEventListener('click', function() {
                    // Update active tab
                    tabElements.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update active content
                    const category = this.getAttribute('data-category');
                    tabContents.forEach(content => content.classList.remove('active'));
                    document.getElementById(category + 'Content').classList.add('active');
                    
                    // Update hidden category input
                    itemCategoryInput.value = category;
                });
            });
            
            // Handle size type change for apparel
            sizeTypeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'standard') {
                        standardSizes.style.display = 'block';
                        customMeasurements.style.display = 'none';
                    } else {
                        standardSizes.style.display = 'none';
                        customMeasurements.style.display = 'block';
                    }
                });
            });
            
            // Handle upholstery type change to show/hide relevant measurement fields
            upholsteryType.addEventListener('change', function() {
                const selectedType = this.value;
                const depthField = document.getElementById('upholsteryDepth').parentElement;
                
                if (selectedType === 'armchair' || selectedType === 'sofa-small' || 
                    selectedType === 'sofa-large' || selectedType === 'chair-cushion' || 
                    selectedType === 'sofa-cushion') {
                    depthField.style.display = 'block';
                } else {
                    depthField.style.display = 'none';
                }
            });
            
            // Calculate fabric requirements
            window.calculateFabric = function() {
                const category = itemCategoryInput.value;
                let itemType, measurements;
                
                // Create FormData object
                const formData = new FormData();
                formData.append('itemCategory', category);
                
                // Get data based on selected category
                switch (category) {
                    case 'apparel':
                        itemType = apparelType.value;
                        const sizeType = document.querySelector('input[name="sizeType"]:checked').value;
                        const isStretchy = document.getElementById('stretchy').checked;
                        
                        if (!itemType) {
                            alert('Please select a garment type');
                            return;
                        }
                        
                        formData.append('itemType', itemType);
                        formData.append('sizeType', sizeType);
                        formData.append('isStretchy', isStretchy);
                        
                        if (sizeType === 'standard') {
                            const size = document.getElementById('size').value;
                            if (!size) {
                                alert('Please select a size');
                                return;
                            }
                            formData.append('size', size);
                        } else {
                            measurements = {
                                height: document.getElementById('height').value,
                                chest: document.getElementById('chest').value,
                                waist: document.getElementById('waist').value,
                                hip: document.getElementById('hip').value
                            };
                            
                            if (!measurements.height || (!measurements.chest && !measurements.waist && !measurements.hip)) {
                                alert('Please enter height and at least one body measurement');
                                return;
                            }
                            
                            formData.append('measurements', JSON.stringify(measurements));
                        }
                        break;
                        
                    case 'curtains':
                        itemType = curtainType.value;
                        
                        if (!itemType) {
                            alert('Please select a curtain type');
                            return;
                        }
                        
                        measurements = {
                            width: document.getElementById('curtainWidth').value,
                            height: document.getElementById('curtainHeight').value,
                            fullness: document.getElementById('fullness').value
                        };
                        
                        if (!measurements.width || !measurements.height) {
                            alert('Please enter curtain width and height');
                            return;
                        }
                        
                        const patternMatchingCurtain = document.getElementById('patternMatchingCurtain').checked;
                        const lined = document.getElementById('lined').checked;
                        
                        formData.append('itemType', itemType);
                        formData.append('patternMatching', patternMatchingCurtain);
                        formData.append('lined', lined);
                        formData.append('measurements', JSON.stringify(measurements));
                        break;
                        
                    case 'upholstery':
                        itemType = upholsteryType.value;
                        
                        if (!itemType) {
                            alert('Please select an upholstery item');
                            return;
                        }
                        
                        measurements = {
                            width: document.getElementById('upholsteryWidth').value,
                            height: document.getElementById('upholsteryHeight').value
                        };
                        
                        // Add depth for items that need it
                        if (itemType === 'armchair' || itemType === 'sofa-small' || 
                            itemType === 'sofa-large' || itemType === 'chair-cushion' || 
                            itemType === 'sofa-cushion') {
                            measurements.depth = document.getElementById('upholsteryDepth').value;
                            
                            if (!measurements.width || !measurements.height || !measurements.depth) {
                                alert('Please enter all measurements (width, height, and depth)');
                                return;
                            }
                        } else {
                            if (!measurements.width || !measurements.height) {
                                alert('Please enter width and height measurements');
                                return;
                            }
                        }
                        
                        const patternMatchingUpholstery = document.getElementById('patternMatchingUpholstery').checked;
                        
                        formData.append('itemType', itemType);
                        formData.append('patternMatching', patternMatchingUpholstery);
                        formData.append('measurements', JSON.stringify(measurements));
                        break;
                }
                
                // Show loading state
                const calculateBtn = document.querySelector('.fc-btn');
                const originalBtnText = calculateBtn.textContent;
                calculateBtn.textContent = 'Calculating...';
                calculateBtn.disabled = true;
                
                // Submit data to the server
                fetch('calculate_fabric.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    // Reset button
                    calculateBtn.textContent = originalBtnText;
                    calculateBtn.disabled = false;
                    
                    if (data.error) {
                        alert(data.error);
                        console.error('Error details:', data.debug_info);
                    } else {
                        // Display the results
                        resultArea.style.display = 'block';
                        metersResult.textContent = `Length: ${data.meters} meters`;
                        piecesResult.textContent = `Or ${data.pieces} pieces of 50cm`;
                        
                        // Scroll to the result
                        resultArea.scrollIntoView({ behavior: 'smooth' });
                    }
                })
                .catch(error => {
                    // Reset button
                    calculateBtn.textContent = originalBtnText;
                    calculateBtn.disabled = false;
                    
                    console.error('Error:', error);
                    alert('Error calculating fabric requirements. Please try again.');
                });
            };
        });
    </script>
    
     <!-- Footer -->
     <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="footer-section">
                        <h3>TOP RIZ INTERNATIONAL</h3>
                        <p>Creating elegant solutions in textile trade. Premium fabrics. Global reach. Trusted quality.
                        </p>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="footer-section">
                        <h4>Quick Links</h4>
                        <ul>
                            <li><a href="Index.php">HOME</a></li>
                            <li><a href="shop.php">SAMPLE FABRIC</a></li>
                            <li><a href="swatch.php">SWATCH BOOK</a></li>
                            <li><a href="orders.php">ORDERS</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="footer-section">
                        <h4>Extra Links</h4>
                        <ul>
                            <li><a href="login.php">LOGIN</a></li>
                            <li><a href="login.php">SIGNUP</a></li>
                            <li><a href="bulk_inquiry.php">BULK ORDERS</a></li>
                            <li><a href="fabric_calculator.php">FABRIC CALCULATOR</a></li>
                        </ul>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="footer-section">
                        <h4>Contact Us</h4>
                        <p><i class="fas fa-phone"></i> +94 777 123456</p>
                        <p><i class="fas fa-phone"></i> +94 777 123456</p>
                        <p><i class="fas fa-envelope"></i> toprizinternational@gmail.com</p>
                        <p><i class="fas fa-map-marker-alt"></i> 1/2 Crow Island, Colombo</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> Pure Linen. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>