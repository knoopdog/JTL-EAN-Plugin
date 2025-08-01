# Installation des JTL EAN Plugins

## 📦 Schnelle Installation

### Automatische Installation (Empfohlen):
1. Laden Sie die Datei `jtl-ean-plugin.zip` herunter
2. Gehen Sie zu **WordPress Admin** → **Plugins** → **Installieren**
3. Klicken Sie auf **Plugin hochladen**
4. Wählen Sie die `jtl-ean-plugin.zip` Datei aus
5. Klicken Sie auf **Jetzt installieren**
6. Klicken Sie auf **Plugin aktivieren**

### Manuelle Installation:
1. Entpacken Sie die `jtl-ean-plugin.zip` Datei
2. Laden Sie den Ordner `jtl-ean-plugin` per FTP in `/wp-content/plugins/` hoch
3. Gehen Sie zu **WordPress Admin** → **Plugins**
4. Aktivieren Sie das **JTL EAN Plugin**

## ⚙️ Systemanforderungen

- **WordPress**: 5.0 oder höher
- **WooCommerce**: 3.0 oder höher  
- **PHP**: 7.4 oder höher
- **MySQL**: 5.6 oder höher

## 🚀 Nach der Installation

### 1. Plugin-Funktionen prüfen:
- Gehen Sie zu **Produkte** → **Produkt bearbeiten**
- Suchen Sie den Bereich **"Product Identifiers"** 
- Sie sollten die Felder **GTIN/EAN** und **MPN** sehen

### 2. Plugin-Einstellungen:
- Gehen Sie zu **WooCommerce** → **JTL EAN**
- Hier finden Sie Plugin-Statistiken und Deinstallationsoptionen

### 3. JTL Connector konfigurieren (optional):
- Aktivieren Sie die EAN-Übertragung in den JTL Connector-Einstellungen
- Führen Sie eine vollständige Synchronisation durch

## ✅ Installation erfolgreich?

Nach erfolgreicher Installation sollten Sie folgende Features nutzen können:

- **✅ EAN/GTIN-Felder** in der Produktbearbeitung
- **✅ Admin-Menü** unter "WooCommerce → JTL EAN"
- **✅ REST API-Unterstützung** für JTL Connector
- **✅ Bulk-Edit und Quick-Edit** Funktionen

## 🔧 Fehlerbehebung

### Plugin wird nicht angezeigt:
- Überprüfen Sie, ob WooCommerce installiert und aktiviert ist
- Prüfen Sie die PHP-Version (mindestens 7.4)
- Aktivieren Sie WP_DEBUG für detaillierte Fehlermeldungen

### Felder werden nicht gespeichert:
- Überprüfen Sie Ihre Benutzerberechtigungen
- Stellen Sie sicher, dass Sie die Berechtigung "edit_products" haben

### JTL Connector-Integration:
- Aktivieren Sie die EAN-Option in den JTL Connector-Einstellungen
- Führen Sie nach der Installation eine vollständige Synchronisation durch

## 📞 Support

Bei Problemen:
1. Überprüfen Sie die WordPress-Fehlerprotokolle
2. Deaktivieren Sie andere Plugins temporär
3. Kontaktieren Sie den Plugin-Entwickler

---

**Entwickelt von:** Karl Knoop  
**Version:** 1.0.0  
**Kompatibel mit:** WordPress 5.0+, WooCommerce 3.0+