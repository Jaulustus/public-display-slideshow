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