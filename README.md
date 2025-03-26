# public-display-slideshow

A lightweight, PHP-based image slideshow for public displays, shop windows, or information terminals. Designed to work on any local or remote PHP-enabled web server, the slideshow cycles through images using smooth animations.

## 🎬 Features

- ✅ Simple animations: horizontal slide, vertical slide, and fade
- 🔁 GIF support with configurable loop count
- 🖥 Fullscreen mode (auto/manual)
- ⏱ Configurable image display time and transition duration
- 🧭 Keyboard navigation (next, previous, fullscreen, UI toggle)
- 🧩 Responsive design for all screen sizes
- ⚙️ Browser-based settings (`config.php`)

## 📁 Supported Formats

### ✅ Current
- JPG / JPEG
- PNG
- WebP (recommended)
- GIF (including loop configuration)

### 🛠 Planned
- WebM videos
- MP4 videos
- YouTube video embedding

## 🚀 Installation

1. Upload `index.php` and `config.php` to your PHP-enabled web server.
2. Create a folder: `assets/pictures/`
3. Add your images to that folder (JPG, PNG, WebP, or GIF).
4. (Optional) Add the Poppins font in `assets/fonts/` for better UI styling.

## 🌐 Embedding the Slideshow

### Option 1: iframe (recommended)
```html
<iframe src="index.php" width="100%" height="400" frameborder="0" allowfullscreen></iframe>
```
```
<a href="index.php" target="_blank">Open slideshow</a>
```
### 🛠 URL Parameters (EN)

Customize behavior using query parameters:

| Parameter   | Description                                               | Example                 |
|-------------|-----------------------------------------------------------|-------------------------|
| `mode`      | Slideshow mode (`fade`, `slide`, `classic`)              | `mode=fade`             |
| `interval`  | Image duration in milliseconds (1000–60000)              | `interval=5000`         |
| `transition`| Transition duration in milliseconds (200–3000)           | `transition=1000`       |
| `ui`        | Show (`1`) or hide (`0`) the UI                          | `ui=1`                  |
| `animation` | Animation type: `slide-horizontal`, `slide-vertical`, `fade` | `animation=slide-horizontal` |


**Example:**
```
index.php?mode=slide&interval=5000&transition=1000&ui=0&animation=fade
```

🔧 Settings Page
Open config.php in a browser to preview and configure your slideshow.

📜 License
MIT License – free for personal or commercial use.


# ----- Deutsch -----

# Werbung Slideshow

Ein leichtgewichtiges PHP-Slideshow-System für Schaufenster, Infobildschirme oder öffentliche Displays. Funktioniert auf jedem Webserver mit PHP-Unterstützung.

🎬 Funktionen
✅ Animationsarten: Horizontaler Slide, vertikaler Slide, Überblenden

🔁 GIF-Unterstützung mit einstellbarer Wiederholung

🖥 Vollbildmodus (automatisch/manuell)

⏱ Konfigurierbare Anzeigedauer & Übergangszeit

🧭 Tastatursteuerung (weiter, zurück, UI umschalten, Vollbild)

🧩 Responsives Design für alle Bildschirmgrößen

⚙️ Einstellungen über config.php im Browser anpassbar

📁 Unterstützte Formate
✅ Aktuell
JPG / JPEG

PNG

WebP (empfohlen)

GIF (inkl. Loop-Konfiguration)

🛠 Geplant
WebM-Videos

MP4-Videos

YouTube-Videos (Einbettung)

🚀 Installation
Lade index.php und config.php auf einen PHP-fähigen Webserver hoch.

Erstelle den Ordner assets/pictures/

Füge deine Bilder dort ein (JPG, PNG, WebP, GIF).

(Optional) Lege Poppins-Schriftarten in assets/fonts/ ab.

🌐 Einbindung
Option 1: iframe (empfohlen)

```html
<iframe src="index.php" width="100%" height="400" frameborder="0" allowfullscreen></iframe>
```

### Als direkter Link

```html
<a href="index.php" target="_blank">Slideshow öffnen</a>
```

### 🛠 URL-Parameter (DE)

Steuere das Verhalten über URL-Parameter:

| Parameter   | Beschreibung                                              | Beispiel                |
|-------------|-----------------------------------------------------------|-------------------------|
| `mode`      | Anzeigemodus (`fade`, `slide`, `classic`)                | `mode=fade`             |
| `interval`  | Bildanzeigedauer in Millisekunden (1000–60000)           | `interval=5000`         |
| `transition`| Übergangszeit in Millisekunden (200–3000)                | `transition=1000`       |
| `ui`        | UI anzeigen (`1`) oder ausblenden (`0`)                  | `ui=1`                  |
| `animation` | Animationstyp: `slide-horizontal`, `slide-vertical`, `fade` | `animation=slide-horizontal` |

Beispiel:
```
index.php?mode=slide&interval=5000&transition=1000&ui=0&animation=slide-vertical
```


🔧 Einstellungsseite
Rufe config.php im Browser auf, um deine Slideshow zu konfigurieren und eine Vorschau zu sehen.

📜 Lizenz
MIT-Lizenz – frei für private und kommerzielle Nutzung.
