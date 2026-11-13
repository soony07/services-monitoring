# IT Services

PHP-Anwendung zur Überwachung von HTTP(S)-Adressen, Suchtexten in HTTP-Antworten und TCP-Ports. Die Oberfläche ist auf Deutsch und Englisch verfügbar und verwendet MVC mit SQLite. Externe Frameworks und Composer sind nicht erforderlich.

## Voraussetzungen

- PHP 8.1 oder neuer mit PDO SQLite, cURL und Sessions
- PHP CLI für Prüfdienst und Tests
- Apache 2.4 für den Serverbetrieb oder der integrierte PHP-Server für lokale Nutzung
- Schreibrecht auf dem Datenverzeichnis für Webserver und Prüfdienst
- Für Tests zusätzlich eine erlaubte PHP-Funktion proc_open

## Lokal starten

1. ZIP entpacken und in den enthaltenen Ordner pulse wechseln.
2. Webserver starten.

       php -S 127.0.0.1:8080 -t public

3. http://127.0.0.1:8080 öffnen. Beim ersten Aufruf Instanzname, Standardsprache und Akzentfarbe festlegen. Die Farbvorschau reagiert direkt auf die Auswahl. Mit Speichern wird die Einrichtung abgeschlossen.
4. In einem zweiten Terminal im selben Projektordner den Prüfdienst starten.

       php worker.php

5. Einen Monitor anlegen. Beide Prozesse laufen lassen. Das Dashboard aktualisiert sich alle 15 Sekunden.


## Auf einem Server betreiben

Die folgenden Schritte gehen von Debian mit systemd und einer Installation unter /var/www/it-services aus.

1. PHP und Apache installieren.

       sudo apt update
       sudo apt install apache2 libapache2-mod-php php-cli php-curl php-sqlite3

   Mit php -v prüfen, dass PHP mindestens Version 8.1 hat. Bei einer bestehenden PHP-FPM-Installation deren Apache-Anbindung verwenden.

2. Projekt nach /var/www/it-services kopieren. Datenordner anlegen und nur diesen für den Dienstbenutzer schreibbar machen.

       sudo mkdir -p /var/www/it-services/data
       sudo chown www-data:www-data /var/www/it-services/data
       sudo chmod 750 /var/www/it-services/data

3. Einen Apache-VirtualHost anlegen, beispielsweise in /etc/apache2/sites-available/it-services.conf. ServerName an die eigene Domain anpassen.

       <VirtualHost *:80>
           ServerName monitoring.example.org
           DocumentRoot /var/www/it-services/public
           <Directory /var/www/it-services/public>
               DirectoryIndex index.php
               Options -Indexes
               AllowOverride None
               Require all granted
           </Directory>
       </VirtualHost>

   Aktivieren und prüfen.

       sudo a2ensite it-services
       sudo apache2ctl configtest
       sudo systemctl reload apache2

   Für externen Zugriff HTTPS und Zugriffsschutz über Apache, VPN oder Reverse Proxy einrichten. Die Anwendung hat keine Benutzeranmeldung. Auch Einstellungen und Monitorverwaltung benötigen diesen Schutz. Die Statusseite ist innerhalb derselben Anwendung erreichbar.

4. Website öffnen und die Ersteinrichtung abschließen.

5. Für automatische Prüfungen /etc/systemd/system/it-services-worker.service mit folgendem Inhalt erstellen.

       [Unit]
       Description=IT Services uptime checker
       After=network-online.target
       Wants=network-online.target

       [Service]
       Type=simple
       User=www-data
       Group=www-data
       WorkingDirectory=/var/www/it-services
       ExecStart=/usr/bin/php /var/www/it-services/worker.php
       Restart=on-failure
       RestartSec=5

       [Install]
       WantedBy=multi-user.target

6. Prüfdienst aktivieren. Er startet anschließend auch beim Neustart des Servers.

       sudo systemctl daemon-reload
       sudo systemctl enable --now it-services-worker
       sudo systemctl status it-services-worker
       sudo journalctl -u it-services-worker -n 50

Alternative für Hosting-Panels mit Cron: jede Minute folgenden Befehl ausführen.

    /usr/bin/php /var/www/it-services/worker.php --once

Bei Cron werden fällige Prüfungen ungefähr im Minutentakt ausgeführt. Kurze Intervalle brauchen den dauerhaften Dienst. Ein Dateilock verhindert gleichzeitig laufende Prüfdienste. Ein fehlendes Lebenszeichen seit mehr als 90 Sekunden wird im Dashboard angezeigt.

