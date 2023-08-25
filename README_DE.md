# SYSTOPIAs Remote-Event-Erweiterung

## Ziele und Funktionsumfang

Diese Erweiterung bietet viele zusätzliche Funktionen zu den Veranstaltungen von
CiviCRM. Insbesondere werden Sie in der Lage sein, Veranstaltungen in CiviCRM zu
konfigurieren, diese Informationen in anderen entfernten Systemen anzuzeigen/zu
verwenden und Anmeldungen für diese Veranstaltungen zurück an CiviCRM zu
übermitteln.

Die allgemeine Idee ist, dass die Mitarbeiter Ihrer Organisation alle relevanten
Veranstaltungseinstellungen in CiviCRM vornehmen können. CiviCRM stellt diese
Informationen dann über seine REST-API zur Verfügung. Die API der Erweiterung
verfügt auch über eingebaute Logik und Aktionen zum Empfang und zur
Aktualisierung von Anmeldungen.

Jedes externe System (dies könnte ein externes System oder das CMS sein, auf dem
Ihr CiviCRM läuft) kann mit der API interagieren und Veranstaltungskalender,
detaillierte Veranstaltungsinformationen, Anmeldeformulare usw. anzeigen und
auch dort eingegebene Informationen an CiviCRM zurücksenden.

