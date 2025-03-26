<?php
// Starte Session, um Einstellungen zu übertragen
session_start();

// Bestimme den aktiven Tab aus der Session oder als Standard "settings"
$activeTab = isset($_SESSION['active_tab']) ? $_SESSION['active_tab'] : 'settings';

// Tab-Wechsel über URL-Parameter verarbeiten
if (isset($_GET['set_tab']) && in_array($_GET['set_tab'], ['settings', 'help', 'images'])) {
    $_SESSION['active_tab'] = $_GET['set_tab'];
    
    // Wenn nur ein Tab-Wechsel angefordert wurde, Antwort senden und beenden
    if (!isset($_GET['mode']) && !isset($_POST['save_settings']) && !isset($_POST['upload_images']) && !isset($_POST['delete_image'])) {
        echo 'Tab set to: ' . $_SESSION['active_tab'];
        exit;
    }
}

// Übersetzungsfunktion
function translate($text) {
    static $translations = [
        'Werbung - Einstellungen' => ['en' => 'Advertisement - Settings'],
        'Einstellungen' => ['en' => 'Settings'],
        'Anleitung' => ['en' => 'Guide'],
        'Bildverwaltung' => ['en' => 'Image Management'],
        'Bilder hochladen' => ['en' => 'Upload Images'],
        'Bilder auswählen' => ['en' => 'Select Images'],
        'Hochladen' => ['en' => 'Upload'],
        'Vorhandene Bilder' => ['en' => 'Existing Images'],
        'Bild löschen' => ['en' => 'Delete Image'],
        'Bild erfolgreich gelöscht' => ['en' => 'Image successfully deleted'],
        'Fehler beim Löschen des Bildes' => ['en' => 'Error deleting image'],
        'Bilder erfolgreich hochgeladen' => ['en' => 'Images successfully uploaded'],
        'Fehler beim Hochladen der Bilder' => ['en' => 'Error uploading images'],
        'Nur Bilder im Format JPG, PNG, WEBP oder GIF sind erlaubt' => ['en' => 'Only images in JPG, PNG, WEBP or GIF format are allowed'],
        'Maximale Dateigröße: 10MB' => ['en' => 'Maximum file size: 10MB'],
        'Vorschau' => ['en' => 'Preview'],
        'Vorschau-Format' => ['en' => 'Preview Format'],
        'Animation' => ['en' => 'Animation'],
        'Anzeigedauer pro Bild' => ['en' => 'Display Duration per Image'],
        'Übergangszeit' => ['en' => 'Transition Time'],
        'Diashow automatisch starten' => ['en' => 'Start Slideshow Automatically'],
        'Automatisch Vollbild aktivieren' => ['en' => 'Enable Fullscreen Automatically'],
        'Diashow endlos wiederholen' => ['en' => 'Repeat Slideshow Endlessly'],
        'Ausrichtung' => ['en' => 'Orientation'],
        'Vertikal' => ['en' => 'Vertical'],
        'Bildschirmauflösung' => ['en' => 'Screen Resolution'],
        'Einstellungen speichern' => ['en' => 'Save Settings'],
        'Vorschau aktualisieren' => ['en' => 'Update Preview'],
        'Integration' => ['en' => 'Integration'],
        'Als iframe (empfohlen)' => ['en' => 'As iframe (recommended)'],
        'Als direkter Link' => ['en' => 'As direct link'],
        'Parameter-Optionen' => ['en' => 'Parameter Options'],
        'Beispiel' => ['en' => 'Example'],
        'Hilfe' => ['en' => 'Help'],
        'Tastatursteuerung' => ['en' => 'Keyboard Controls'],
        'Installation' => ['en' => 'Installation'],
        'Unterstützte Formate' => ['en' => 'Supported Formats'],
        'Aktuelle Formate' => ['en' => 'Current Formats'],
        'Geplante Formate' => ['en' => 'Planned Formats'],
        'Lizenz' => ['en' => 'License'],
        'Einstellungen wurden erfolgreich gespeichert' => ['en' => 'Settings saved successfully'],
        'Fehler beim Speichern der Einstellungen' => ['en' => 'Error saving settings'],
        'Millisekunden' => ['en' => 'Milliseconds'],
        'Sekunden' => ['en' => 'Seconds'],
        'Horizontal Slide (Links/Rechts)' => ['en' => 'Horizontal Slide (Left/Right)'],
        'Vertikal Slide (Oben/Unten)' => ['en' => 'Vertical Slide (Up/Down)'],
        'Überblenden' => ['en' => 'Fade'],
        'Konfiguration' => ['en' => 'Configuration'],
        'Verwendung' => ['en' => 'Usage'],
        'Beispiele' => ['en' => 'Examples'],
        'Tipps & Tricks' => ['en' => 'Tips & Tricks'],
        'Fehlerbehebung' => ['en' => 'Troubleshooting'],
        'Häufige Fragen' => ['en' => 'FAQ'],
        'Technische Details' => ['en' => 'Technical Details'],
        'API-Dokumentation' => ['en' => 'API Documentation'],
        'Entwickler-Guide' => ['en' => 'Developer Guide'],
        'Best Practices' => ['en' => 'Best Practices'],
        'Sicherheit' => ['en' => 'Security'],
        'Performance' => ['en' => 'Performance'],
        'Wartung' => ['en' => 'Maintenance'],
        'GIF-Wiederholungen' => ['en' => 'GIF Loops']
    ];

    // Bestimme die Sprache
    $lang = isset($_GET['lang']) ? $_GET['lang'] : 
           (isset($_SESSION['lang']) ? $_SESSION['lang'] : 
           (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : 'de'));

    // Speichere die Sprache in der Session
    $_SESSION['lang'] = $lang;

    // Wenn die Übersetzung existiert und die Sprache Englisch ist, gib die englische Version zurück
    if (isset($translations[$text]) && isset($translations[$text][$lang]) && $lang === 'en') {
        return $translations[$text][$lang];
    }

    // Ansonsten gib den Original-Text zurück
    return $text;
}

// Kurzform für die Übersetzungsfunktion
function t($text) {
    return translate($text);
}

// Starte Output-Buffering für bessere Performance
ob_start();

// Prüfe, ob Einstellungen gespeichert wurden
$message = '';
$messageType = '';

// Standard-Einstellungen
$defaultSettings = [
    'mode' => 'slide',
    'interval' => 5000,
    'transition' => 1000,
    'autostart' => true,
    'fullscreen' => false,
    'animation' => 'slide-horizontal',
    'repeat' => true,  // Neue Einstellung für Wiederholung
    'gif_loops' => 1   // Anzahl der GIF-Wiederholungen, bevor zum nächsten Bild gewechselt wird
];

// Pfad zur Einstellungsdatei
$settingsFile = 'assets/index-settings.json';

// Stelle sicher, dass das Verzeichnis existiert
if (!is_dir('assets')) {
    mkdir('assets', 0755, true);
}

// Lade aktuelle Einstellungen
$settings = $defaultSettings;
if (file_exists($settingsFile)) {
    $savedSettings = json_decode(file_get_contents($settingsFile), true);
    if (is_array($savedSettings)) {
        $settings = array_merge($defaultSettings, $savedSettings);
    }
}

// Stellen wir sicher, dass das Index-Verzeichnis existiert
$werbeDir = 'assets/pictures/';
if (!is_dir($werbeDir)) {
    mkdir($werbeDir, 0755, true);
}

