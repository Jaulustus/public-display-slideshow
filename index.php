<?php
// Starte Session, um Einstellungen von werbung-settings.php zu übernehmen
session_start();

// Starte Output-Buffering für bessere Performance
ob_start();

// Cache-Einstellungen für bessere Performance
$isPreview = isset($_GET['preview']) && $_GET['preview'] === '1';

if ($isPreview) {
    // Für Vorschau kein Caching erlauben
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
} else {
    // Normale Cache-Einstellungen
    $cacheTime = 3600; // 1 Stunde
    header("Cache-Control: max-age=$cacheTime, public");
    header("Expires: " . gmdate("D, d M Y H:i:s", time() + $cacheTime) . " GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s", time()) . " GMT");
}

// Aktiviere Komprimierung
if(extension_loaded('brotli')) {
    ini_set('zlib.output_compression', 'Off');
    ob_start('ob_gzhandler');
} else if(extension_loaded('zlib')) {
    ini_set('zlib.output_compression', 'On');
    ini_set('zlib.output_compression_level', '5');
}

// Standard-Einstellungen
$settings = [
    'autostart' => true,
    'fullscreen' => false,
    'repeat' => true  // Standardmäßig wiederholen
];

// Lade gespeicherte Einstellungen, wenn verfügbar
$settingsFile = 'assets/werbung-settings.json';
if (file_exists($settingsFile)) {
    $savedSettings = json_decode(file_get_contents($settingsFile), true);
    if (is_array($savedSettings)) {
        $settings = array_merge($settings, $savedSettings);
    }
}

// Lese alle Bilder aus dem Werbeordner
$werbeDir = 'assets/pictures/';
$erlaubteFormate = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

$bilder = [];
$bilderInfos = []; // Neue Variable für detaillierte Bildinformationen

// Protokollierung für Debugging aktivieren
$debug = isset($_GET['debug']) && $_GET['debug'] === '1';
$debugInfo = "Advertisement Images Analysis:\n";
$debugInfo .= "Search Directory: $werbeDir\n";

if (is_dir($werbeDir)) {
    $debugInfo .= "Directory exists.\n";
    if ($handle = opendir($werbeDir)) {
        $debugInfo .= "Directory opened successfully.\n";
        $bilderAnzahl = 0;
        
        while (($file = readdir($handle)) !== false) {
            // Ignoriere . und .. Verzeichnisse
            if ($file == '.' || $file == '..') {
                continue;
            }
            
            // Prüfe Dateierweiterung
            $fileInfo = pathinfo($file);
            if (isset($fileInfo['extension']) && in_array(strtolower($fileInfo['extension']), $erlaubteFormate)) {
                // Füge Bild zur Liste hinzu
                $bildPfad = $werbeDir . $file;
                $bilder[] = $bildPfad;
                
                // Sammle zusätzliche Informationen über das Bild
                $bilderInfos[] = [
                    'pfad' => $bildPfad,
                    'name' => $fileInfo['filename'],
                    'erweiterung' => strtolower($fileInfo['extension']),
                    'groesse' => file_exists($bildPfad) ? filesize($bildPfad) : 0,
                    'datum' => file_exists($bildPfad) ? filemtime($bildPfad) : 0
                ];
                
                $bilderAnzahl++;
            }
        }
        closedir($handle);
        
        $debugInfo .= "Found Images: $bilderAnzahl\n";
        if ($bilderAnzahl > 0) {
            $debugInfo .= "Image List:\n";
            foreach ($bilderInfos as $info) {
                $debugInfo .= "- {$info['name']}.{$info['erweiterung']} ({$info['groesse']} Bytes)\n";
            }
        }
    } else {
        $debugInfo .= "Error: Could not open directory.\n";
    }
} else {
    $debugInfo .= "Error: Directory does not exist.\n";
}

// Sortiere die Bilder (optional)
sort($bilder);

// Fallback Bild falls keine gefunden wurden
if (empty($bilder)) {
    $debugInfo .= "No images found. Creating test images.\n";
    // Mehrere Test-Bilder hinzufügen (als Base64 SVGs)
    $colors = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6'];
    $texts = ['Example Image 1', 'Example Image 2', 'Test Image 3', 'Demo 4', 'Sample 5'];
    
    for ($i = 0; $i < 5; $i++) {
        $color = $colors[$i];
        $text = $texts[$i];
        $bilder[] = 'data:image/svg+xml;base64,' . base64_encode('<svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="' . $color . '"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="Arial, sans-serif" font-size="24px" fill="#fff">' . $text . '</text></svg>');
        
        // Auch für die Bilder-Infos
        $bilderInfos[] = [
            'pfad' => "Test-SVG-$i",
            'name' => "Test-Image-$i",
            'erweiterung' => 'svg',
            'groesse' => 0,
            'datum' => time()
        ];
    }
}