All dies setzt voraus, dass Sie über ein externes System verfügen oder ein
solches einrichten, das als Frontend für Ihre Veranstaltungskalender,
Anmeldeformulare usw. fungiert. Falls Sie Ihr System auf der Basis von Drupal 8
aufbauen möchten, werden Sie höchstwahrscheinlich das CiviRemote-Drupal-Modul
ansehen und/oder verwenden wollen, das eine Menge vorgefertigter Funktionen
enthält (https://github.com/systopia/civiremote).

Beachten Sie, dass diese Erweiterung neben den regulären CiviCRM-Anmeldeseiten
verwendet werden kann - Sie können für jedes Event wählen, ob Sie die
Remote-Funktionen verwenden möchten oder nicht.

### Warum diese Erweiterung?

In vielen Fällen möchte oder kann man die in CiviCRM eingebauten Formulare nicht
verwenden, z.B:

* Aus Sicherheitsgründen läuft Ihr CiviCRM innerhalb eines VPN
* Die eingebauten Formulare und die Verarbeitungslogik bieten nicht genügend
  Optionen, um an Ihre Bedürfnisse angepasst zu werden
* Möglicherweise verfügen Sie bereits über ein externes System für Ihre Wähler
  (z.B. einen Mitgliederbereich auf Ihrer Website oder eine
  Kollaborationsplattform), das nicht ohne weiteres mit CiviCRM verbunden werden
  kann.

Diese Erweiterung ist lizenziert
unter [AGPL-3.0](https://www.gnu.org/licenses/agpl-3.0).

## Funktionen

* Verbinden Sie ein anderes (entferntes) System mit CiviCRM, das hochgradig
  anpassbare Veranstaltungskalender, Anmeldefunktionen u.v.a.m. bereitstellen
  kann.
* Vordefinierte Anmeldeprofile einschließlich einer "Ein-Klick-Anmeldung" für
  authentifizierte Benutzer (zusätzliche Profile können relativ einfach
  hinzugefügt werden)
* Verwenden Sie verschiedene Anmeldeprofile für dieselbe Veranstaltung
* Erlauben Sie den Teilnehmer*innen, ihre eigenen Anmeldungen zu ändern und/oder
  zu stornieren
* Alternativer Ansatz zur Nutzung von Veranstaltungsorten

TODO: Vollständige Merkmalliste

## Anforderungen

* PHP ab Version 7.0
* CiviCRM 5.0
* Abhängigkeit zur CiviCRM-Erweiterung "Remote Tools" (de.systopia.remotetools)
* Abhängigkeit zur CiviCRM-Erweiterung "Extended Contact Matcher" (
  de.systopia.xcm)
* Ein System, das als Ihr öffentliches System dient, wie z.B. eine externe
  Website

TODO: Listenerweiterungen, die sich mit Remote-Ereignis-Erweiterungen
integrieren

## Konfiguration

Vergewissern Sie sich nach der Installation der Erweiterung, dass die
Einstellungen des Remote Tools Ihren Bedürfnissen entsprechen (siehe
Dokumentation in de.systopia.remotetools). Fahren Sie dann mit der allgemeinen
Konfiguration fort:

* Navigieren Sie zu >>Verwalten >>Remote-Veranstaltungen - Allgemeine
  Konfiguration.
* Definieren Sie, welche Status eine erneute Anmeldung zu Ihren Veranstaltungen
  blockieren (z.B. abgelehnt) und welche Rollen Sie den Referent*innen zuweisen
  möchten.
* Konfigurieren Sie, welches Standard-Matcher-Profil Sie verwenden möchten (
  XCM-Erweiterung)
* Geben Sie die URLs ein, die für Anmeldungen, Änderungen von Anmeldungen und
  Stornierungen verwendet werden sollen - diese hängen vom von Ihnen verwendeten
  externen System ab. Falls Sie das Drupal-Modul CiviRemote verwenden, können
  Sie https://yourdomain.org/d8civiremote/,
  https://yourdomain.org/d8civiremote/modify/,
  https://yourdomain.org/d8civiremote/cancel/
  verwenden.

Nach der allgemeinen Konfiguration besuchen Sie den neuen Reiter "
Remote-Online-Registrierung" innerhalb der Veranstaltungskonfigurationsmaske von
CiviCRM. Dort finden Sie verschiedene Optionen und Einstellungen, vor allem die
Funktionen für die Nutzung eines externen Systems, die Deaktivierung nativer
CiviCRM-Anmeldeformulare und die zu nutzenden Anmeldeprofile.

Die "Registrierungseinschränkungen" ermöglichen Ihnen Konfigurationen, wann eine
Registrierung verfügbar ist, ob eine Registrierung eine manuelle Überprüfung
erfordert und ob Teilnehmer*innen ihre eigenen Anmeldungen stornieren oder
ändern können.

Die "Textblöcke für öffentliche Veranstaltungen" können von Ihrem externen
System verwendet werden, um die Informationen während des Anmeldevorgangs an den
entsprechenden Stellen anzuzeigen. Wie und wann diese Informationen präsentiert
werden, hängt von der Gestaltung Ihres externen Systems ab.

Die anderen Grundeinstellungen von CiviCRM für Veranstaltungen gelten weiterhin
und können in Ihrem externen System verwendet werden, z.B. Titel,
Beschreibungen, Start- und Enddatum, maximale Teilnehmerzahl,
Wartelisteneinstellungen usw.

### Einstellungen für alternative Veranstaltungsorte

Da die Standardfunktion für Veranstaltungsorte etwas eingeschränkt ist, können
Sie eine Funktion für alternative Veranstaltungsorte verwenden. Wenn Sie dies
tun, wird der reguläre Reiter für den Veranstaltungsort ausgeblendet und es
erscheint eine benutzerdefinierte Reiter, in der Sie einen Kontakt auswählen
können, der den Untertyp "Veranstaltungsort" hat (der Untertyp wird bei der
Installation der Erweiterung erstellt). Außerdem können Sie Informationen über
den Veranstaltungsort angeben, der für die jeweilige Veranstaltung gilt (z.B.
die Raumnummer oder Reisehinweise).

Die API stellt dann die Grunddaten des gewählten Veranstaltungsortes zur
Verfügung, darunter Name, Adresse, Geodaten und die Zusatzinformationen zu
dieser Veranstaltung. Diese Informationen können vom externen System angezeigt
und auf andere Weise verwendet werden.

## Remote-Event-API

Die Erweiterung stellt eine neue API für externe Systeme zur Verfügungzur
Verfügung:

1. ``RemoteEvent.get`` erlaubt die Abfrage nach entfernt verfügbaren
   Veranstaltungen auf die gleiche Weise wie ``Event.get``
1. ``RemoteParticipant.get_form`` liefert eine Standard-Spezifikation für das
   Registrierungsformular, das für die Anmeldung/Abmeldung/Aktualisierung
   benötigt wird
1. ``RemoteParticipant.create`` meldet jemanden für eine Veranstaltung an, indem
   Sie die erforderlichen Formulardaten angeben (wie vom ``RemoteParticipant``
   gefordert)
1. ``RemoteRegistration.validate`` validiert vor der eigentlichen Anmeldung nach
   die angegebenen Anmeldedaten
1. ``RemoteRegistration.cancel`` eine bestehende Registrierung, identifiziert
   durch remote_contact_id oder Token, löschen
1. ``RemoteRegistration.update`` eine bestehende Registrierung, identifiziert
   durch remote_contact_id oder Token, aktualisieren

Bitte beachten Sie, dass alle diese API-Aktionen mit einem unabhängigen Satz von
Berechtigungen ausgestattet sind.

## Erweiterbarkeit und Interoperabilität

Diese Erweiterung nutzt eine Reihe von Symfony-Events, um das Verhalten dieser
Anrufe zu erweitern und anzupassen:

1. ``civi.remoteevent.get.params`` modifizieren / beschränken Abfrageparameter
   auf Ereignisinformationen
1. ``civi.remoteevent.get.result`` Veranstaltungsinformationen ändern /
   erweitern
1. ``civi.remoteevent.registration.validate`` ändern / erweitern der
   Anmeldedaten
1. ``civi.remoteevent.registration.submit``Anmeldevorgang modifizieren /
   erweitern
1. ``civi.remoteevent.registration.cancel`` Ändern / Erweitern des
   Stornierungsprozesses
1. ``civi.remoteevent.registration.geform``Anmeldeformular modifizieren /
   erweitern
1. ``civi.remoteevent.cancellation.geform``Abmeldeformular modifizieren /
   erweitern
1. ``civi.remoteevent.registration_update.geform``Ändern / Erweitern des
   Update-Formulars
1. ``civi.remoteevent.label`` Ändern / Anpassen von bestimmten Benamungen (z.B.
   Workshop Gruppierung)
