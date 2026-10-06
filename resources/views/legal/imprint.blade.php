@extends('layout')

@section('title', 'Impressum · StadtNews')

@section('content')
<main class="narrow legal-page">
    <div class="section-heading">
        <div class="eyebrow">STADTNEWS</div>
        <h1>Impressum</h1>
        <p>Angaben zum Betreiber und zur Kontaktaufnahme.</p>
    </div>
    <div class="notice"><strong>Angaben werden ergänzt.</strong> Die gekennzeichneten Platzhalter enthalten noch keine Betreiberangaben.</div>
    <section class="panel" aria-labelledby="operator-heading">
        <h2 id="operator-heading">Betreiber</h2>
        <address>
            [Name / Firma und Rechtsform]<br>
            [Straße und Hausnummer]<br>
            [Postleitzahl und Ort]<br>
            [Land]
        </address>
        <p>Vertreten durch: [Name der vertretungsberechtigten Person, soweit zutreffend]</p>
    </section>
    <section class="panel" aria-labelledby="contact-heading">
        <h2 id="contact-heading">Kontakt</h2>
        <p>E-Mail: [Kontakt-E-Mail]<br>Telefon oder weiterer direkter Kontaktweg: [Kontaktangabe]</p>
    </section>
    <section class="panel" aria-labelledby="additional-heading">
        <h2 id="additional-heading">Weitere Angaben</h2>
        <p>Register und Registernummer: [Angaben, soweit zutreffend]</p>
        <p>Umsatzsteuer-Identifikationsnummer / Wirtschafts-Identifikationsnummer: [Angaben, soweit zutreffend]</p>
        <p>Inhaltlich verantwortlich: [Name und Anschrift, soweit erforderlich]</p>
        <p>Aufsichtsbehörde und berufsrechtliche Angaben: [Angaben, soweit zutreffend]</p>
    </section>
    <p class="legal-source">Grundlage für die Betreiberangaben: <a href="https://www.gesetze-im-internet.de/ddg/__5.html">§ 5 Digitale-Dienste-Gesetz</a>.</p>
</main>
@endsection