// Wähle ein Anzeige-Modus - Bevorzuge Session-Einstellungen vor URL-Parametern 
$displayMode = isset($_GET['mode']) ? $_GET['mode'] : 'slide';
$animationType = isset($_GET['animation']) ? $_GET['animation'] : 'slide-horizontal';
$interval = isset($_GET['interval']) ? max(1000, min(120000, (int)$_GET['interval'])) : 5000;
$transitionTime = isset($_GET['transition']) ? max(200, min(3000, (int)$_GET['transition'])) : 1000;
$showUI = isset($_GET['ui']) && $_GET['ui'] === '1';

// Überschreibe mit Session-Daten, wenn verfügbar
if (isset($_SESSION['werbung_preview_settings'])) {
    $sessionSettings = $_SESSION['werbung_preview_settings'];
    
    if (isset($sessionSettings['mode'])) {
        $displayMode = $sessionSettings['mode'];
    }
    
    if (isset($sessionSettings['animation'])) {
        $animationType = $sessionSettings['animation'];
    }
    
    if (isset($sessionSettings['interval'])) {
        $interval = max(1000, min(120000, (int)$sessionSettings['interval']));
    }
    
    if (isset($sessionSettings['transition'])) {
        $transitionTime = max(200, min(3000, (int)$sessionSettings['transition']));
    }
    
    if (isset($sessionSettings['ui'])) {
        $showUI = $sessionSettings['ui'] === '1';
    }
    
    // Session-Einstellungen nur einmal verwenden
    unset($_SESSION['werbung_preview_settings']);
}

$validModes = ['fade', 'slide', 'classic'];
if (!in_array($displayMode, $validModes)) {
    $displayMode = 'slide';
}

// Zufälliges Bild auswählen oder Parameter nutzen
$bildIndex = 0;
// Nur wenn explizit ein Bild angefordert wurde UND wir nicht im Autostart-Modus sind
if (isset($_GET['id']) && is_numeric($_GET['id']) && $_GET['id'] >= 0 && $_GET['id'] < count($bilder) && 
    !(isset($_GET['autostart']) && $_GET['autostart'] === '1')) {
    $bildIndex = (int)$_GET['id'];
} else if (count($bilder) > 1) {
    $bildIndex = mt_rand(0, count($bilder) - 1);
}

$aktiverHintergrund = $bilder[$bildIndex];
$nextIndex = ($bildIndex + 1) % count($bilder);

// Read resolution from URL
$defaultResolution = '1920x1080';
$resolution = isset($_GET['resolution']) ? $_GET['resolution'] : $defaultResolution;
list($resolutionWidth, $resolutionHeight) = explode('x', $resolution);

// Apply resolution to styles
$style = "width: {$resolutionWidth}px; height: {$resolutionHeight}px;";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Werbung</title>
    <style>
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: local('Poppins Regular'), local('Poppins-Regular'), 
                 url('/assets/fonts/poppins-regular.woff2') format('woff2');
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            background-color: #000;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            overflow: hidden;
            height: 100vh;
            width: 100vw;
            position: relative;
        }
        
        /* Classic Mode Styling */
        .background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url('<?php echo htmlspecialchars($aktiverHintergrund); ?>');
            background-position: center;
            background-size: contain;
            background-repeat: no-repeat;
            transition: opacity <?php echo $transitionTime/1000; ?>s ease-in-out;
            z-index: 1;
        }
        
        /* Fade Mode Styling */
        .slideshow-container {
            position: relative;
            width: 100vw;
            height: 100vh;
        }
        
        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            opacity: 0;
            transition: opacity <?php echo $transitionTime/1000; ?>s ease-in-out;
            /* outline: 2px dashed rgba(255, 255, 255, 0.5); */
        }
        
        .slide.active {
            opacity: 1;
        }
