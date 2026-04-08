<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Sınav Giriş Belgeleri</title>
    @php
        $template = in_array(($exam['template'] ?? 'modern'), ['modern', 'classic', 'minimal', 'grid', 'premium'], true) ? $exam['template'] : 'modern';
        $theme = $exam['theme'] ?? [];
        $primaryColor = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($theme['primary'] ?? '')) ? $theme['primary'] : '#0f172a';
        $accentColor = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($theme['accent'] ?? '')) ? $theme['accent'] : '#1d4ed8';
        $borderColor = preg_match('/^#[0-9A-Fa-f]{6}$/', (string) ($theme['border'] ?? '')) ? $theme['border'] : '#cbd5e1';
    @endphp
    <style>
        :root {
            --primary-color: {{ $primaryColor }};
            --accent-color: {{ $accentColor }};
            --border-color: {{ $borderColor }};
        }
        @page { margin: 12mm 10mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 11px; margin: 0; }
        .page-break {
            page-break-after: always;
        }
        .card {
            border: 1.8px solid var(--primary-color);
            border-radius: 18px;
            padding: 14px 16px 12px;
            background: #fff;
            height: 258mm;
            overflow: hidden;
            margin: 0;
        }
        .card.template-classic {
            border-radius: 6px;
            border-width: 2px;
        }
        .card.template-minimal {
            border-width: 1.2px;
            border-radius: 12px;
        }
        .card.template-grid {
            border-width: 2px;
            border-radius: 14px;
            background: #fbfdff;
        }
        .card.template-premium {
            border-width: 2.2px;
            border-radius: 16px;
            background: #ffffff;
        }

        .logo-wrap {
            margin: 0 auto 8px;
            width: 252px;
        }
        .logo-table {
            width: 252px;
            border-collapse: collapse;
            table-layout: fixed;
            margin: 0 auto;
        }
        .logo-table td {
            text-align: center;
            vertical-align: middle;
            padding: 0 5px;
        }
        .logo-slot.logo-primary {
            width: 132px;
            height: 82px;
            display: inline-block;
            overflow: hidden;
        }
        .logo-slot.logo-secondary {
            width: 124px;
            height: 82px;
            display: inline-block;
            overflow: hidden;
        }
        .logo-slot.logo-primary img {
            width: 132px;
            height: 82px;
            object-fit: contain;
            display: block;
        }
        .logo-slot.logo-secondary img {
            width: 124px;
            height: 82px;
            object-fit: contain;
            display: block;
        }

        .title-band {
            margin-top: 6px;
            width: 88%;
            margin-left: auto;
            margin-right: auto;
            border-radius: 10px;
            background: var(--primary-color);
            color: #fff;
            text-align: center;
            padding: 8px 10px;
        }
        .card.template-classic .title-band {
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
            border-radius: 4px;
        }
        .card.template-minimal .title-band {
            background: #f8fafc;
            color: var(--primary-color);
            border: 1px solid var(--border-color);
        }
        .card.template-grid .title-band {
            background: var(--accent-color);
            color: #fff;
            border: 1px solid var(--primary-color);
            border-radius: 6px;
        }
        .card.template-premium .title-band {
            background: linear-gradient(110deg, var(--primary-color), var(--accent-color));
            color: #fff;
            border-radius: 12px;
        }
        .title-band h1 { margin: 0; font-size: 15px; letter-spacing: 0.8px; }

        .student-stack {
            margin-top: 10px;
            width: 88%;
            margin-left: auto;
            margin-right: auto;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 8px 10px;
            background: #fff;
        }
        .info-grid {
            width: 100%;
        }
        .info-row {
            padding: 6px 4px;
            border-bottom: 1px dashed #dbe5f0;
            text-align: center;
        }
        .info-row:last-child { border-bottom: none; }
        .card.template-grid .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }
        .card.template-grid .info-row {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 7px 5px;
            border-bottom: 1px solid var(--border-color);
            background: #fff;
        }
        .card.template-grid .info-row:last-child {
            border-bottom: 1px solid var(--border-color);
        }
        .card.template-premium .info-row {
            border-bottom: 1px solid var(--border-color);
            border-left: 3px solid var(--accent-color);
            padding-left: 8px;
        }
        .stack-item {
            text-align: center;
        }
        .stack-label {
            font-size: 8.6px;
            letter-spacing: 0.8px;
            color: #475569;
            font-weight: 700;
            margin-bottom: 2px;
            text-align: center;
        }
        .stack-value {
            font-size: 13px;
            line-height: 1.2;
            color: #0f172a;
            font-family: DejaVu Sans, sans-serif;
            font-weight: 700;
            text-align: center;
        }
        .stack-value.name {
            font-size: 17px;
        }
        .stack-value.address {
            font-size: 11px;
            line-height: 1.35;
        }

        .session-table {
            width: 78%;
            margin: 8px auto 0;
            border-collapse: collapse;
            font-size: 9.5px;
        }
        .session-table td, .session-table th {
            border: 1px solid var(--border-color);
            padding: 4px 5px;
        }
        .session-title {
            background: #f1f5f9;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-align: center;
        }
        .session-head { background: #f8fafc; font-weight: 700; text-align: center; }
        .session-total td { font-weight: 700; background: #f8fafc; }
        .card.template-grid .session-title {
            background: var(--accent-color);
            color: #fff;
        }
        .card.template-grid .session-head {
            background: #eef6ff;
        }
        .card.template-premium .session-title {
            background: linear-gradient(110deg, var(--primary-color), var(--accent-color));
            color: #fff;
        }
        .card.template-premium .session-head {
            background: #f1f5f9;
        }

        .notes {
            margin-top: 8px;
            border: 1px dashed var(--border-color);
            border-radius: 10px;
            padding: 8px 10px;
            background: #f8fafc;
        }
        .notes-title {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #334155;
            margin-bottom: 4px;
        }
        .notes-text {
            font-size: 10px;
            line-height: 1.45;
            color: #334155;
            white-space: normal;
        }
        .footer {
            margin-top: 8px;
            text-align: center;
            font-size: 9px;
            color: #64748b;
        }
    </style>
</head>
<body>
@foreach($placements as $placement)
        <div class="card template-{{ $template }}">
            <div class="logo-wrap">
                <table class="logo-table">
                    <tr>
                        @if(!empty($logoDataUri) && !empty($secondaryLogoDataUri))
                            <td><span class="logo-slot logo-primary"><img src="{{ $logoDataUri }}" alt="Logo 1" width="132" height="82"></span></td>
                            <td><span class="logo-slot logo-secondary"><img src="{{ $secondaryLogoDataUri }}" alt="Logo 2" width="124" height="82"></span></td>
                        @elseif(!empty($logoDataUri))
                            <td colspan="2"><span class="logo-slot logo-primary"><img src="{{ $logoDataUri }}" alt="Logo" width="132" height="82"></span></td>
                        @elseif(!empty($secondaryLogoDataUri))
                            <td colspan="2"><span class="logo-slot logo-secondary"><img src="{{ $secondaryLogoDataUri }}" alt="Logo" width="124" height="82"></span></td>
                        @endif
                    </tr>
                </table>
            </div>

            <div class="title-band">
                <h1>SINAV GİRİŞ BELGESİ</h1>
            </div>

            <div class="student-stack">
                <div class="info-grid">
                    <div class="info-row">
                        <div class="stack-item">
                            <div class="stack-label">Öğrenci Adı Soyadı</div>
                            <div class="stack-value name">{{ $placement['student_name'] }}</div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="stack-item">
                            <div class="stack-label">Sınava Gireceği Sınıf / Şube</div>
                            <div class="stack-value">{{ $placement['room_name'] }}</div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="stack-item">
                            <div class="stack-label">Sıra Numarası</div>
                            <div class="stack-value">{{ $placement['seat_number'] }}</div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="stack-item">
                            <div class="stack-label">Sınav Tarihi</div>
                            <div class="stack-value">{{ $exam['date']->format('d.m.Y') }}</div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="stack-item">
                            <div class="stack-label">Sınav Saati</div>
                            <div class="stack-value">{{ $exam['time'] ?: '-' }}</div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="stack-item">
                            <div class="stack-label">Adres</div>
                            <div class="stack-value address">{{ $exam['address'] ?: '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            @php
                $oneRows = collect($exam['session_one_rows'] ?? []);
                $oneTotal = $oneRows->sum(fn($r) => (int) ($r['count'] ?? 0));
                $twoRows = collect($exam['session_two_rows'] ?? []);
                $twoTotal = $twoRows->sum(fn($r) => (int) ($r['count'] ?? 0));
            @endphp

            @if($oneRows->count() > 0)
                <table class="session-table">
                    <tr><th class="session-title" colspan="2">{{ $exam['session_one_title'] ?? 'BİRİNCİ OTURUM - SÖZEL ALAN' }}</th></tr>
                    <tr>
                        <th class="session-head">Alt Testler</th>
                        <th class="session-head" style="width:26%;">Soru Sayısı</th>
                    </tr>
                    @foreach($oneRows as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td style="text-align:center;">{{ $row['count'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="session-total">
                        <td>Toplam</td>
                        <td style="text-align:center;">{{ $oneTotal }}</td>
                    </tr>
                </table>
            @endif

            @if($twoRows->count() > 0)
                <table class="session-table">
                    <tr><th class="session-title" colspan="2">{{ $exam['session_two_title'] ?? 'İKİNCİ OTURUM - SAYISAL ALAN' }}</th></tr>
                    <tr>
                        <th class="session-head">Alt Testler</th>
                        <th class="session-head" style="width:26%;">Soru Sayısı</th>
                    </tr>
                    @foreach($twoRows as $row)
                        <tr>
                            <td>{{ $row['name'] }}</td>
                            <td style="text-align:center;">{{ $row['count'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="session-total">
                        <td>Toplam</td>
                        <td style="text-align:center;">{{ $twoTotal }}</td>
                    </tr>
                </table>
            @endif

            <div class="notes">
                <div class="notes-title">Sınav Bilgilendirmesi</div>
                <div class="notes-text">{!! nl2br(e($exam['notes'] ?: 'Öğrencinin sınav saatinden en az 15 dakika önce ilgili sınıfta hazır bulunması, giriş belgesini ve gerekli kimlik bilgisini yanında bulundurması gerekmektedir.')) !!}</div>
            </div>

            <div class="footer">Özelsin Koleji Bilişim Yönetim Sistemleri v.1.0.</div>

        </div>
        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
@endforeach
</body>
</html>