// Verarbeite Formular-Übermittlung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    // Hole Einstellungen aus dem Formular
    $newSettings = [
        'mode' => isset($_POST['mode']) ? $_POST['mode'] : $defaultSettings['mode'],
        'interval' => isset($_POST['interval']) ? (int)$_POST['interval'] : $defaultSettings['interval'],
        'transition' => isset($_POST['transition']) ? (int)$_POST['transition'] : $defaultSettings['transition'],
        'autostart' => isset($_POST['autostart']),
        'fullscreen' => isset($_POST['fullscreen']),
        'animation' => isset($_POST['animation']) ? $_POST['animation'] : $defaultSettings['animation'],
        'repeat' => isset($_POST['repeat']),  // Neue Einstellung für Wiederholung
        'gif_loops' => isset($_POST['gif_loops']) ? (int)$_POST['gif_loops'] : $defaultSettings['gif_loops']
    ];
    
    // Konvertiere Sekunden in Millisekunden, wenn nötig
    if (isset($_POST['interval_unit']) && $_POST['interval_unit'] === 's') {
        $newSettings['interval'] = (int)($newSettings['interval'] * 1000);
    }
    
    if (isset($_POST['transition_unit']) && $_POST['transition_unit'] === 's') {
        $newSettings['transition'] = (int)($newSettings['transition'] * 1000);
    }
    
    // Validiere Einstellungen
    if ($newSettings['interval'] < 1000) {
        $newSettings['interval'] = 1000;
    } else if ($newSettings['interval'] > 60000) {
        $newSettings['interval'] = 60000;
    }
    
    if ($newSettings['transition'] < 200) {
        $newSettings['transition'] = 200;
    } else if ($newSettings['transition'] > 3000) {
        $newSettings['transition'] = 3000;
    }
    
    // Speichere Einstellungen
    if (file_put_contents($settingsFile, json_encode($newSettings, JSON_PRETTY_PRINT))) {
        $message = 'Einstellungen wurden erfolgreich gespeichert.';
        $messageType = 'success';
        $settings = $newSettings;
        
        // Speichere aktuelle Einstellungen in der Session
        $_SESSION['werbung_preview_settings'] = [
            'mode' => $settings['animation'] === 'fade' ? 'fade' : 
                    (in_array($settings['animation'], ['slide-horizontal', 'slide-vertical']) ? 'slide' : ''),
            'interval' => $settings['interval'],
            'transition' => $settings['transition'],
            'animation' => $settings['animation'],
            'ui' => '0', // UI in der Live-Version ausblenden
            'timestamp' => time() // Timestamp hinzufügen, um Cache zu umgehen
        ];
        
        // Behalte den aktiven Tab bei
        $_SESSION['active_tab'] = $activeTab;
        
        // Setze URL für Weiterleitung
        $redirectUrl = 'index.php';
    } else {
        $message = 'Fehler beim Speichern der Einstellungen. Bitte überprüfe die Schreibrechte.';
        $messageType = 'error';
    }
}

// Verarbeite Bild-Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_images'])) {
    $uploadDir = 'assets/pictures/';
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    $uploadSuccess = true;
    $uploadMessage = '';
    
    // Stelle sicher, dass das Upload-Verzeichnis existiert
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Verarbeite jede hochgeladene Datei
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        $fileName = $_FILES['images']['name'][$key];
        $fileType = $_FILES['images']['type'][$key];
        $fileSize = $_FILES['images']['size'][$key];
        
        // Validiere Dateityp und Größe
        if (!in_array($fileType, $allowedTypes)) {
            $uploadMessage .= "Nur Bilder im Format JPG, PNG, WEBP oder GIF sind erlaubt.\n";
            $uploadSuccess = false;
            continue;
        }
        
        if ($fileSize > $maxFileSize) {
            $uploadMessage .= "Maximale Dateigröße: 10MB\n";
            $uploadSuccess = false;
            continue;
        }
        
        // Generiere sicheren Dateinamen
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = uniqid() . '.' . $fileExtension;
        $targetPath = $uploadDir . $newFileName;
        
        // Versuche die Datei zu verschieben
        if (move_uploaded_file($tmp_name, $targetPath)) {
            $uploadMessage .= "Bild erfolgreich hochgeladen: $fileName\n";
        } else {
            $uploadMessage .= "Fehler beim Hochladen von: $fileName\n";
            $uploadSuccess = false;
        }
    }
    
    if ($uploadSuccess) {
        $message = 'Bilder erfolgreich hochgeladen';
        $messageType = 'success';
    } else {
        $message = 'Fehler beim Hochladen der Bilder';
        $messageType = 'error';
    }
    
    // Setze den aktiven Tab auf Bildverwaltung
    $_SESSION['active_tab'] = 'images';
}

// Verarbeite Bild-Löschung
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $imagePath = $_POST['image_path'];
    $uploadDir = 'assets/pictures/';
    
    // Sicherheitscheck: Stelle sicher, dass der Pfad im erlaubten Verzeichnis liegt
    if (strpos(realpath($imagePath), realpath($uploadDir)) === 0) {
        if (unlink($imagePath)) {
            $message = 'Bild erfolgreich gelöscht';
            $messageType = 'success';
        } else {
            $message = 'Fehler beim Löschen des Bildes';
            $messageType = 'error';
        }
    } else {
        $message = 'Ungültiger Bildpfad';
        $messageType = 'error';
    }
    
    // Setze den aktiven Tab auf Bildverwaltung
    $_SESSION['active_tab'] = 'images';
}

// Generiere die Vorschau-URL
$previewParams = http_build_query([
    'mode' => $settings['animation'] === 'fade' ? 'fade' : 
             (in_array($settings['animation'], ['slide-horizontal', 'slide-vertical']) ? 'slide' : ''),
    'interval' => $settings['interval'],
    'transition' => $settings['transition'],
    'ui' => '1', // UI immer anzeigen in der Vorschau
    'animation' => $settings['animation'],
    'gif_loops' => $settings['gif_loops'], // GIF-Wiederholungseinstellung
    'preview' => '1', // Markiere als Vorschau, um Cache zu vermeiden
    'ts' => time() // Timestamp hinzufügen, um Cache zu umgehen
]);
$previewUrl = "index.php?$previewParams";

// Verfügbare Animationen
$animationOptions = [
    'slide-horizontal' => 'Horizontal Slide (Links/Rechts)',
    'slide-vertical' => 'Vertikal Slide (Oben/Unten)',
    'fade' => 'Überblenden'
];

// Helper-Funktionen für die Anzeige der Zeitwerte
function getIntervalForDisplay($milliseconds) {
    // Wenn der Wert sauber durch 1000 teilbar ist, zeige Sekunden an
    if ($milliseconds % 1000 === 0 && $milliseconds >= 1000) {
        return [
            'value' => $milliseconds / 1000,
            'unit' => 's'
        ];
    }
    
    // Ansonsten zeige Millisekunden an
    return [
        'value' => $milliseconds,
        'unit' => 'ms'
    ];
}

function getTransitionForDisplay($milliseconds) {
    // Wenn der Wert sauber durch 1000 teilbar ist und >= 1s, zeige Sekunden an
    if ($milliseconds % 1000 === 0 && $milliseconds >= 1000) {
        return [
            'value' => $milliseconds / 1000,
            'unit' => 's'
        ];
    }
    
    // Ansonsten zeige Millisekunden an
    return [
        'value' => $milliseconds,
        'unit' => 'ms'
    ];
}