.fade-mode .slide.previous {
    opacity: 0;
}
        
        /* Slide Mode Styling */
        .slide-mode .slide {
            opacity: 1;
            left: 100vw; /* Initial rechts positioniert */
            top: 0;
            transition: left <?php echo $transitionTime/1000; ?>s ease-in-out;
        }
        
        .slide-mode .slide.active {
            left: 0; /* Aktiver Slide ist in der Mitte */
        }
        
        .slide-mode .slide.previous {
            left: -100vw; /* Vorheriger Slide wird nach links geschoben */
        }
        
        /* Erweiterte Animationsoptionen */
        /* Vertikaler Slide */
        .slide-vertical-mode .slide {
            opacity: 1;
            top: 100vh; /* Initial unten positioniert */
            left: 0;
            transition: top <?php echo $transitionTime/1000; ?>s ease-in-out;
        }
        
        .slide-vertical-mode .slide.active {
            top: 0; /* Aktiver Slide ist in der Mitte */
        }
        
        .slide-vertical-mode .slide.previous {
            top: -100vh; /* Vorheriger Slide wird nach oben geschoben */
        }
        
        .zoom-in-mode .slide {
            opacity: 0;
            transform: scale(0.5);
            transition: opacity <?php echo $transitionTime/1000; ?>s ease-out, 
                        transform <?php echo $transitionTime/1000; ?>s ease-out;
        }
        
        .zoom-in-mode .slide.active {
            opacity: 1;
            transform: scale(1);
        }
        
        .zoom-in-mode .slide.previous {
            opacity: 0;
            transform: scale(1.5);
        }
        
        /* Zoom Out */
        .zoom-out-mode .slide {
            opacity: 0;
            transform: scale(1.5);
            transition: opacity <?php echo $transitionTime/1000; ?>s ease-out, 
                       transform <?php echo $transitionTime/1000; ?>s ease-out;
        }
        
        .zoom-out-mode .slide.active {
            opacity: 1;
            transform: scale(1);
        }
        
        .zoom-out-mode .slide.previous {
            opacity: 0;
        }
        
        /* 3D Flip */
        .flip-mode .slideshow-container {
            perspective: 1000px;
        }
        
        .flip-mode .slide {
            opacity: 0;
            transform: rotateY(90deg);
            transform-origin: center center;
            transition: opacity <?php echo $transitionTime/1000; ?>s ease-in-out,
                        transform <?php echo $transitionTime/1000; ?>s ease-in-out;
            backface-visibility: hidden;
        }
        
        .flip-mode .slide.active {
            opacity: 1;
            transform: rotateY(0deg);
        }
        
        .flip-mode .slide.previous {
            opacity: 0;
            transform: rotateY(-90deg);
        }
        
        /* Rotation */
        .rotate-mode .slide {
            opacity: 0;
            transform: rotate(180deg) scale(0.5);
            transition: opacity <?php echo $transitionTime/1000; ?>s ease-out, 
                       transform <?php echo $transitionTime/1000; ?>s ease-out;
        }
        
        .rotate-mode .slide.active {
            opacity: 1;
            transform: rotate(0deg) scale(1);
        }
        
        /* Unschärfe-Übergang */
        .blur-mode .slide {
            opacity: 0;
            filter: blur(20px);
            transition: opacity <?php echo $transitionTime/1000; ?>s ease-out, 
                       filter <?php echo $transitionTime/1000; ?>s ease-out;
        }
        
        .blur-mode .slide.active {
            opacity: 1;
            filter: blur(0);
        }
        
        /* 3D Würfel */
        .cube-mode .slideshow-container {
            perspective: 1000px;
            transform-style: preserve-3d;
        }
        
        .cube-mode .slide {
            opacity: 1;
            transform: rotateY(90deg) translateZ(50vw);
            transition: transform <?php echo $transitionTime/1000; ?>s ease-in-out;
            backface-visibility: hidden;
        }
        
        .cube-mode .slide.active {
            transform: rotateY(0deg) translateZ(0);
        }
        
        .cube-mode .slide.previous {
            transform: rotateY(-90deg) translateZ(50vw);
        }
        
        /* UI Controls */
        .controls {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 20px;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            gap: 20px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 10;
            <?php if (!$showUI): ?>display: none !important;<?php endif; ?>
        }
        
        body:hover .controls {
            <?php if ($showUI): ?>opacity: 1;<?php endif; ?>
        }
        
        .control-button {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            transition: background-color 0.3s;
        }
        
        .control-button:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .fullscreen-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 10;
            <?php if (!$showUI): ?>display: none !important;<?php endif; ?>
        }
        
        body:hover .fullscreen-button {
            <?php if ($showUI): ?>opacity: 1;<?php endif; ?>
        }
        
        .image-counter {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(0,0,0,0.5);
            color: white;
            padding: 5px 10px;
            border-radius: 10px;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 10;
            <?php if (!$showUI): ?>display: none !important;<?php endif; ?>
        }
        
        body:hover .image-counter {
            <?php if ($showUI): ?>opacity: 1;<?php endif; ?>
        }
        
        .mode-selector {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.5);
            color: white;
            padding: 5px 10px;
            border-radius: 10px;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 10;
            display: flex;
            gap: 10px;
            <?php if (!$showUI): ?>display: none !important;<?php endif; ?>
        }
        
        body:hover .mode-selector {
            <?php if ($showUI): ?>opacity: 1;<?php endif; ?>
        }
        
        .mode-option {
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 5px;
            background: rgba(255,255,255,0.1);
        }
        
        .mode-option.active {
            background: rgba(255,255,255,0.3);
        }

        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
                align-items: center;
                padding: 10px;
                gap: 10px;
            }
            
            .control-button {
                width: 100%;
                max-width: 300px;
            }
            
            .mode-selector {
                flex-direction: column;
                align-items: center;
                padding: 10px;
                gap: 5px;
            }
        }
    </style>
</head>
<body class="<?php 
    // Bestimme den CSS-Klassen-Modus basierend auf animation und displayMode
    $bodyClass = $displayMode;
    if (isset($_GET['animation'])) {
        switch ($_GET['animation']) {
            case 'slide-horizontal':
                $bodyClass = 'slide';
                break;
            case 'slide-vertical':
                $bodyClass = 'slide-vertical';
                break;
            case 'fade':
                $bodyClass = 'fade';
                break;
            case 'zoom-in':
                $bodyClass = 'zoom-in';
                break;
            case 'zoom-out':
                $bodyClass = 'zoom-out';
                break;
            case 'flip':
                $bodyClass = 'flip';
                break;
            case 'rotate':
                $bodyClass = 'rotate';
                break;
            case 'blur':
                $bodyClass = 'blur';
                break;
            case 'cube':
                $bodyClass = 'cube';
                break;
            default:
                $bodyClass = $displayMode;
        }
    }
    echo $bodyClass;
