@extends('layout')

@section('title', 'Datenschutz · StadtNews')

@section('content')
<main class="narrow legal-page">
    <div class="section-heading">
        <div class="eyebrow">STADTNEWS</div>
        <h1>Datenschutz</h1>
        <p>Informationen zur Verarbeitung personenbezogener Daten.</p>
    </div>
    <div class="notice"><strong>Entwurf mit offenen Angaben.</strong> Betreiber, Dienstleister, Rechtsgrundlagen und Speicherfristen müssen für den tatsächlichen Betrieb ergänzt werden.</div>
    <section class="panel" aria-labelledby="responsible-heading">
        <h2 id="responsible-heading">Verantwortlicher und Kontakt</h2>
        <p>[Name / Firma und vollständige Anschrift des Verantwortlichen]</p>
        <p>Datenschutzkontakt: [E-Mail-Adresse]<br>Datenschutzbeauftragte Person: [Kontaktdaten, sofern benannt]</p>
        <p>Weitere Betreiberangaben findest du im <a href="{{ route('imprint') }}">Impressum</a>.</p>
    </section>
    <section class="panel" aria-labelledby="visit-heading">
        <h2 id="visit-heading">Aufruf der Website</h2>
        <p>Beim Aufruf werden technische Verbindungsdaten wie IP-Adresse, angefragte Adresse, Zeitpunkt und Browserinformationen an den Webserver übermittelt. Server- und Fehlerprotokolle dienen dem Betrieb, der Fehleranalyse und der Sicherheit der Website.</p>
        <p>Hostinganbieter und Verarbeitungsort: [Anbieter, Anschrift und Standort]<br>Rechtsgrundlage und gegebenenfalls berechtigtes Interesse: [Angaben ergänzen]<br>Speicherdauer der Protokolle: [Frist ergänzen]</p>
    </section>
    <section class="panel" aria-labelledby="account-heading">
        <h2 id="account-heading">Benutzerkonto und Redaktion</h2>
        <p>Bei der Registrierung werden Name, E-Mail-Adresse und ein als Hash gespeichertes Passwort verarbeitet. Mitgliedschaften und Rollen steuern den Zugriff auf Organisationen und Beiträge. Diese Angaben werden benötigt, um ein Konto anzulegen und die Redaktion zu nutzen. Ohne sie ist keine Registrierung möglich; öffentliche Beiträge kannst du ohne Konto lesen.</p>
        <p>Bei der optionalen Nutzung der API werden außerdem Token-Namen, Berechtigungen sowie Nutzungs- und Ablaufzeitpunkte gespeichert.</p>
        <p>Rechtsgrundlage: [Angaben ergänzen]<br>Speicherdauer und Vorgehen bei Kontolöschung: [Angaben ergänzen]</p>
    </section>
    <section class="panel" aria-labelledby="content-heading">
        <h2 id="content-heading">Beiträge, Bilder und Meldungen</h2>
        <p>Veröffentlichte Beiträge, Organisationsprofile und hochgeladene Bilder sind öffentlich abrufbar. Sichtbare Beiträge werden auch über die API und den RSS-Feed bereitgestellt. Entwürfe und redaktionelle Kontodaten werden dadurch nicht veröffentlicht.</p>
        <p>Wenn du einen Beitrag meldest, wird dein eingegebener Meldungsgrund zur Bearbeitung durch die Moderation gespeichert.</p>
        <p>Rechtsgrundlagen und gegebenenfalls berechtigte Interessen: [Angaben ergänzen]<br>Speicher- und Löschfristen für Inhalte, Bilder und Meldungen: [Angaben ergänzen]</p>
    </section>
    <section class="panel" aria-labelledby="cookies-heading">
        <h2 id="cookies-heading">Cookies und lokale Schriftarten</h2>
        <p>Die Website verwendet Cookies für die Sitzung und den Schutz von Formularen. Wenn du beim Login „Angemeldet bleiben“ auswählst, wird zusätzlich ein Cookie zur Wiedererkennung gesetzt.</p>
        <p>Cookie-Namen, Laufzeiten und Rechtsgrundlagen: [Für die eingesetzte Konfiguration ergänzen]</p>
        <p>Open Sans wird direkt von dieser Website geladen. Dafür erfolgt kein Aufruf eines externen Schriftartendienstes. In der Anwendung sind derzeit keine Analyse- oder Werbetracker eingebunden.</p>
    </section>
    <section class="panel" aria-labelledby="mail-heading">
        <h2 id="mail-heading">E-Mail und eingesetzte Dienstleister</h2>
        <p>Für das Zurücksetzen deines Passworts wird deine E-Mail-Adresse verwendet, um dir einen Wiederherstellungslink zu senden. Bei Kontaktaufnahme werden deine Nachricht und die darin enthaltenen Kontaktdaten zur Bearbeitung deines Anliegens verarbeitet.</p>
        <p>E-Mail-Dienstleister und weitere Empfänger: [Anbieter und Zweck ergänzen]<br>Rechtsgrundlagen und Speicherfristen: [Angaben ergänzen]<br>Übermittlungen außerhalb der EU / des EWR und gegebenenfalls Schutzmaßnahmen: [Angaben ergänzen]<br>Speicherfristen für Sicherungskopien: [Angaben ergänzen]</p>
    </section>
    <section class="panel" aria-labelledby="rights-heading">
        <h2 id="rights-heading">Deine Rechte</h2>
        <p>Unter den jeweiligen gesetzlichen Voraussetzungen kannst du Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung und Datenübertragbarkeit verlangen sowie Widerspruch einlegen. Eine erteilte Einwilligung kannst du für die Zukunft widerrufen. Du kannst dich außerdem bei einer Datenschutzaufsichtsbehörde beschweren.</p>
        <p>Wende dich dafür an den oben angegebenen Datenschutzkontakt.<br>Zuständige Aufsichtsbehörde: [Name, Anschrift und Website ergänzen]</p>
        <p>Die Anwendung trifft keine automatisierten Entscheidungen über Personen und enthält kein Profiling.</p>
    </section>
    <p class="legal-source">Informationen zu den Informationspflichten und Betroffenenrechten: <a href="https://www.datenschutz.rlp.de/themen/informationspflichten-und-auskunftsrechte">Landesbeauftragter für den Datenschutz Rheinland-Pfalz</a>.</p>
</main>
@endsection