// Bereite die Werte für die Anzeige vor
$intervalDisplay = getIntervalForDisplay($settings['interval']);
$transitionDisplay = getTransitionForDisplay($settings['transition']);
?>
<!DOCTYPE html>
<html lang="<?php echo isset($_GET['lang']) && $_GET['lang'] === 'en' ? 'en' : 'de'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('Werbung - Einstellungen'); ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="data:image/png;base64,89504e470d0a1a0a0000000d4948445200000040000000400806000000aa6971de000000097048597300000b1300000b1301009a9c18000019f249444154789ced9b69985d5595f77f6bef73a7ba35670e904006421240084308848811684041100c0dd22d8a40185490b1a145d17e41096944785b195e115019df408b8043448818101286301343279084a49254556ab8d3397baffeb0cfad146ae531dd1ff8a0fb79ce93dc5b75cfddfb7fd6f05fffb54a5495bfe5653eec0d7cd8ebef007cd81bf8b0d7df3c00d1503ff8e3ef1701022882802828343717686e69a4bbb7c2faf77b2777f79446c5716c156f0430828a5844449d3aeb1315638c8a11753e31ea1510bcaa80604454011df4daa1620034fcae18d1c818872a0d59f12dcd05ffc8a3cf1cf9cb5fbe7c4cb9e6dbf391e99877f261f79e7efac7bfdbdfd74b57671fc618847004519830fbc41d030042763002de4343439ed1235b797de5baa90bef78e673cb97af3abab465cb747155ebbde25551afa082b1828ae0bd07af18238840e21545b18053877ac588208053415511012302183c8af70a62c8882567211f39d6aceb62ccc4891c77eaa9ec7be0749e5df2ec6ed72cf8e1cca79e7ce5983b7e74e191560c5bba7a11634095ede539192a0dbeb3345880f39e11c35bc9e432b2f0074f7ce7a7f73c75d1a46287f9c4be390e9e946354a3105983aac10ba828228a8860458211890b1b71c1e38c11c4081e4550c40790bd2a98f47e085e3daa8222a0094d79e5b30b37e2f6f8240f3d72230628d77a28649b59fdce3b1c3aed043e75ec7e3ffedecde7fc53dfd65e3a3aba11519c2abbcf3e69c72c403038af8c18d14aa5e6467ee69cbb16ad7ff10f872c3cbd814fcc698656a00fe854e2c4616c828847f18005a46ec1283e3dacc1120eee512c82a2781fcc540410c1a7ff37260081576ca6c6ca95fd74c868eebafd9b1860d9b3cff38593cfe317cf2d62d70913b8e9eeab3961defcd35e5bdd3fe5d61b3ff74fbbec34ecadf7deebd86ea41b1200a79ee6962289d3fcb19fbbf5c9e2a61553975ddf4661b7043a6216dd5be1bea515d66e2d62b2451c1e9f788c18226b3122c4cee1350111c444a9b93bd4791c1e6b2c1ec53930623002ce39bc82b1198c2ab18b01259331ac5a5d65cf03473261540b8b17ff9a2f7ce6225c7785b34ef82a0f3ffd53f69e3583b97b4ca0d0fdc201279cdcb9f4811f9f376beca896b7376cdcbae300186b681fd6c49997dd774bf5dd97a63e7fc33018e958fb82f0855bba5897db9ba33f7924271f308961a35ba8d61cb5d82302192b20827a87faf05e644c8803894354116bc05abc53d4fb60291ac0f118720d8da88b29f5f461ada1d8dcc2baf7dee5fa8bbfcddaf7d6b2eb6ee399f5d1fd58fec4f3ec75c0142213b1e2e5358cc8f571ef4da3f9fc256bda3f77eedd8f3d7efffc69c5864c6d480454f52f5ebd6fff965fdc77d7cc31edfbebcaab67aafef2005dfd9dfd74eaa87df58be7dfa83d7d65fd30d691079da09fdaf7b881d76bd6beadaaaacec73a7df2d17afe4153557f335bdd5d07e8e4e21efaed2baeb9b0b666c990e71cda02a2881fddff870b3fb17b95497bb7a1eb6b7ce9ce1a9ff8caa52cf89753014f7fef5ad6c7092f61294b066b04414135b89d5850c5aa866c12dc3958471a202d0a5ec9b6b443b50cd50a3b358fe487f3cea79c6b64dedddfa6a7af83868606b2a6893d175cca4f0e3b8db30eff0c175c7b2553f69fc6138f2fe69a2bbe83f5555654c6f1da6f7b987e98e59c2333dcfbc80be77ce194036f18b1a32ef0eefaaee2aab7fe78d835075b30caa25ff7d23372260bfee55494989ed266623ccbbcf27b113c0938030898900e258df20630867a94a39ea0054f640c3623ac7b6229ed33a6512c36320ce1edb6566aadad3c85502a14c9556bf4bdba94f1fb4ee3c447eee2f7d7ddc069c79f4d316f29d562667c6c2677dcfc752efdd2cd2c78e83e7eb46f1b27cdc871dbd2f7262f7f65ddfe47edc9b2bff8a08702e0edffdabc972d778eda67ac815ec7236f7a8efcf43100f456bbb0a2d8a6d1340c1f4f6b3e436314518c2cc5c850b4118d9988864828640cd9ac251359f291a59089284496868ca11845140b055a6c232f1efb25961e71264d9922abbb3b69fe87231839f3006a5d5dec6c1bf9fd1167b1f888334854689cb62b87df7f2b53aeba08e70a7cfdae859cfbedcb686c6ee01fe71dcaebdd396a6b1376196d18912db1e2cd0d73863ae79016b0656bb5ad6895f626035b2b6c8d8bec3e653c00e23c857c33af2e5dc6b3e50aeeb003a9964a8842a658c0c70e8f0fe6ee4280330889a44600812b2048ad8666f2ccbae3ffd0f1fadbac78e0b7bc74f1754cce6e467b37f32b37867d6eb996b64f1fc5840b4fc1151b492a3df456bbd0610576db7d12b6a1404f77378d63863365f771d8e208d676763361a4d05e842d5b7a87f280a101a8549326558731023587f396c857c3e68dc19a467ef1fd9ff06429e1a08fcfa5662a44c6e2bbfab0198bcb6670de8309c6ee4c607ce9f9110d34dbe60af4218c9e771cf12bab593ee318be754207c71c3b9c68bfcff2d8f7fec065279ec2ecd797b0f3d449f4e1c80c1f463b11fdf6552aa57e5a0a05f6d97d17329205118ccd5289011b91a8c130b4e831a40b543ab7b66122f0c1af55408caf6f1f50bc814c3e4302d472596a36e28df9dfa6ebb1a5bc7fd712ba5f58090d4dc4263cfd582006620c0982d81cd58d9d945e7e839e728da55f5ec0d9877470d2fc1c0db64a768fb91cff9d833979fcfbfce12b37d157aad0f9f22bf46eee42c8a251066a093326ee446394016a20e001110185c47bac95648701c0b946630cea058810317fc6a9d5396a718d7ea05273942b35dace3e8968ffe9e4664e241a37925ab542423878a250f34aac4a22422e6a62cd376f63c9c147d1f5fa4a6c7fc247f6c98164c045e84fce8317efe1237376a28af0eee34ff0e83e87b07ed162042855137c5c2571311e976e4a11f5a00ebcc707ff1b726d270d9a50cc00888414973a70dd8f8d282e51ca78aa714c56a178c441c49532d94850e7a9c6d5407a9c62a20c997c2b0071650bbd712fa3cf9fc7b0538fa46dcfc960322c59e238749ec2708b94bbc0442c7bb18c8c179a3f3a8b998f2fa279cf49f4126a074962bcf7a96b09aa1e55874f3c68b004a96f7c4700888c6982f0d4c5bb90cafee436460c68424d1d35172abda4ab1335825405238a1883c49e289f23cab6b0fef687699c3e8111b3f6a6146f263f751245b2e410f6fdfe7c6e9ff11853afeee5d39fad4126e281eff671cfab05f67fe82b340d6f233aea1fa8b96eca840ad2aa23357a806d56eac20b23046eb2a300184b9b4f2bb210053cf558a229da0838e7899da3ea129c8600194afb500d464942269f21976de1cd6bfe1fa52baf44da27617e7e1bc3664da5afb68586a8910dbf788a4dcb5f63b75b1670f9c50b78e8b9f75063788e9d9978ffd574ffe411d877779a8e9d4bb554c536414d95c6d4d7eb0018013132b04711d0545ed83100c48612cd79108f10eaf58040fa6504665773107b504f287bbd8672d703d90cb98661bc7ceddd345c752df77e6702cb9f2b73c99c3399fd9bef3362ce5e94e22acb4eb99468f709ecfffc22861f3b83f79e7c9b7e63983e773ac3468ce5c57d3ec9e6ebfe2ffb74bf4c9ccb1293906828a8240dca0022a1e6f01e481c56b751af1d0200a15f0013bb80a44afddc03cbdaa001c489e25231040f6a40128f4411b675386f2dbc17aef837eebca295c9138529e38a74f7f6f0ad8f9ec99cdfdd4ae3ecbd19ffeb5b68da6322fdd52e8a6376237aeb67144d86dcc947b0b9dac5b8276f2779e92d4ae5adc428269bb24d61d0be42a25515bc4bc03b12afdb0d8243fe4855fb5150a7e085bf184624d4fca1da87449418c5250e11437ef82856dff420b58bbfc9dd1715993c2d8255316c8a39f78c662e9b5b66c99cf9743fff3a630e9c495c8ca8f497a9029d8b97527af20f2440a5bf82cfe7c91e368b72e2f02ec4fc5853af1c7081f0c20ca84adb5c61a835a40578effb51521e1050d63f31010dde817ac5d5b5431fcc2e3f6a0cabbfff30f197bfc14fbf9267affd0bb0ce85a2a0acb021e682b3daf0ae937f9f359f7d9fbb8dfc8c29d4a4c4d69ecdb4dff92d1028f5758287cad67ed85a0223a9a4e2a85513d425213ed5f74d205e460157cf5c4307c1a18dc3988d01d410073e88643d188683ab6a489989c33a4f6ee771acbbfd717acfbd8adbcfca30e36379d8e8535b4d55ca12b0a1ca57cf6ee54b076ce2f903cea277c53bd8b6b154fb4ad851ed64278cc737b692b4b652f54a3551aa89a71a7baacee15cd01a3e90e53495d6122049e9f6d03170680044a4222288d3c0629ca6f80efe255063102c2601eb1c9971e3d974cf136c3df36bdc7e4ac4ec7d1a60a5a29182f1a9959a1404850d8ecbce1fcd85fb6d64d9cc2fd2fbc6bbe4c7ee82696ca4b6ba8337677f91eefb9662c7ec8c178f3ac579214982d06682163df0702495e05ca2e0d20c31b4076c2706782dd54d1ce750f583f269b8a335061365d1044c9c6077dd95ad8f3d4fc7a99771e3676a1c7e74313cf90e608b4064c34713b66daed7c3fb09975d3086cb3fb29e15079d41e9ddcd542b79de3cf6228e78e57ea2f3ae60f3cf96931dbb0bde0adef954ed159c7a54073f184d2d22dc3fb042753b0c8017235a87cfa70aee0722a1626d0632397c35c6ecba33fdcfbcc5da932fe6ba23fb39fee8226cac42e421e7a0af867626018048413c2a1e3209942bf07e8d8bbf32961b66aca3e3d317f1dbfde6f38fb565dc76c72cae3a2a61fda7e6b3f1c1a7c98cd909b50a29fb0b5b1a8884691a9470301f5cc0e9d03630b405a0eddb0c0b54cd20534a5997823841c6b4505db58155275dccd7266fe2b44fb6c31a87762a5a16b466201158eff0ab3d5a35682c5015b46cd09a45372bbc5fe6a8d9a3f8e3f23738d7fc8eeb2f1a0d6bfa39e6c856be77ace7fdcf9ccbe6079e24bbf378ac98d42deb2e30b0ef549572e09560b77ec77980518dcc00fb331f80b01e59bdf32463c752edf6ac3cf1222ed477397fbf71b0a48ac636958100978066c098e052efa6df6c25641967905ccc3b2f09fb2c17ce9e9ce7c6237250a94225c4a0534e6a03dfcd05f32e407fbc80119f3d02b5161fcad46d8faa4ed21220099aa7fc4fd2a0e28da2682c88094a3f03d1d40386a8b948697385b5fffc0d3ebfe165ae3a6a226cf168c52246d3765ae808804937a769ea0ce6aa6a916ccc7b3d963d97479c34a2c68dfb587843d03516463b24b1f076cc29d39ab19ddd7cf5b44b70c37f8899b41b48a8f66550860a5920cd0408dba985b6d318117120a833086ea04b3878550b453aefbd8f33762df2dda3a7409280f348c6a49d0dbf8d876aca5a24b4ba248d2d92f1bc5fc970c82b11a7ed54e5d68f64a002641cb235033d59c86b30f70d86797b8fa4854e2e987f05e5c30f25696b09070ecf3d05554992d09613953fafe2fe1a00108cc78443f97a36f920a1d8d05b636ad2c9d7f6184e577799fe4a827a17a22f41f652e3312640e73d697f488954291ac31a6799f7ba6146a69b5b76316cd81451292790a472aa2a5a51443c78417b0c87ec9ce1e2ea46be76fb0f593b7d0f9ccdb08d09868e9298548256dd1e0fda5e6b8c9aa048126acac46f7bfece291073cac987f3da638b39fee56ea2c6664c94c3b91af88428ed6fc59247b14478441c560cc680f7a15a5c5ff26c3635a6343570f43b59fa3c58e383946e14118b9a0835162f0e4d94e256a53d9363e43065eedc99b436b5504d7ac9450534a5c2b61e17ea2971470130223d0248dace96c1359528fde54e0e99bd1f3f7afa4ed66deca2a9582404c660010a411e171b7a7f2281a39be0fbce43ac1eeb3d4d915031199c08567dc8dd840ad488c59808b1902a1ce021aed4688860dc84f154e312b5b84a2eca0f2e0e4022a08a11fd1306f75700e0bc8e7175b74dbd7f1ba514bc2a7da55ec68fd989f16326100263fd7b0667d75499481ba6db72b60cba483f5be72b830957fd4ad27b643fb0cf8aef264e6a419c19288c05ef64a0161091219d60e86248b51a02954df7b4ed8002a11204fa4a3da995d9d0194ed366aa441032736a4369fafc336db1bef501ddc107e0eb159d3a9a0a2d409615afbfc63bab37337a542b07edb71779d3422c9b06ea0c15c24c8123d402a164b63b0c8018bb2a08fbe1c85277a7faa6d593cbe5c8d9b6a16ef1bf5a31bd542a65543d4d8556cae52a9f9fff2d963ffa28bb64aaace96b60e2c133b9fbaeaf336ad4287aca1df59d425d084fc203dbde80c47678806c1a782121726f531e8442ae858e4d1bb8f9ba85d4faab140a05a26c84892cea3ddef90135de9aa02d06f15206e8aae24345176e893a4fad56058938e7922fb2f32e63709a20449c74ea45fcd7c37771e5fec3993ece31b129e1b3772ee26373d6f2bb67eea0b5ad99103740ac206a43f652fd0b36f757002068437d2ea80ec200380291cd522ed5f8dd63cf522b57686a2c628c21b2c13a822c6630512045890b4a6d086c61304255f19a56f0e9384b7f7f1f62234e3be754acc91091e39e877fce8b0fff8cf7be309c450d73b8be3c89cbbbff83c72f19ce5e0b9e67e1cdf771cd55e70265eaa62aa92b6a68d6ee3815f66883a088034c60d8038c5a84bef216c6ef3696a7df5c5c87856d689941ef0d1624b65397fec98ae9a354eda6986be7374bdfe0d8b10e5b1432b9889b6efa57aebc7a2cc73d7f3997ce2a72cb532f139c3e0322380f3e2565033ae68e0280d78601a9c909deb1cd0a148cb1c4714c4ffffb589b21934fb93e82f7612648c4a6a93808a51a64e354d509deea073144f54ab55cc50834341506be2e17197a630365c3cade2ad5955d4cec5cc59462338f6d80c6b12d29e8490880de879a23098ab6fe9990f15700a0d03c90d07d3af1556f8ca852ccb7f3e6db6ff0a9d9a742ec691fd6868d2cc608ea344c8118033698bcf7a948610dd6864249147caa353875b82461cba6cd186b59b4e47ef6dc731a0087cdda932f5e1bb1ba23cfc4ec7a969c7e22378c7a1d464fe486ffdcc0bf5e7e647850040137b2823576db49fe27310031c9807ce50941cdd53b45508dfb19317218df5c70059572896c26c28849eb2505afe990940c7c46d23199ba74ad3ed518049c0f71a3522e633319468d6ea71af793cdc0dcc36771e84927f2b1071fe4cac92bf9fc30e5975b8673f9436f32e5b81339fda4c3f15ac688c146166bcd001f322116ec785f0091c6a00184d2ad568da954d2511b15aa7199424396933f77f290b7f8dfac6adc4535ee0393c5aaf2b56bcee3a1bd2771e7634f72dba6f7d15c2333cf3d91f9179f81a54235eea3906dc188214e1cb10f1a64669b44fa17d776062589026ff11019229477d76f06423fc0258a738edefe8d7c402c1d20406c4b9b66903097b2491d44590742e3e0809552e65a2da190b1b435184efce74f70e2c91fa363c3261a9b9b681e3992f67c4412f7e19c07b26ce8eaa75aab50cc46e03c4615bf9d1830a469e433b6a7e2849203328e5d0bc2b217dea4ecca3464f344191bb4b834c80dd06542572894c39ad603522f0febd434bdc264a81883189bfe1b862807839a78d86544134dc4d41218b9d34e343435d24495518d4114c9e502457e61c52a1ae25e762e5aa87ad47b8c61c829b1212d60c2e8d6c61e9363554fcc5e0d70dcc8884b96bec4332fbcc1b4c9a369cce7692814d20e725d26ff802da490d40954aa030cca8aaad120b122cc146db3819420a73fb6c6b2c7f831547c42927832d690b3110e47ec1c3da52a35bb95471f7d8a03f2250a852cd5adc2fa38e2a8314d2b761c809dda17378f1ecdaf36ae64af9111478c50262f7d8beb6f7a8085d79d4767ef563228c54236d4ec3aa843eb53c113c10c3a04f5a0280cb8c7001e291d0c00a48c6e50feaa0b5b511491890c65af3897508b1db1185a468de4ae1f3cc43bbffc153ff8780eac63e9264357d3b0de03a7eff2e450e71cd2051a0b998d73f79fbceac7ab3dbe3702a32c9c9ee7dd877fc62597df4c576f152d34d1132b3de584feaaa35c8b29576a946b09e56a42b5e6a824f5cb872b76a1b9e1c355f250f24ad97b2a786a408c10235431543194d552514b8588be043acb09dde5989213a26223b1312cfcf7bb5970d942ae9be6d975840789f88f376accd86f8f7b27ef366aeb0e5bc0d6ad7d9c76e4bed73ffcdb17bf7ff9cbebb96e4623e39a131edb27e2ac471ee1cc575e63d611873271d73114f259e238a1528d831e90727e6b4dda52db660081999840971130362544296bd3a0418639c34115586a3d22618a359789108435eb3a787af133c4cb9673fbd42cc74f8f205be1fee58edf979a9345a7cebe29768ec210e71c725afca5ff7f272346b46797bdfadea5675dfda36f5d3a52b968f706481c54e03fd7f7f3d8e6126b244b25ca61108c0b1d1f518f683af09e96cdeac3d498a607b3a9db48dae0f0d487284dcade42ffcf2a690344c3bc415054888d01e7199d9438ac3dcbe9939ac88ded876cc23daf2a672d4df8e6a5a72e3e7ffe3147adebe870bb1e70ec8e01b0e2a1bbf1083b8d1cc613cfbef1a50b163ef8bdc3b48f6f4c69624ab3802441a84cdbe7034fcb08c1730319c2d73340fa44078b33890bb120158d07d2e8e0a0e1495b697ed0fb83d4a9bc816204851aabbbabfcdb0b551eec2870e169739ff8f219477db2da9029f7f7f432f1a04feda0053c70275e0d998c65784b913756ad5f70ed3d4b2e7e6bc54a0ec9d598333c626aa361786430aad480c40421b33e1c6f4588bc4bd58850feaa187c5de8a8f71b53d3f6697518f4a3749c16c56848ad09a0c6105943de7b32783a1258514e78bab3ca735d19464d9ac845f366df7ad0be93cef6ad0d78e3892b951d0760e5930f50e9a951e9ad215618d6522442263cf5f23b37fce2c555e356bef3be96fbca6d4675b47acdab6c537e3495bd1575828a513546c25f7fb894311842a114de0960253e68082ae974d2009fd2014aaf04ae6054897c0c6269686b65f26ea3fd9c3dc72d9e3363d24d3e763f8ff396b671c3a994fa7149c2a459c7ef1800ab973d4c524ee8d9d03760cdd658863517b1826cdad2a35bb6f6db4ae2462b5a3290171881a0464c0e549cd71e1ffe98689888e480bc572211531642c3321c4d7db954ebcbe7332dd6d8615e5da4aa092a2a488c912da09b51acaa16826708a837f9acd591ed4daebdad79a3f3b2a1737327893a464d1b8f89a056a9e2bd67f2c13b08c0dfcafa9bffb3b9bf03f0616fe0c35e7f07e0c3dec087bdfe1b3377667811d7b37c0000000049454e44ae426082">
    <style>
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: local('Poppins Regular'), local('Poppins-Regular'), 
                 url('/assets/fonts/poppins-regular.woff2') format('woff2');
        }
        
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 600;
            font-display: swap;
            src: local('Poppins SemiBold'), local('Poppins-SemiBold'), 
                 url('/assets/fonts/poppins-semibold.woff2') format('woff2');
        }
        
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #2ecc71;
            --error-color: #e74c3c;
            --dark-color: #1a1a1a;
            --light-color: #ecf0f1;
            --border-radius: 6px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background-color: var(--light-color);
            color: var(--dark-color);
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1, h2, h3 {
            color: var(--secondary-color);
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .tab-button {
            padding: 10px 20px;
            cursor: pointer;
            background: none;
            border: none;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            opacity: 0.7;
            transition: all 0.3s;
        }
        
        .tab-button.active {
            border-bottom: 3px solid var(--primary-color);
            opacity: 1;
            font-weight: 600;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .preview-container {
            width: 100%;
            max-width: 800px;
            margin: 0 auto 20px;
            overflow: hidden;
            border-radius: var(--border-radius);
            position: relative;
            border: 1px solid #ddd;
        }
        
        .preview-container[data-ratio="16:9"] { height: 450px; }
        .preview-container[data-ratio="9:16"] { height: 600px; }
        .preview-container[data-ratio="1:1"] { height: 500px; }
        .preview-container[data-ratio="21:9"] { height: 400px; }
        
        .preview-frame {
            width: 100%;
            height: 100%;
            border: none;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }
        
        input[type="file"] {
            padding: 10px 0;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 16px;
            transition: background 0.3s;
        }
        
        .button:hover {
            background-color: #2980b9;
        }
        
        .button.secondary {
            background-color: #95a5a6;
        }
        
        .button.secondary:hover {
            background-color: #7f8c8d;
        }
        
        .button.danger {
            background-color: var(--error-color);
        }
        
        .button.danger:hover {
            background-color: #c0392b;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
        }
        
        .message.success {
            background-color: rgba(46, 204, 113, 0.2);
            color: #27ae60;
            border: 1px solid #27ae60;
        }
        
        .message.error {
            background-color: rgba(231, 76, 60, 0.2);
            color: #c0392b;
            border: 1px solid #c0392b;
        }
        
        .code-box {
            background: #272822;
            color: #f8f8f2;
            padding: 20px;
            border-radius: var(--border-radius);
            overflow-x: auto;
            font-family: monospace;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .tabs {
                flex-direction: column;
                border-bottom: none;
            }
            
            .tab-button {
                border-bottom: 1px solid #ddd;
                text-align: left;
                padding: 15px;
            }
            
            .tab-button.active {
                border-bottom: 1px solid var(--primary-color);
                border-left: 5px solid var(--primary-color);
            }
        }
        
        .input-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .input-with-unit {
            flex: 1;
        }
        
        .unit-select {
            width: auto;
            min-width: 140px;
        }
        
        .form-text {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .preview-container[data-ratio="16:9"] { height: 450px; }
        .preview-container[data-ratio="9:16"] { height: 600px; }
        .preview-container[data-ratio="1:1"] { height: 500px; }
        .preview-container[data-ratio="21:9"] { height: 400px; }
        
        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .image-item {
            position: relative;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            overflow: hidden;
            background: #f8f9fa;
        }
        
        .image-item img {
            width: 100%;
            height: 150px;
            object-fit: contain;
            display: block;
        }
        
        .image-info {
            padding: 10px;
            background: rgba(0,0,0,0.05);
            font-size: 12px;
            word-break: break-all;
        }
        
        .delete-form {
            padding: 10px;
            text-align: center;
        }
        
        .delete-form .button {
            width: 100%;
            padding: 8px;
            font-size: 14px;
        }
        
        .upload-form {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .upload-form input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 2px dashed #ddd;
            border-radius: var(--border-radius);
            background: #f8f9fa;
        }
        
        .upload-form .form-text {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="language-switcher" style="text-align: right; margin-bottom: 20px;">
            <a href="?lang=de" class="button secondary" style="padding: 5px 10px; font-size: 14px; <?php echo (!isset($_GET['lang']) || $_GET['lang'] === 'de') ? 'background-color: var(--primary-color);' : ''; ?>">DE</a>
            <a href="?lang=en" class="button secondary" style="padding: 5px 10px; font-size: 14px; <?php echo (isset($_GET['lang']) && $_GET['lang'] === 'en') ? 'background-color: var(--primary-color);' : ''; ?>">EN</a>
        </div>
        <h1><?php echo t('Werbung - Einstellungen'); ?></h1>
        
        <?php if (!empty($message)): ?>
        <div class="message <?php echo $messageType; ?>">
            <?php echo t($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab-button <?php echo $activeTab === 'settings' ? 'active' : ''; ?>" data-tab="settings"><?php echo t('Einstellungen'); ?></button>
            <button class="tab-button <?php echo $activeTab === 'help' ? 'active' : ''; ?>" data-tab="help"><?php echo t('Anleitung'); ?></button>
            <button class="tab-button <?php echo $activeTab === 'images' ? 'active' : ''; ?>" data-tab="images"><?php echo t('Bildverwaltung'); ?></button>
        </div>
        
        <div class="tab-content <?php echo $activeTab === 'settings' ? 'active' : ''; ?>" id="settings">
            <div class="card">
                <h2><?php echo t('Vorschau'); ?></h2>
                <div class="form-group">
                    <label><?php echo t('Vorschau-Format'); ?></label>
                    <div class="input-group">
                        <button type="button" class="button aspect-ratio-button" data-ratio="16:9">16:9</button>
                        <button type="button" class="button aspect-ratio-button" data-ratio="9:16">9:16</button>
                        <button type="button" class="button aspect-ratio-button" data-ratio="1:1">1:1</button>
                        <button type="button" class="button aspect-ratio-button" data-ratio="21:9">21:9</button>
                    </div>
                </div>
                <div class="preview-container" data-ratio="16:9">
                    <iframe src="<?php echo $previewUrl; ?>" class="preview-frame" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="animation"><?php echo t('Animation'); ?></label>
                        <select id="animation" name="animation">
                            <?php foreach ($animationOptions as $value => $label): ?>
                            <option value="<?php echo $value; ?>" <?php echo $settings['animation'] === $value ? 'selected' : ''; ?>><?php echo t($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="interval"><?php echo t('Anzeigedauer pro Bild'); ?></label>
                        <div class="input-group">
                            <input type="number" id="interval" name="interval" 
                                 value="<?php echo $intervalDisplay['value']; ?>" 
                                 min="<?php echo $intervalDisplay['unit'] === 's' ? '1' : '1000'; ?>" 
                                 max="<?php echo $intervalDisplay['unit'] === 's' ? '60' : '60000'; ?>" 
                                 step="<?php echo $intervalDisplay['unit'] === 's' ? '0.5' : '500'; ?>"
                                 class="input-with-unit">
                            <select name="interval_unit" id="interval_unit" class="unit-select">
                                <option value="ms" <?php echo $intervalDisplay['unit'] === 'ms' ? 'selected' : ''; ?>><?php echo t('Millisekunden'); ?></option>
                                <option value="s" <?php echo $intervalDisplay['unit'] === 's' ? 'selected' : ''; ?>><?php echo t('Sekunden'); ?></option>
                            </select>
                        </div>
                        <small class="form-text">Bereich: 1-60 Sekunden oder 1000-60000 Millisekunden</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="transition"><?php echo t('Übergangszeit'); ?></label>
                        <div class="input-group">
                            <input type="number" id="transition" name="transition" 
                                 value="<?php echo $transitionDisplay['value']; ?>" 
                                 min="<?php echo $transitionDisplay['unit'] === 's' ? '0.2' : '200'; ?>" 
                                 max="<?php echo $transitionDisplay['unit'] === 's' ? '3' : '3000'; ?>" 
                                 step="<?php echo $transitionDisplay['unit'] === 's' ? '0.1' : '100'; ?>"
                                 class="input-with-unit">
                            <select name="transition_unit" id="transition_unit" class="unit-select">
                                <option value="ms" <?php echo $transitionDisplay['unit'] === 'ms' ? 'selected' : ''; ?>><?php echo t('Millisekunden'); ?></option>
                                <option value="s" <?php echo $transitionDisplay['unit'] === 's' ? 'selected' : ''; ?>><?php echo t('Sekunden'); ?></option>
                            </select>
                        </div>
                        <small class="form-text">Bereich: 0.2-3 Sekunden oder 200-3000 Millisekunden</small>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="autostart" name="autostart" <?php echo $settings['autostart'] ? 'checked' : ''; ?>>
                        <label for="autostart"><?php echo t('Diashow automatisch starten'); ?></label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="fullscreen" name="fullscreen" <?php echo $settings['fullscreen'] ? 'checked' : ''; ?>>
                        <label for="fullscreen"><?php echo t('Automatisch Vollbild aktivieren'); ?></label>
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="repeat" name="repeat" <?php echo isset($settings['repeat']) ? ($settings['repeat'] ? 'checked' : '') : 'checked'; ?>>
                        <label for="repeat"><?php echo t('Diashow endlos wiederholen'); ?></label>
                    </div>
                    
                    <div class="form-group">
                        <label for="gif_loops"><?php echo t('GIF-Wiederholungen'); ?></label>
                        <div class="input-group">
                            <input type="number" id="gif_loops" name="gif_loops" 
                                 value="<?php echo isset($settings['gif_loops']) ? $settings['gif_loops'] : 1; ?>" 
                                 min="1" 
                                 max="10" 
                                 step="1"
                                 class="input-with-unit">
                        </div>
                        <small class="form-text"><?php echo t('Anzahl der Wiederholungen eines GIF-Bildes, bevor zum nächsten Bild gewechselt wird. (1-10)'); ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="orientation"><?php echo t('Ausrichtung'); ?></label>
                        <input type="checkbox" id="orientation" name="orientation" class="input-with-unit"> <?php echo t('Vertikal'); ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="resolution"><?php echo t('Bildschirmauflösung'); ?></label>
                        <select id="resolution" name="resolution" class="input-with-unit">
                            <option value="1920x1080">Full HD (1920x1080)</option>
                            <option value="1280x720">HD (1280x720)</option>
                            <option value="2560x1440">QHD (2560x1440)</option>
                            <option value="3840x2160">4K UHD (3840x2160)</option>
                            <option value="1366x768">WXGA (1366x768)</option>
                            <option value="1600x900">HD+ (1600x900)</option>
                            <option value="1440x900">WXGA+ (1440x900)</option>
                            <option value="1280x800">WXGA (1280x800)</option>
                            <option value="2560x1080">21:9 (2560x1080)</option>
                            <option value="3440x1440">21:9 (3440x1440)</option>
                            <option value="1680x1050">16:10 (1680x1050)</option>
                            <option value="1920x1200">16:10 (1920x1200)</option>
                            <option value="4096x2560">16:10 4K (4096x2560)</option>
                            <option value="8192x5120">16:10 8K (8192x5120)</option>
                            <option value="2560x1440">16:9 2K (2560x1440)</option>
                            <option value="3840x2160">16:9 4K (3840x2160)</option>
                            <option value="7680x4320">16:9 8K (7680x4320)</option>
                            <option value="2048x2048">1:1 2K (2048x2048)</option>
                            <option value="4096x4096">1:1 4K (4096x4096)</option>
                            <option value="8192x8192">1:1 8K (8192x8192)</option>
                            <option value="5120x2160">21:9 4K (5120x2160)</option>
                            <option value="10240x4320">21:9 8K (10240x4320)</option>
                            <option value="720x1280">9:16 (720x1280)</option>
                            <option value="1080x1920">9:16 (1080x1920)</option>
                            <option value="1440x2560">9:16 2K (1440x2560)</option>
                            <option value="2160x3840">9:16 4K (2160x3840)</option>
                            <option value="4320x7680">9:16 8K (4320x7680)</option>
                            <option value="1080x2520">9:21 (1080x2520)</option>
                            <option value="1440x3360">9:21 2K (1440x3360)</option>
                            <option value="2160x5040">9:21 4K (2160x5040)</option>
                            <option value="4320x10080">9:21 8K (4320x10080)</option>
                            <option value="1050x1680">10:16 (1050x1680)</option>
                            <option value="1200x1920">10:16 2K (1200x1920)</option>
                            <option value="2400x3840">10:16 4K (2400x3840)</option>
                            <option value="4800x7680">10:16 8K (4800x7680)</option>
                        </select>
                    </div>
                    
                    <button type="submit" name="save_settings" id="saveSettingsBtn" class="button"><?php echo t('Einstellungen speichern'); ?></button>
                    <button type="button" id="previewButton" class="button secondary"><?php echo t('Vorschau aktualisieren'); ?></button>
                </form>
            </div>
        </div>
        
        <div class="tab-content <?php echo $activeTab === 'help' ? 'active' : ''; ?>" id="help">
            <div class="card">
                <h2><?php echo t('Konfiguration'); ?></h2>
                <p><?php echo t('Die Konfiguration erfolgt über die Datei config.php. Hier finden Sie alle wichtigen Einstellungen:'); ?></p>
                <ul>
                    <li><?php echo t('Animation und Übergänge'); ?></li>
                    <li><?php echo t('Anzeigedauer der Bilder'); ?></li>
                    <li><?php echo t('Automatische Wiedergabe'); ?></li>
                    <li><?php echo t('Vollbildmodus'); ?></li>
                    <li><?php echo t('Bildschirmauflösung'); ?></li>
                </ul>
            </div>

            <div class="card">
                <h2><?php echo t('Verwendung'); ?></h2>
                <h3><?php echo t('Integration in Ihre Webseite'); ?></h3>
                <p><?php echo t('Sie können die Werbung auf verschiedene Arten einbinden:'); ?></p>
                <ul>
                    <li><?php echo t('Als iframe (empfohlen)'); ?></li>
                    <li><?php echo t('Als direkten Link'); ?></li>
                    <li><?php echo t('Als AJAX-Integration'); ?></li>
                </ul>

                <h3><?php echo t('Parameter-Optionen'); ?></h3>
                <p><?php echo t('Verfügbare URL-Parameter:'); ?></p>
                <ul>
                    <li><code>mode</code> - <?php echo t('Anzeigemodus (fade, slide)'); ?></li>
                    <li><code>interval</code> - <?php echo t('Zeit zwischen Bildwechseln in ms'); ?></li>
                    <li><code>transition</code> - <?php echo t('Übergangszeit in ms'); ?></li>
                    <li><code>ui</code> - <?php echo t('Bedienelemente anzeigen/verstecken'); ?></li>
                    <li><code>animation</code> - <?php echo t('Animationstyp'); ?></li>
                </ul>
            </div>

            <div class="card">
                <h2><?php echo t('Beispiele'); ?></h2>
                <h3><?php echo t('Einfache Integration'); ?></h3>
                <div class="code-box">
                    &lt;iframe src="index.php" width="100%" height="400" frameborder="0" allowfullscreen&gt;&lt;/iframe&gt;
                </div>

                <h3><?php echo t('Mit Parametern'); ?></h3>
                <div class="code-box">
                    &lt;iframe src="index.php?mode=slide&interval=5000&transition=1000&ui=0" width="100%" height="400" frameborder="0" allowfullscreen&gt;&lt;/iframe&gt;
                </div>
            </div>

            <div class="card">
                <h2><?php echo t('Tipps & Tricks'); ?></h2>
                <ul>
                    <li><?php echo t('Optimieren Sie Ihre Bilder für das Web'); ?></li>
                    <li><?php echo t('Verwenden Sie das WebP-Format für bessere Performance'); ?></li>
                    <li><?php echo t('Testen Sie die Anzeige auf verschiedenen Geräten'); ?></li>
                    <li><?php echo t('Regelmäßige Wartung der Bildergalerie'); ?></li>
                </ul>
            </div>

            <div class="card">
                <h2><?php echo t('Fehlerbehebung'); ?></h2>
                <h3><?php echo t('Häufige Probleme'); ?></h3>
                <ul>
                    <li><?php echo t('Bilder werden nicht angezeigt'); ?></li>
                    <li><?php echo t('Animation funktioniert nicht'); ?></li>
                    <li><?php echo t('Vollbildmodus nicht verfügbar'); ?></li>
                    <li><?php echo t('Performance-Probleme'); ?></li>
                </ul>

                <h3><?php echo t('Lösungen'); ?></h3>
                <ul>
                    <li><?php echo t('Überprüfen Sie die Dateiberechtigungen'); ?></li>
                    <li><?php echo t('Aktualisieren Sie Ihren Browser'); ?></li>
                    <li><?php echo t('Leeren Sie den Browser-Cache'); ?></li>
                    <li><?php echo t('Kontrollieren Sie die Bildformate'); ?></li>
                </ul>
            </div>

            <div class="card">
                <h2><?php echo t('Technische Details'); ?></h2>
                <h3><?php echo t('Unterstützte Formate'); ?></h3>
                <ul>
                    <li><strong>JPG/JPEG</strong> - <?php echo t('Standard-Bildformat, gute Kompression für Fotos'); ?></li>
                    <li><strong>PNG</strong> - <?php echo t('Unterstützt Transparenz, ideal für Grafiken'); ?></li>
                    <li><strong>WebP</strong> - <?php echo t('Empfohlen! Moderne Format mit bester Kompression'); ?></li>
                    <li><strong>GIF</strong> - <?php echo t('Einfache Animationen möglich'); ?></li>
                </ul>

                <h3><?php echo t('Systemanforderungen'); ?></h3>
                <ul>
                    <li><?php echo t('PHP 7.4 oder höher'); ?></li>
                    <li><?php echo t('GD oder ImageMagick für Bildverarbeitung'); ?></li>
                    <li><?php echo t('Moderne Webbrowser'); ?></li>
                    <li><?php echo t('JavaScript aktiviert'); ?></li>
                </ul>
            </div>

            <div class="card">
                <h2><?php echo t('Sicherheit'); ?></h2>
                <ul>
                    <li><?php echo t('Regelmäßige Sicherheitsupdates'); ?></li>
                    <li><?php echo t('Validierung aller Eingaben'); ?></li>
                    <li><?php echo t('Sichere Dateioperationen'); ?></li>
                    <li><?php echo t('XSS-Schutz'); ?></li>
                </ul>
            </div>

            <div class="card">
                <h2><?php echo t('Performance'); ?></h2>
                <ul>
                    <li><?php echo t('Bildoptimierung'); ?></li>
                    <li><?php echo t('Caching-Strategien'); ?></li>
                    <li><?php echo t('Lazy Loading'); ?></li>
                    <li><?php echo t('Minimierte Ressourcen'); ?></li>
                </ul>
            </div>

            <div class="card">
                <h2><?php echo t('Wartung'); ?></h2>
                <ul>
                    <li><?php echo t('Regelmäßige Backups'); ?></li>
                    <li><?php echo t('Überprüfung der Logs'); ?></li>
                    <li><?php echo t('Aktualisierung der Abhängigkeiten'); ?></li>
                    <li><?php echo t('Bereinigung alter Dateien'); ?></li>
                </ul>
            </div>
        </div>
        
        <div class="tab-content <?php echo $activeTab === 'images' ? 'active' : ''; ?>" id="images">
            <div class="card">
                <h2><?php echo t('Bilder hochladen'); ?></h2>
                <form method="post" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group">
                        <label for="images"><?php echo t('Bilder auswählen'); ?></label>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" required>
                        <small class="form-text"><?php echo t('Nur Bilder im Format JPG, PNG, WEBP oder GIF sind erlaubt'); ?></small>
                        <small class="form-text"><?php echo t('Maximale Dateigröße: 10MB'); ?></small>
                    </div>
                    <button type="submit" name="upload_images" class="button"><?php echo t('Hochladen'); ?></button>
                </form>
            </div>
            
            <div class="card">
                <h2><?php echo t('Vorhandene Bilder'); ?></h2>
                <div class="image-grid">
                    <?php
                    $imageDir = 'assets/pictures/';
                    if (is_dir($imageDir)) {
                        // Verbesserte Methode zum Finden aller Bilder, auch mit Großbuchstaben-Endungen
                        $bilder = [];
                        $dateien = scandir($imageDir);
                        
                        foreach ($dateien as $datei) {
                            if ($datei === '.' || $datei === '..') {
                                continue;
                            }
                            
                            $erweiterung = strtolower(pathinfo($datei, PATHINFO_EXTENSION));
                            if (in_array($erweiterung, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                                $bilder[] = $imageDir . $datei;
                            }
                        }
                        
                        foreach ($bilder as $image) {
                            $fileName = basename($image);
                            echo '<div class="image-item">';
                            echo '<img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($fileName) . '">';
                            echo '<div class="image-info">' . htmlspecialchars($fileName) . '</div>';
                            echo '<form method="post" class="delete-form">';
                            echo '<input type="hidden" name="image_path" value="' . htmlspecialchars($image) . '">';
                            echo '<button type="submit" name="delete_image" class="button danger">' . t('Bild löschen') . '</button>';
                            echo '</form>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab-Funktionalität
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');
        
        // URL-Hash zu Tab-ID Mapping
        const hashToTabMapping = {
            'einstellungen': 'settings',
            'anleitung': 'help',
            'bildverwaltung': 'images',
            'settings': 'settings',
            'help': 'help',
            'images': 'images'
        };
        
        // Funktion zum Aktivieren eines Tabs
        function activateTab(tabId) {
            // Finde die entsprechende Tab-ID aus dem Mapping
            const actualTabId = hashToTabMapping[tabId] || tabId;
            
            // Deaktiviere alle Tabs
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Aktiviere den gewählten Tab
            const tabButton = document.querySelector(`.tab-button[data-tab="${actualTabId}"]`);
            if (tabButton) {
                tabButton.classList.add('active');
                document.getElementById(actualTabId).classList.add('active');
                
                // Aktualisiere die Session
                fetch('config.php?set_tab=' + actualTabId, { method: 'GET' });
            }
        }
        
        // Aktiven Tab aus Session setzen, falls vorhanden
        document.addEventListener('DOMContentLoaded', () => {
            <?php if (isset($_SESSION['active_tab'])): ?>
            // Tab aus Session aktivieren
            const activeTab = '<?php echo $_SESSION['active_tab']; ?>';
            const activeTabButton = document.querySelector(`.tab-button[data-tab="${activeTab}"]`);
            
            if (activeTabButton) {
                // Aktiviere den Tab aus der Session
                activateTab(activeTab);
            }
            <?php endif; ?>
            
            // Hash-Navigation hat Vorrang vor Session
            if (window.location.hash) {
                const hashValue = window.location.hash.substring(1);
                activateTab(hashValue);
            }
        });
        
        // Event-Listener für Tab-Buttons
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                const tab = button.dataset.tab;
                
                // Setze den Tab in der URL
                const germanTab = Object.keys(hashToTabMapping).find(key => 
                    hashToTabMapping[key] === tab && key !== tab);
                
                if (germanTab) {
                    window.location.hash = germanTab;
                } else {
                    window.location.hash = tab;
                }
                
                // Aktiviere den Tab
                activateTab(tab);
            });
        });
        
        // Event-Listener für Hash-Änderungen
        window.addEventListener('hashchange', () => {
            if (window.location.hash) {
                const hashValue = window.location.hash.substring(1);
                activateTab(hashValue);
            }
        });
        
        // Live-Vorschau
        const previewButton = document.getElementById('previewButton');
        const previewFrame = document.querySelector('.preview-frame');
        const animationSelect = document.getElementById('animation');
        const intervalInput = document.getElementById('interval');
        const transitionInput = document.getElementById('transition');
        
        function updatePreview() {
            // Wähle den richtigen Modus basierend auf der Animation
            let mode = '';
            const animation = animationSelect.value;
            
            if (animation === 'fade') {
                mode = 'fade';
            } else if (['slide-horizontal', 'slide-vertical'].includes(animation)) {
                mode = 'slide';
            }
            
            // Konvertiere Eingabewerte in Millisekunden für die URL
            let intervalValue = parseInt(intervalInput.value);
            if (document.getElementById('interval_unit').value === 's') {
                intervalValue = intervalValue * 1000;
            }
            
            let transitionValue = parseInt(transitionInput.value);
            if (document.getElementById('transition_unit').value === 's') {
                transitionValue = transitionValue * 1000;
            }
            
            // Baue die URL
            const params = new URLSearchParams({
                mode: mode,
                interval: intervalValue,
                transition: transitionValue,
                ui: '1',
                animation: animation,
                gif_loops: $settings['gif_loops'], // GIF-Wiederholungseinstellung
                preview: '1', // Markiere als Vorschau, um Cache zu vermeiden
                ts: time() // Timestamp hinzufügen, um Cache zu umgehen
            });
            
            previewFrame.src = `index.php?${params.toString()}`;
        }
        
        // Füge Event-Listener für Einheiten-Änderungen hinzu
        document.getElementById('interval_unit').addEventListener('change', function() {
            const input = document.getElementById('interval');
            const value = parseFloat(input.value);
            
            if (this.value === 's' && input.value >= 1000) {
                // Konvertiere von ms zu s
                input.value = (value / 1000).toFixed(1);
                input.min = '1';
                input.max = '60';
                input.step = '0.5';
            } else if (this.value === 'ms' && input.value < 100) {
                // Konvertiere von s zu ms
                input.value = value * 1000;
                input.min = '1000';
                input.max = '60000';
                input.step = '500';
            }
        });
        
        document.getElementById('transition_unit').addEventListener('change', function() {
            const input = document.getElementById('transition');
            const value = parseFloat(input.value);
            
            if (this.value === 's' && input.value >= 200) {
                // Konvertiere von ms zu s
                input.value = (value / 1000).toFixed(1);
                input.min = '0.2';
                input.max = '3';
                input.step = '0.1';
            } else if (this.value === 'ms' && input.value < 10) {
                // Konvertiere von s zu ms
                input.value = value * 1000;
                input.min = '200';
                input.max = '3000';
                input.step = '100';
            }
        });
        
        previewButton.addEventListener('click', updatePreview);
        
        // Einstellungen speichern und neuen Tab öffnen
        document.getElementById('saveSettingsBtn').addEventListener('click', function(e) {
            // Öffne neuen Tab mit aktuellen Einstellungen
            const mode = animationSelect.value === 'fade' ? 'fade' : 
                        (['slide-horizontal', 'slide-vertical'].includes(animationSelect.value) ? 'slide' : '');
            
            // Konvertiere Eingabewerte in Millisekunden für die URL
            let intervalValue = parseInt(intervalInput.value);
            if (document.getElementById('interval_unit').value === 's') {
                intervalValue = intervalValue * 1000;
            }
            
            let transitionValue = parseInt(transitionInput.value);
            if (document.getElementById('transition_unit').value === 's') {
                transitionValue = transitionValue * 1000;
            }
            
            // Erstelle URL mit aktuellen Einstellungen
            const autostart = document.getElementById('autostart').checked;
            const repeat = document.getElementById('repeat').checked;
            const resolutionInput = document.getElementById('resolution').value;
            const params = new URLSearchParams({
                mode: mode,
                interval: intervalValue,
                transition: transitionValue,
                ui: '0', // UI in der Live-Version ausblenden
                animation: animationSelect.value,
                autostart: autostart ? '1' : '0',
                repeat: repeat ? '1' : '0',
                ts: Date.now(),
                resolution: resolutionInput, // Add resolution to URL
                control: '0' // Steuerung deaktivieren
            });
            
            // Öffne neuen Tab mit den aktuellen Einstellungen
            window.open('index.php?' + params.toString(), '_blank');
            
            // Das Formular wird normal abgesendet (kein e.preventDefault())
        });
        
        // Aktualisiere die Vorschau beim Laden
        document.addEventListener('DOMContentLoaded', () => {
            // Eventuelle Hash-Navigation verarbeiten
            if (window.location.hash) {
                const tab = window.location.hash.substring(1);
                const tabButton = document.querySelector(`.tab-button[data-tab="${tab}"]`);
                
                if (tabButton) {
                    tabButton.click();
                }
            }
        });
        
        document.querySelectorAll('.aspect-ratio-button').forEach(button => {
            button.addEventListener('click', () => {
                const ratio = button.getAttribute('data-ratio');
                const previewContainer = document.querySelector('.preview-container');
                previewContainer.setAttribute('data-ratio', ratio);
 
                // Deaktiviere vorherige aktive Buttons
                document.querySelectorAll('.aspect-ratio-button').forEach(btn => btn.classList.remove('active'));
                button.classList.add('active');
 
                // Wähle passende Auflösung basierend auf Verhältnis
                let resolution = '1920x1080'; // fallback
                switch (ratio) {
                    case '16:9': resolution = '1920x1080'; break;
                    case '9:16': resolution = '1080x1920'; break;
                    case '1:1': resolution = '1080x1080'; break;
                    case '21:9': resolution = '2560x1080'; break;
                }
 
                // Setze die Auflösung im Dropdown
                const resolutionSelect = document.getElementById('resolution');
                resolutionSelect.value = resolution;
 
                // Vorschau-URL aktualisieren
                updatePreview();
            });
        });

        function parseResolution(resolution) {
            const [width, height] = resolution.split('x').map(Number);
            return { width, height };
        }


        const resolutionSelect = document.getElementById('resolution');
        const orientationToggle = document.getElementById('orientation');

        const horizontalResolutions = [
            { value: '1920x1080', label: 'Full HD (1920x1080)' },
            { value: '1280x720', label: 'HD (1280x720)' },
            { value: '2560x1440', label: 'QHD (2560x1440)' },
            { value: '3840x2160', label: '4K UHD (3840x2160)' },
            { value: '1366x768', label: 'WXGA (1366x768)' },
            { value: '1600x900', label: 'HD+ (1600x900)' },
            { value: '1440x900', label: 'WXGA+ (1440x900)' },
            { value: '1280x800', label: 'WXGA (1280x800)' },
            { value: '2560x1080', label: '21:9 (2560x1080)' },
            { value: '3440x1440', label: '21:9 (3440x1440)' },
            { value: '1680x1050', label: '16:10 (1680x1050)' },
            { value: '1920x1200', label: '16:10 (1920x1200)' },
            { value: '4096x2560', label: '16:10 4K (4096x2560)' },
            { value: '8192x5120', label: '16:10 8K (8192x5120)' },
            { value: '2560x1440', label: '16:9 2K (2560x1440)' },
            { value: '3840x2160', label: '16:9 4K (3840x2160)' },
            { value: '7680x4320', label: '16:9 8K (7680x4320)' }
        ];

        const verticalResolutions = [
            { value: '720x1280', label: '9:16 (720x1280)' },
            { value: '1080x1920', label: '9:16 (1080x1920)' },
            { value: '1440x2560', label: '9:16 2K (1440x2560)' },
            { value: '2160x3840', label: '9:16 4K (2160x3840)' },
            { value: '4320x7680', label: '9:16 8K (4320x7680)' },
            { value: '1080x2520', label: '9:21 (1080x2520)' },
            { value: '1440x3360', label: '9:21 2K (1440x3360)' },
            { value: '2160x5040', label: '9:21 4K (2160x5040)' },
            { value: '4320x10080', label: '9:21 8K (4320x10080)' },
            { value: '1050x1680', label: '10:16 (1050x1680)' },
            { value: '1200x1920', label: '10:16 2K (1200x1920)' },
            { value: '2400x3840', label: '10:16 4K (2400x3840)' },
            { value: '4800x7680', label: '10:16 8K (4800x7680)' }
        ];

        function updateResolutionOptions() {
            const isVertical = orientationToggle.checked;
            const resolutions = isVertical ? verticalResolutions : horizontalResolutions;

            resolutionSelect.innerHTML = '';
            resolutions.forEach(res => {
                const option = document.createElement('option');
                option.value = res.value;
                option.textContent = res.label;
                resolutionSelect.appendChild(option);
            });
        }

        orientationToggle.addEventListener('change', updateResolutionOptions);
    </script>
</body>
</html>
<?php
// Output-Buffering beenden und Ausgabe senden
ob_end_flush();
?> 