?>-mode" style="<?php echo $style; ?>">
    
    <?php if ($displayMode === 'classic'): ?>
    <div class="background" id="background"></div>
    <?php else: ?>
    <div class="slideshow-container">
        <div class="slide" id="slide1" style="background-image: url('<?php echo htmlspecialchars($bilder[$bildIndex]); ?>')"></div>
        <div class="slide" id="slide2"></div>
    </div>
    <?php endif; ?>
    
    <button class="fullscreen-button" id="fullscreenButton">⛶</button>
    
    <div class="image-counter">
        Image <span id="currentImageNumber"><?php echo ($bildIndex + 1); ?></span> of <?php echo count($bilder); ?>
    </div>
    
    <div class="mode-selector">
        <div class="mode-option <?php echo $displayMode === 'fade' ? 'active' : ''; ?>" data-mode="fade">Fade</div>
        <div class="mode-option <?php echo $animationType === 'slide-horizontal' && $displayMode === 'slide' ? 'active' : ''; ?>" data-mode="slide" data-animation="slide-horizontal">H-Slide</div>
        <div class="mode-option <?php echo $animationType === 'slide-vertical' && $displayMode === 'slide' ? 'active' : ''; ?>" data-mode="slide" data-animation="slide-vertical">V-Slide</div>
        <div class="mode-option <?php echo $displayMode === 'classic' ? 'active' : ''; ?>" data-mode="classic">Classic</div>
    </div>
    
    <div class="controls">
        <?php if (count($bilder) > 1): ?>
            <button class="control-button" id="prevButton">Previous Image</button>
            <button class="control-button" id="slideshowButton">Start Slideshow</button>
            <button class="control-button" id="nextButton">Next Image</button>
        <?php endif; ?>
    </div>

    <script>
        // Globale Variablen mit detaillierten Bildinformationen
        const images = <?php echo json_encode($bilder); ?>;
        const imageInfo = <?php echo json_encode($bilderInfos); ?>;
        let currentIndex = <?php echo $bildIndex; ?>;
        let displayMode = "<?php echo $displayMode; ?>";
        let animationType = "<?php echo $animationType; ?>";
        let isSlideshow = false;
        let slideshowInterval;
        let isSlide1Active = true;
        const slideshowDelay = <?php echo $interval; ?>;
        const resolutionWidth = <?php echo (int)$resolutionWidth; ?>;
        const resolutionHeight = <?php echo (int)$resolutionHeight; ?>;
        const transitionTime = <?php echo $transitionTime; ?>;
        // Wiederholungsoption aus URL oder Standardeinstellung
        const isRepeat = <?php echo (isset($_GET['repeat']) && $_GET['repeat'] === '0') ? 'false' : ((isset($_GET['repeat']) && $_GET['repeat'] === '1') || (isset($sessionSettings['repeat']) && $sessionSettings['repeat'] === '1') || $settings['repeat'] ? 'true' : 'false'); ?>;
        
        // Debug-Infos im Konsolenlogs
        console.log(`Advertisement.php initialized with ${images.length} images`);
        console.log(`Animation: ${animationType}, Mode: ${displayMode}`);
        images.forEach((img, idx) => console.log(`Image ${idx + 1}: ${img}`));
        
        // DOM-Elemente
        const slideshowButton = document.getElementById('slideshowButton');
        const fullscreenButton = document.getElementById('fullscreenButton');
        const prevButton = document.getElementById('prevButton');
        const nextButton = document.getElementById('nextButton');
        const currentImageNumber = document.getElementById('currentImageNumber');
        const modeOptions = document.querySelectorAll('.mode-option');
        
        // Elemente je nach Modus
        const background = document.getElementById('background');
        const slide1 = document.getElementById('slide1');
        const slide2 = document.getElementById('slide2');
        
        // Hilfsfunktion, um einen Slide für die Animation vorzubereiten
        function prepareSlideForAnimation(slide, mode) {
            slide.style.transition = 'none';
            
            console.log(`Preparing slide for animation type '${mode}'`);
            
            switch (mode) {
            case 'slide-horizontal':
                    slide.style.left = '100vw';
                    slide.style.top = '0';
                    break;
            case 'slide-vertical':
                    slide.style.top = resolutionHeight + 'px';
                    slide.style.left = '0';
                    break;
            case 'flip':
                    slide.style.transform = 'rotateY(90deg)';
                    slide.style.opacity = '0';
                    break;
                case 'cube':
                    slide.style.transform = 'rotateY(90deg) translateZ(50vw)';
                    break;
                case 'rotate':
                    slide.style.transform = 'rotate(180deg) scale(0.5)';
                    break;
                case 'zoom-in':
                    slide.style.transform = 'scale(0.5)';
                    slide.style.opacity = '0';
                    break;
                case 'zoom-out':
                    slide.style.transform = 'scale(1.5)';
                    slide.style.opacity = '0';
                    break;
                case 'blur':
                    slide.style.filter = 'blur(20px)';
                    slide.style.opacity = '0';
                    break;
                case 'fade':
                    slide.style.opacity = '0';
                    break;
            }
            
            // Force reflow
            void slide.offsetHeight;
            slide.style.transition = '';
            console.log(`Slide prepared for animation '${mode}'`);
            
            // Debugging der Positionsdaten
            if (mode === 'slide-horizontal') {
                console.log(`Slide prepared (horizontal): left=${slide.style.left}, top=${slide.style.top}`);
            } else if (mode === 'slide-vertical') {
                console.log(`Slide prepared (vertical): top=${slide.style.top}, left=${slide.style.left}`);
            } else {
                console.log(`Slide transformations: opacity=${slide.style.opacity}, transform=${slide.style.transform}, filter=${slide.style.filter}`);
            }
        }
        
        // Prüfen, ob notwendige DOM-Elemente verfügbar sind
        const checkDomElements = () => {
            if (displayMode === 'classic' && !background) {
                console.error('Background element not found in Classic mode!');
                return false;
            }
            
            if (displayMode !== 'classic' && (!slide1 || !slide2)) {
                console.error('Slide elements not found in Fade/Slide mode!');
                return false;
            }
            
            return true;
        };
        
        // Initial prüfen
        const domElementsAvailable = checkDomElements();
        console.log(`DOM Elements Check: ${domElementsAvailable ? 'OK' : 'ERROR'}`);
        
        // Initial den ersten Slide aktiv machen
        if (displayMode !== 'classic' && slide1) {
            slide1.classList.add('active');
            console.log(`Initial slide active: slide1 with background ${slide1.style.backgroundImage}`);
        }
        
        // Hilfsfunktion zum Loggen des Slide-Status
        function logSlideStatus(message = "Current Slide Status") {
            if (!slide1 || !slide2) return;
            
            const slide1Status = {
                isActive: slide1.classList.contains('active'),
                isPrevious: slide1.classList.contains('previous'),
                left: slide1.style.left,
                top: slide1.style.top,
                opacity: slide1.style.opacity,
                transform: slide1.style.transform,
                backgroundImage: slide1.style.backgroundImage
            };
            
            const slide2Status = {
                isActive: slide2.classList.contains('active'),
                isPrevious: slide2.classList.contains('previous'),
                left: slide2.style.left,
                top: slide2.style.top,
                opacity: slide2.style.opacity,
                transform: slide2.style.transform,
                backgroundImage: slide2.style.backgroundImage
            };
            
            console.log(`--- ${message} ---`);
            console.log(`Slide1: active=${slide1Status.isActive}, previous=${slide1Status.isPrevious}, left=${slide1Status.left}, top=${slide1Status.top}`);
            console.log(`Slide2: active=${slide2Status.isActive}, previous=${slide2Status.isPrevious}, left=${slide2Status.left}, top=${slide2Status.top}`);
            const debugOverlay1 = document.getElementById('debug-slide1');
            const debugOverlay2 = document.getElementById('debug-slide2');
            if (debugOverlay1 && debugOverlay2) {
                debugOverlay1.textContent = `Slide1: left=${slide1Status.left}, top=${slide1Status.top}, opacity=${slide1Status.opacity}, active=${slide1Status.isActive}`;
                debugOverlay2.textContent = `Slide2: left=${slide2Status.left}, top=${slide2Status.top}, opacity=${slide2Status.opacity}, active=${slide2Status.isActive}`;
            }
        }
        
        // Funktion zum Ändern des Hintergrundbildes im Classic-Modus
        function changeBackgroundClassic(index) {
            if (!background) return;
            
            // Debug-Info vor dem Wechsel
            console.log(`Classic Mode Image Change: Current Display=${background.style.backgroundImage}`);
            
            // Alte Hintergrund ausblenden
            background.style.opacity = 0;
            
            // Nach der Überblendung das neue Bild setzen
            setTimeout(() => {
                background.style.backgroundImage = `url('${images[index]}')`;
                background.style.opacity = 1;
                console.log(`Classic Mode: New image set ${images[index]}`);
                updateImageCounter(index);
            }, transitionTime / 2);
        }
        
        // Prüfe, ob wir nach dem letzten Bild sind und keine Bilder angezeigt werden
        function checkCurrentImage() {
            // Wenn keine Bilder vorhanden sind
            if (!images || images.length === 0) {
                console.error("No images found!");
                return false;
            }
            
            // Wenn der aktuelle Index außerhalb des gültigen Bereichs liegt
            if (currentIndex < 0 || currentIndex >= images.length) {
                console.log(`Invalid index: ${currentIndex}, setting to 0`);
                currentIndex = 0;
            }
            
            try {
                // Aktualisiere das Bild basierend auf dem Anzeigemodus
                if (displayMode === 'classic') {
                    if (background) {
                        background.style.backgroundImage = `url('${images[currentIndex]}')`;
                        background.style.opacity = 1;
                        console.log(`Classic Mode: Image ${currentIndex + 1} set`);
                    } else {
                        console.error("Background element not found");
                        return false;
                    }
                } else {
                    const activeSlide = isSlide1Active ? slide1 : slide2;
                    if (activeSlide) {
                        activeSlide.style.backgroundImage = `url('${images[currentIndex]}')`;
                        console.log(`${displayMode} Mode: Image ${currentIndex + 1} set on ${isSlide1Active ? 'slide1' : 'slide2'}`);
                        
                        // Status der Slides protokollieren
                        logSlideStatus("Status when setting image in checkCurrentImage");
                    } else {
                        console.error("Active slide not found");
                        return false;
                    }
                }
                
                updateImageCounter(currentIndex);
                return true;
            } catch (error) {
                console.error(`Error setting image: ${error.message}`);
                return false;
            }
        }
        
        // Funktion zum Ändern des Bildes im Fade/Slide-Modus
        function changeBackgroundModern(index) {
            if (!slide1 || !slide2 || index < 0 || index >= images.length) {
                console.error(`Fehler in changeBackgroundModern: slide1=${!!slide1}, slide2=${!!slide2}, index=${index}, images.length=${images.length}`);
                return;
            }

            const activeSlide = isSlide1Active ? slide1 : slide2;
            const inactiveSlide = isSlide1Active ? slide2 : slide1;

            // Neues Bild setzen
            inactiveSlide.style.backgroundImage = `url('${images[index]}')`;

            // Vorbereitung für den Start
            inactiveSlide.classList.remove('active', 'previous');
            activeSlide.classList.remove('previous');

    // Position zurücksetzen
    prepareSlideForAnimation(inactiveSlide, animationType);
 
    // Reflow erzwingen
    void inactiveSlide.offsetHeight;
 
    // Klassen setzen
    inactiveSlide.classList.add('active');
    // Aktiver Slide soll sichtbar sein
    inactiveSlide.style.left = '0px';
    inactiveSlide.style.top = '0px';
    inactiveSlide.style.opacity = '1';
    activeSlide.classList.remove('active');
    activeSlide.classList.add('previous');
    if (animationType === 'zoom-in') {
        activeSlide.style.opacity = '0';
    } else if (animationType === 'zoom-out') {
        activeSlide.style.opacity = '0';
        activeSlide.style.transform = 'scale(1.5)';
    } else if (animationType === 'flip') {
        activeSlide.style.opacity = '0';
        activeSlide.style.transform = 'rotateY(-90deg)';
    }

    // Logging
    console.log(`Wechsle Bild auf Index ${index}, Slide1 ist jetzt ${!isSlide1Active ? 'aktiv' : 'passiv'}`);
    logSlideStatus("Status nach Animation-Start");
 
    isSlide1Active = !isSlide1Active;
    updateImageCounter(index);
 
    // Slide nach links rausfahren und dann zurücksetzen
    if (animationType === 'slide-vertical') {
        activeSlide.style.transition = `top ${transitionTime / 1000}s ease-in-out, opacity ${transitionTime / 1000}s ease-in-out`;
        activeSlide.style.top = '-100vh';
        activeSlide.style.opacity = '0';
    } else if (animationType === 'slide-horizontal') {
        activeSlide.style.transition = `left ${transitionTime / 1000}s ease-in-out, opacity ${transitionTime / 1000}s ease-in-out`;
        activeSlide.style.left = '-100vw';
        activeSlide.style.opacity = '0';
    } else {
        activeSlide.style.transition = `opacity ${transitionTime / 1000}s ease-in-out`;
        activeSlide.style.opacity = '0';
    }

    setTimeout(() => {
        activeSlide.classList.remove('previous');
        activeSlide.style.transition = 'none';
        if (animationType === 'slide-vertical') {
            activeSlide.style.top = '100vh';
        } else if (animationType === 'slide-horizontal') {
            activeSlide.style.left = '100vw';
        }
        activeSlide.style.opacity = '1';
        void activeSlide.offsetWidth;
        activeSlide.style.transition = '';
        console.log("previous-Klasse entfernt und Slide zurückgesetzt (rechts, sichtbar)");
        logSlideStatus("Status nach Reset");
    }, transitionTime);

    // Fade-Animation separat behandeln
    if (animationType === 'fade') {
        inactiveSlide.style.opacity = '0';
        inactiveSlide.style.zIndex = '2';
        activeSlide.style.zIndex = '1';

        inactiveSlide.classList.add('active');
        void inactiveSlide.offsetWidth; // Reflow

        setTimeout(() => {
            inactiveSlide.style.opacity = '1';
        }, 50);

        setTimeout(() => {
            activeSlide.classList.remove('active');
            activeSlide.style.zIndex = '';
            activeSlide.style.opacity = '';
        }, transitionTime);
    }
    }
        
        // Hilfsfunktion zum Ermitteln der Body-Klasse basierend auf dem Animationstyp
        function getBodyClass(animationType) {
            return `${animationType}-mode`;
        }

        // Anzeigemodus ändern
        function changeDisplayMode(newMode, newAnimation) {
            // Aktuellen Modus entfernen
            document.body.classList.remove(getBodyClass(animationType));
            
            // Status vor dem Wechsel loggen
            console.log(`Modus-Wechsel von ${displayMode} nach ${newMode}, Animation: ${newAnimation || animationType}`);
            logSlideStatus(`Status VOR Moduswechsel von ${displayMode} nach ${newMode}`);
            
            // Neuen Modus setzen
            displayMode = newMode;
            
            // Setze Animation, wenn angegeben
            if (newAnimation) {
                animationType = newAnimation;
            }
            
            // Neue Klasse hinzufügen
            document.body.classList.add(getBodyClass(animationType));
            
            // Modus-Buttons aktualisieren
            modeOptions.forEach(option => {
                const optionMode = option.dataset.mode;
                const optionAnimation = option.dataset.animation;
                
                if (optionMode === newMode && (!optionAnimation || optionAnimation === animationType)) {
                    option.classList.add('active');
                } else {
                    option.classList.remove('active');
                }
            });
            
            // Aktuelles URL-Objekt erstellen, um vorhandene Parameter zu behalten
            const url = new URL(window.location.href);
            
            // Modus-Parameter aktualisieren
            url.searchParams.set('mode', newMode);
            
            // Animation entsprechend updaten
            if (newAnimation) {
                url.searchParams.set('animation', newAnimation);
            } else if (newMode === 'slide') {
                url.searchParams.set('animation', 'slide-horizontal');
            } else {
                url.searchParams.set('animation', newMode);
            }
            
            // Bild-ID entfernen, um nicht einzelne Bilder zu fixieren
            url.searchParams.delete('id');
            
            // Seite mit beibehaltenen Parametern neu laden
            window.location.href = url.toString();
        }
        
        // Hilfsfunktion für Counter-Update
        function updateImageCounter(index) {
            if (currentImageNumber) {
                currentImageNumber.textContent = index + 1;
            }
        }
        
        // Vorheriges Bild anzeigen
        function prevImage() {
            return changeImage(currentIndex - 1);
        }
        
        // Nächstes Bild anzeigen
        function nextImage() {
            return changeImage(currentIndex + 1);
        }
        
        // Allgemeine Funktion zum Bildwechsel
        function changeImage(index) {
            console.log(`changeImage(${index}) called`);
        
            if (!images || images.length === 0) return false;
        
            if (isRepeat) {
                index = (index + images.length) % images.length;
            } else {
                if (index < 0 || index >= images.length) return false;
            }
        
            if (index === currentIndex) return true;
        
            currentIndex = index;
        
            if (displayMode === 'classic') {
                changeBackgroundClassic(currentIndex);
            } else {
                changeBackgroundModern(currentIndex);
            }
        
            return true;
        }
        
        // Diashow starten/stoppen
        function toggleSlideshow() {
            isSlideshow = !isSlideshow;
            console.log(`Slideshow ${isSlideshow ? 'started' : 'stopped'}`);
            
            // Aktualisieren der UI
            if (slideshowButton) {
                slideshowButton.textContent = isSlideshow ? 'Stop Slideshow' : 'Start Slideshow';
            }
            
            if (isSlideshow) {
                // Starte die Slideshow
                try {
                    // Sicherstellen, dass mindestens 2 Bilder vorhanden sind für eine sinnvolle Diashow
                    if (images.length < 2) {
                        console.warn("Less than 2 images available - Slideshow might be unnecessary");
                    }
                    
                    // Sicherstellen, dass wir bei Repeat-Modus am Ende nicht stehen bleiben
                    if (isRepeat && currentIndex >= images.length - 1) {
                        console.log("At the end of image list - resetting to beginning");
                        currentIndex = -1; // Will be set to 0 in next nextImage()
                    }
                    
                    // Interval für automatischen Bildwechsel
                    slideshowInterval = setInterval(() => {
                        // Wenn wir am Ende sind und Repeat aktiv ist, explizit zum ersten Bild springen
                        if (isRepeat && currentIndex >= images.length - 1) {
                            console.log("Last image reached with repeat - jumping to beginning");
                            currentIndex = -1; // Will be set to 0 in nextImage()
                        }
                        
                        const result = nextImage();
                        
                        // Bei Fehlern oder wenn wir am Ende sind und kein Repeat aktiv ist
                        if (!result && isSlideshow) {
                            console.error("Image change failed, stopping slideshow");
                            toggleSlideshow();
                        }
                    }, slideshowDelay);
                    
                    console.log(`Slideshow interval set: ${slideshowDelay}ms`);
                } catch (error) {
                    console.error(`Error starting slideshow: ${error.message}`);
                    isSlideshow = false;
                    if (slideshowButton) {
                        slideshowButton.textContent = 'Start Slideshow';
                    }
                }
            } else {
                // Stoppe die Slideshow
                if (slideshowInterval) {
                    clearInterval(slideshowInterval);
                    console.log("Slideshow interval cleared");
                }
            }
        }
        

        
        // Automatisch starten nach dem Laden
        document.addEventListener('DOMContentLoaded', () => {
            // Anfänglichen Zustand prüfen
            console.log("DOM loaded, performing initializations");
            
            // Prüfe, ob der Bilderordner existiert und Bilder enthält
            const testImg = new Image();
            testImg.onload = () => console.log("Image directory loaded successfully");
            testImg.onerror = () => console.error("Image directory could NOT be loaded");
            testImg.src = "assets/pictures/";
            
            // Lade alle Bilder im Voraus, um zu sehen, welche funktionieren
            images.forEach((img, idx) => {
                const testImg = new Image();
                testImg.onload = () => console.log(`Image ${idx}: ${img} loaded successfully`);
                testImg.onerror = () => console.error(`Image ${idx}: ${img} could NOT be loaded`);
                testImg.src = img;
            });
            
            // Prüfe, ob DOM-Elemente verfügbar sind
            if (!checkDomElements()) {
                console.error("Critical DOM elements missing - limited functionality");
            }
            
            // Prüfen, ob es URL-Parameter für Autostart gibt
            const autostart = <?php echo (isset($_GET['autostart']) && $_GET['autostart'] === '1') || (isset($sessionSettings['autostart']) && $sessionSettings['autostart'] === '1') || $settings['autostart'] ? 'true' : 'false'; ?>;
            
            // Automatisch Vollbild starten wenn gewünscht
            const autofullscreen = <?php echo (isset($_GET['fullscreen']) && $_GET['fullscreen'] === '1') || (isset($sessionSettings['fullscreen']) && $sessionSettings['fullscreen'] === '1') || $settings['fullscreen'] ? 'true' : 'false'; ?>;
            
            // Bei Autostart die 'id' aus der URL entfernen, falls vorhanden
            if (autostart) {
                try {
                    const url = new URL(window.location.href);
                    if (url.searchParams.has('id')) {
                        console.log("Removing 'id' parameter from URL due to autostart");
                        url.searchParams.delete('id');
                        // Stille URL-Aktualisierung ohne Neuladen
                        window.history.replaceState({}, document.title, url.toString());
                    }
                } catch (error) {
                    console.error(`Error updating URL: ${error.message}`);
                }
            }
            
            // Sicherstellen, dass ein Bild angezeigt wird
            const imageCheck = checkCurrentImage();
            if (!imageCheck) {
                console.error("Initial image check failed");
            }
            
            // Automatisch Vollbild starten
            if (autofullscreen) {
                console.log("Starting fullscreen mode automatically");
                setTimeout(toggleFullscreen, 1000);
            }
            
            // Automatisch Diashow starten
            if (autostart && !isSlideshow) {
                console.log("Starting slideshow automatically");
                isSlideshow = true;
                if (slideshowButton) {
                    slideshowButton.textContent = 'Stop Slideshow';
                }
                slideshowInterval = setInterval(() => {
                    nextImage();
                }, slideshowDelay);
                console.log(`Slideshow interval set: ${slideshowDelay}ms`);
                nextImage(); // Start with next image
            }
        });
        
        // Vollbildmodus ein-/ausschalten
        function toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.error(`Error enabling fullscreen mode: ${err.message}`);
                });
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        }
        
        // Event-Listener
        if (slideshowButton) {
            slideshowButton.addEventListener('click', toggleSlideshow);
        }
        
        if (fullscreenButton) {
            fullscreenButton.addEventListener('click', toggleFullscreen);
        }
        
        if (prevButton) {
            prevButton.addEventListener('click', prevImage);
        }
        
        if (nextButton) {
            nextButton.addEventListener('click', nextImage);
        }
        
        // Modus-Auswahl
        modeOptions.forEach(option => {
            option.addEventListener('click', () => {
                changeDisplayMode(option.dataset.mode, option.dataset.animation);
            });
        });
        
        // Tastatursteuerung
        document.addEventListener('keydown', (e) => {
            switch(e.key) {
                case 'ArrowRight':
                    nextImage();
                    break;
                case 'ArrowLeft':
                    prevImage();
                    break;
                case ' ':
                    toggleSlideshow();
                    break;
                case 'f':
                    toggleFullscreen();
                    break;
                case 'Escape':
                    if (isSlideshow) {
                        toggleSlideshow();
                    }
                    break;
                case '1':
                    changeDisplayMode('fade', null);
                    break;
                case '2':
                    changeDisplayMode('slide', null);
                    break;
                case '3':
                    changeDisplayMode('classic', null);
                    break;
            }
        });
        
        // Beim Klick auf die Seite Vollbild aktivieren
        document.addEventListener('click', function(e) {
            // Nur wenn die UI aktiviert ist und der Klick nicht auf einen Button oder ein Bedienelement war
            if (<?php echo $showUI ? 'true' : 'false'; ?> && 
                !e.target.closest('.control-button') && 
                !e.target.closest('.fullscreen-button') && 
                !e.target.closest('.mode-option')) {
                toggleFullscreen();
            }
        });


    </script>
</body>
</html>
<?php
// Output-Buffering beenden und Ausgabe senden
ob_end_flush();
?> 