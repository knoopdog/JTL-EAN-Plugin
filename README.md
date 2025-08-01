# JTL EAN Plugin für WooCommerce

Ein schlankes WordPress-Plugin, das EAN/GTIN-Funktionalität für WooCommerce mit JTL Connector-Kompatibilität bereitstellt. Extrahiert die EAN-Funktionalität aus WooCommerce Germanized ohne die zusätzlichen Features.

## Beschreibung

Dieses Plugin wurde entwickelt, um nur die EAN-Funktionalität aus dem umfangreichen WooCommerce Germanized Plugin zu nutzen, ohne alle anderen deutschen Rechtsanpassungen zu benötigen. Es ist speziell für die Zusammenarbeit mit dem JTL WooCommerce Connector optimiert.

## Features

- **EAN/GTIN Unterstützung**: Speichert EAN/GTIN-Nummern für Produkte und Produktvariationen
- **MPN Unterstützung**: Zusätzliche Unterstützung für Herstellernummern (MPN)
- **JTL Connector Kompatibilität**: Vollständige REST API-Integration für JTL-Wawi
- **Admin-Interface**: Benutzerfreundliche Eingabefelder in der Produktbearbeitung
- **Bulk-Edit**: Massen-Bearbeitung von EAN/GTIN-Werten
- **Quick-Edit**: Schnellbearbeitung direkt in der Produktliste
- **WooCommerce Core Fallback**: Unterstützung für die native WooCommerce GTIN-Funktionalität
- **Mehrsprachigkeit**: Unterstützt Deutsch und Englisch, automatische Spracherkennung basierend auf WordPress-Einstellungen

## Systemanforderungen

- WordPress 5.0 oder höher
- WooCommerce 3.0 oder höher
- PHP 7.4 oder höher
- JTL WooCommerce Connector (optional, für JTL-Wawi Integration)

## Installation

1. Laden Sie das Plugin-Verzeichnis in `/wp-content/plugins/` hoch
2. Aktivieren Sie das Plugin über das WordPress Admin-Panel
3. Das Plugin ist sofort einsatzbereit - keine Konfiguration erforderlich

## Deinstallation

Das Plugin bietet **zwei Methoden** für die komplette Deinstallation:

### Automatische Deinstallation
1. Gehen Sie zu "Plugins" → "Installierte Plugins"
2. Deaktivieren Sie das Plugin
3. Klicken Sie auf "Löschen"
4. Alle EAN/GTIN-Daten werden automatisch aus der Datenbank entfernt

### Manuelle One-Click Deinstallation
1. Gehen Sie zu "WooCommerce" → "JTL EAN"
2. Scrollen Sie zum Bereich "Complete Uninstallation"
3. Optional: Exportieren Sie Ihre Daten als Backup (CSV)
4. Bestätigen Sie beide Checkboxen
5. Klicken Sie auf "Completely Uninstall Plugin & Delete All Data"

**⚠️ Wichtiger Hinweis:** Beide Methoden löschen ALLE EAN/GTIN-Daten unwiderruflich!

## Verwendung

### Admin-Interface

Nach der Aktivierung finden Sie in der Produktbearbeitung unter "Allgemein" einen neuen Bereich "Product Identifiers" mit folgenden Feldern:

- **GTIN / EAN**: Globale Handelsartikelnummer
- **MPN**: Herstellernummer

### JTL Connector Integration

Das Plugin ist vollständig mit dem JTL WooCommerce Connector kompatibel:

1. Aktivieren Sie die EAN-Übertragung in den JTL Connector-Einstellungen
2. EAN-Nummern aus JTL-Wawi werden automatisch als GTIN übertragen
3. Nach Änderungen führen Sie eine vollständige Synchronisation durch

### REST API

Das Plugin erweitert die WooCommerce REST API um GTIN/EAN-Felder:

```json
{
  "id": 123,
  "name": "Beispielprodukt",
  "gtin": "4250123456789",
  "mpn": "ABC-123-XYZ"
}
```

#### Beispiel API-Aufruf:

```bash
# Produkt mit GTIN aktualisieren
curl -X PUT https://example.com/wp-json/wc/v3/products/123 \
  -u consumer_key:consumer_secret \
  -H "Content-Type: application/json" \
  -d '{"gtin": "4250123456789"}'
```

## Technische Details

### Datenstruktur

- **GTIN Meta-Key**: `_ts_gtin`
- **MPN Meta-Key**: `_ts_mpn`
- Kompatibel mit WooCommerce Core `global_unique_id`

### Hooks und Filter

Das Plugin bietet verschiedene Hooks für Entwickler:

