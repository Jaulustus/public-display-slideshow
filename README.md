# public-display-slideshow
A PHP-based slideshow system for public screens. Runs on any local webserver and cycles through images for advertising or info purposes.



# Advertising Slideshow

An elegant, modern image gallery slideshow for public displays and advertising purposes. Offers various animation effects and configuration options. Runs on any local or remote PHP-enabled web server.

## Features

- **Multiple animation types**: Fade, horizontal slide, vertical slide, zoom, 3D flip, rotation, blur, and 3D cube
- **Fullscreen mode**: Automatic or manual fullscreen toggle
- **Configurable display times**: Adjustable transition and display durations
- **Responsive design**: Works on all screen sizes
- **Keyboard controls**: Intuitive navigation with hotkeys
- **Cross-browser**: Compatible with all modern browsers

## Supported Formats

### Current Formats
- JPG / JPEG
- PNG
- WebP (recommended)
- GIF

### Planned Formats (future versions)
- WebM videos
- MP4 videos

## Installation

1. Upload the files `index.php` and `config.php` to your web server
2. Create a directory `assets/advertising/` on your server
3. Place your images inside the `assets/advertising/` folder (supported formats: JPG, JPEG, PNG, WEBP, GIF)
4. (Optional) Create a directory `assets/fonts/` and add the Poppins font files for custom font support

## Usage

You can embed the slideshow in two ways:

### As an iframe (recommended)

```html
<iframe src="index.php" width="100%" height="400" frameborder="0" allowfullscreen></iframe>
```
## URL Parameters

You can use various URL parameters to customize the display:

- **mode**: Display mode (`fade`, `slide`, `classic`)
- **interval**: Time between image transitions in milliseconds (1000–60000)
- **transition**: Duration of the transition in milliseconds (200–3000)
- **ui**: Show (`1`) or hide (`0`) the UI controls
- **animation**: Animation type (`slide-horizontal`, `slide-vertical`, `fade`, `zoom-in`, `zoom-out`, `flip`, `rotate`, `blur`, `cube`)

**Example:**
```
index.php?mode=slide&interval=5000&transition=1000&ui=0&animation=flip
```


## Keyboard Control

- **Arrow keys (left/right)**: Navigate to the previous or next image
- **Spacebar**: Start or stop the slideshow
- **F**: Toggle fullscreen mode
- **ESC**: Stop the slideshow or exit fullscreen mode
- **1**: Switch to fade mode
- **2**: Switch to slide mode
- **3**: Switch to classic mode

## Settings Page

Open `config.php` in your browser to configure the slideshow and preview your settings.

## License

MIT License – Free to use for any purpose.


# ----- Deutsch -----

# Werbung Slideshow

Eine elegante, moderne Bildergalerie-Slideshow für Werbezwecke mit verschiedenen Animationseffekten und Konfigurationsmöglichkeiten.

## Features

- **Mehrere Animationstypen**: Überblenden, Horizontaler Slide, Vertikaler Slide, Zoom, 3D-Flip, Rotation, Unschärfe und 3D-Würfel
- **Vollbild-Modus**: Automatischer oder manueller Vollbildmodus
- **Konfigurierbare Anzeigezeiten**: Einstellbare Übergangszeiten und Anzeigedauern
- **Responsive Design**: Funktioniert auf allen Bildschirmgrößen
- **Tastatursteuerung**: Intuitive Navigation mit Tastenkombinationen
- **Browserübergreifend**: Funktioniert in allen modernen Browsern

## Unterstützte Formate

### Aktuelle Formate
- JPG/JPEG
- PNG
- WebP (empfohlen)
- GIF

### Geplante Formate (kommende Versionen)
- WebM Videos
- MP4 Videos

## Installation

1. Laden Sie die Dateien `index.php` und `config.php` auf Ihren Webserver hoch
2. Erstellen Sie ein Verzeichnis `assets/bilder/` auf Ihrem Server
3. Legen Sie Ihre Bilder im `assets/bilder/` Verzeichnis ab (unterstützte Formate: JPG, JPEG, PNG, WEBP, GIF)
4. (Optional) Erstellen Sie ein Verzeichnis `assets/fonts/` und legen Sie die Poppins-Schriftarten dort ab

## Verwendung

Die Slideshow kann auf zwei Arten eingebunden werden:

### Als iframe (empfohlen)

```html
<iframe src="index.php" width="100%" height="400" frameborder="0" allowfullscreen></iframe>
```

### Als direkter Link

```html
<a href="index.php" target="_blank">Index öffnen</a>
```

## URL-Parameter

Sie können verschiedene Parameter in der URL verwenden, um die Darstellung anzupassen:

- **mode**: Anzeigemodus (fade, slide, classic)
- **interval**: Zeit zwischen Bildwechseln in ms (1000-60000)
- **transition**: Übergangszeit in ms (200-3000)
- **ui**: Bedienelemente anzeigen (1) oder verstecken (0)
- **animation**: Animationstyp (slide-horizontal, slide-vertical, fade, zoom-in, zoom-out, flip, rotate, blur, cube)

Beispiel:
```
index.php?mode=slide&interval=5000&transition=1000&ui=0&animation=flip
```

## Tastatursteuerung

- **Pfeiltasten (links/rechts)**: Vorheriges/nächstes Bild
- **Leertaste**: Diashow starten/stoppen
- **F**: Vollbildmodus ein/aus
- **ESC**: Diashow stoppen oder Vollbildmodus beenden
- **1**: Fade-Modus
- **2**: Slide-Modus
- **3**: Classic-Modus

## Einstellungsseite

Öffnen Sie `config.php` im Browser, um die Slideshow zu konfigurieren und eine Vorschau der Einstellungen zu sehen.

## Lizenz

MIT-Lizenz - Frei zur Verwendung für jeden Zweck. 
