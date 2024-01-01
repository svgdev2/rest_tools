
# Toolsammlung für Konvertierung und Datenverarbeitung

## Überblick
Diese Toolsammlung, bestehend aus verschiedenen PHP-Skripten, dient der effizienten Konvertierung und Verarbeitung von Daten. Jedes Tool in dieser Sammlung bietet spezialisierte Funktionen, um über HTTP-REST-Anfragen verschiedene Arten von Datenkonvertierungen und -manipulationen durchzuführen.

## Funktionalitäten
Die Toolsammlung umfasst:

- **`convert2UTF8.php`**: Konvertiert Dateien in das UTF-8-Format, um Kodierungsprobleme zu vermeiden.
- **`create_bulk_dataverse_operation.php`**: Ermöglicht die Massenerstellung von Operationen in Dataverse-Umgebungen, ideal für die Verwaltung und Automatisierung.
- **`csv2json.php`**: Wandelt CSV-Dateien in das JSON-Format um, was die Integration in Webanwendungen und APIs erleichtert.
- **`excel2csv.php`**: Konvertiert Excel-Dateien (XLS, XLSX) in CSV, was die Datenanalyse und -verarbeitung vereinfacht.
- **`formatJson.php`**: Formatierung von JSON-Dateien für verbesserte Lesbarkeit und Konsistenz.
- **`hash.php`**: Generiert Hashes für gegebene Daten, nützlich für Sicherheitsüberprüfungen und Datenintegrität.

## Installation
Folgen Sie diesen Schritten, um die Toolsammlung einzurichten:

1. **Download und Einrichtung**:
   - Laden Sie die PHP-Skripte herunter und speichern Sie sie in Ihrem bevorzugten Arbeitsverzeichnis.
   - Stellen Sie sicher, dass PHP auf Ihrem System installiert ist und ordnungsgemäß funktioniert.

2. **Konfiguration**:
   - Erstellen Sie im Hauptverzeichnis der PHP-Skripte zwei Unterordner: `config` und `temp`.
   - Im `config`-Ordner erstellen Sie eine `config.ini`-Datei mit folgendem Inhalt:
     ```
     ; config.ini
     api_key = <IHR_PASSWORT>
     temp_path = temp
     ```
     Diese Datei dient der Konfiguration der Skripte und sollte entsprechend angepasst werden.
   - Der `temp`-Ordner wird für das temporäre Speichern von Dateien verwendet.

## Nutzung
Die Skripte sind so konzipiert, dass sie HTTP-REST-Anfragen entgegennehmen. Die spezifischen Anforderungen für die Ausführung jedes Skripts, einschließlich der notwendigen Parameter und des Formats der HTTP-Requests, sind in den Kommentaren innerhalb jedes Skripts detailliert beschrieben.

## Lizenz
Diese Toolsammlung ist unter der [MIT-Lizenz](https://opensource.org/licenses/MIT) veröffentlicht. Dies gewährleistet eine breite Nutzung und Verteilung unter geringen Einschränkungen.

## Mitwirkende und Danksagungen
- Hier können Sie Mitwirkende und Organisationen nennen, die zur Entwicklung dieser Toolsammlung beigetragen haben.