```php
// GTIN-Wert filtern
add_filter('jtl_ean_product_get_gtin', function($gtin, $jtl_product, $wc_product, $context) {
    // Ihre Anpassungen hier
    return $gtin;
}, 10, 4);

// Helper-Funktion verwenden
$jtl_product = jtl_ean_get_product($product_id);
$gtin = $jtl_product->get_gtin();
```

### Klassen-Struktur

- `JTL_EAN_Plugin`: Hauptplugin-Klasse
- `JTL_EAN_Product`: Produktklasse für EAN/GTIN-Funktionalität
- `JTL_EAN_Admin`: Admin-Interface und Meta-Boxen
- `JTL_EAN_API`: REST API-Erweiterungen

## Migration von WooCommerce Germanized

Falls Sie bereits WooCommerce Germanized verwenden:

1. Die EAN-Daten bleiben erhalten (beide Plugins verwenden `_ts_gtin`)
2. Sie können Germanized deaktivieren, nachdem Sie dieses Plugin aktiviert haben
3. Testen Sie die JTL Connector-Funktionalität nach der Migration

## Häufige Fragen (FAQ)

### Ist dieses Plugin ein Ersatz für WooCommerce Germanized?

Nein, dieses Plugin extrahiert nur die EAN-Funktionalität. Wenn Sie andere Germanized-Features benötigen (Kleinunternehmerregelung, Lieferzeiten, etc.), sollten Sie bei Germanized bleiben.

### Funktioniert es mit Produktvariationen?

Ja, das Plugin unterstützt vollständig Produktvariationen. Jede Variation kann ihre eigene EAN/GTIN haben.

### Kann ich es ohne JTL Connector verwenden?

Ja, das Plugin funktioniert auch unabhängig vom JTL Connector als einfache EAN/GTIN-Lösung für WooCommerce.

### Welche Sprachen werden unterstützt?

Das Plugin unterstützt automatische Spracherkennung und ist verfügbar in:
- **Deutsch (de_DE)** - Standard für deutsche WordPress-Installationen
- **Deutsch Formal (de_DE_formal)** - Sie-Form für offizielle/geschäftliche Umgebungen  
- **Englisch (en_US)** - Standard für internationale Installationen

Die Sprache wird automatisch basierend auf Ihren WordPress-Spracheinstellungen erkannt.

## Entwicklung

### Projektstruktur

```
jtl-ean-plugin/
├── jtl-ean-plugin.php          # Hauptplugin-Datei
├── includes/
│   ├── class-jtl-ean-product.php   # EAN-Produktklasse
│   ├── class-jtl-ean-admin.php     # Admin-Interface
│   └── class-jtl-ean-api.php       # REST API-Erweiterungen
├── languages/                  # Übersetzungsdateien
│   ├── jtl-ean-plugin.pot         # Template für Übersetzungen
│   ├── jtl-ean-plugin-de_DE.po    # Deutsche Übersetzung (Du-Form)
│   ├── jtl-ean-plugin-de_DE.mo    # Kompilierte deutsche Übersetzung
│   ├── jtl-ean-plugin-de_DE_formal.po  # Deutsche Übersetzung (Sie-Form)
│   └── jtl-ean-plugin-de_DE_formal.mo  # Kompilierte formale deutsche Übersetzung
├── uninstall.php               # Deinstallations-Script
├── INSTALLATION.md             # Installationsanleitung
└── README.md                   # Diese Datei
```

### Entwickler-Funktionen

```php
// Produkt-Instanz erstellen
$jtl_product = new JTL_EAN_Product($product);

// GTIN setzen/abrufen
$jtl_product->set_gtin('4250123456789');
$gtin = $jtl_product->get_gtin();

// MPN setzen/abrufen
$jtl_product->set_mpn('ABC-123-XYZ');
$mpn = $jtl_product->get_mpn();

// Änderungen speichern
$jtl_product->save();
```

## Support

Bei Fragen oder Problemen:

1. Überprüfen Sie die [WooCommerce Systemanforderungen](https://woocommerce.com/document/server-requirements/)
2. Stellen Sie sicher, dass JTL Connector aktuell ist
3. Kontaktieren Sie den Plugin-Entwickler

## Lizenz

Dieses Plugin steht unter der GPL v3 oder höher.

## Changelog

### Version 1.0.0
- Erste Veröffentlichung
- EAN/GTIN-Unterstützung für Produkte und Variationen
- JTL Connector-Kompatibilität
- REST API-Integration
- Admin-Interface mit Bulk-Edit und Quick-Edit

## Credits

Entwickelt von Karl Knoop, basierend auf der EAN-Funktionalität von WooCommerce Germanized.