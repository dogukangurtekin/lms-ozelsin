<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #0f172a; }
        .title { font-size: 18px; font-weight: bold; margin-bottom: 8px; }
        .meta { margin-bottom: 14px; color: #475569; }
        .section { margin-top: 14px; }
        .section h3 { font-size: 13px; margin: 0 0 6px 0; padding-bottom: 4px; border-bottom: 1px solid #cbd5e1; }
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #cbd5e1; padding: 6px; text-align: left; vertical-align: top; }
        .muted { color: #64748b; }
    </style>
</head>
<body>
    <div class="title">Ogrenci Raporu</div>
    <div class="meta">
        <strong>Ogrenci:</strong> {{ $student->user?->name }}<br>
        <strong>Sinif:</strong> {{ $student->class?->name ?? '-' }}<br>
        <strong>Tarih:</strong> {{ $generatedAt->format('d.m.Y H:i') }}
    </div>

    @if(in_array('identity', $selectedFields, true) && !empty($reportData['identity']))
        <div class="section">
            <h3>Kimlik Bilgileri</h3>
            <table>
                <tr><th>Ad Soyad</th><td>{{ $reportData['identity']['name'] ?? '-' }}</td></tr>
                <tr><th>E-posta</th><td>{{ $reportData['identity']['email'] ?? '-' }}</td></tr>
                <tr><th>Telefon</th><td>{{ $reportData['identity']['phone'] ?? '-' }}</td></tr>
                <tr><th>Ogrenci No</th><td>{{ $reportData['identity']['student_number'] ?? '-' }}</td></tr>
            </table>
        </div>
    @endif

    @if(in_array('attendance', $selectedFields, true) && !empty($reportData['attendance']))
        <div class="section">
            <h3>Yoklama Ozeti</h3>
            <table>
                <tr>
                    <th>Geldi</th><th>Gelmedi</th><th>Izinli</th><th>Raporlu</th>
                </tr>
                <tr>
                    <td>{{ $reportData['attendance']['present_count'] ?? 0 }}</td>
                    <td>{{ $reportData['attendance']['absent_count'] ?? 0 }}</td>
                    <td>{{ $reportData['attendance']['excused_count'] ?? 0 }}</td>
                    <td>{{ $reportData['attendance']['medical_count'] ?? 0 }}</td>
                </tr>
            </table>
        </div>
    @endif

    @if(in_array('assignments', $selectedFields, true) && !empty($reportData['assignments']))
        <div class="section">
            <h3>Odev Ozeti</h3>
            <table>
                <tr><th>Toplam Odev</th><td>{{ $reportData['assignments']['total'] ?? 0 }}</td></tr>
                <tr><th>Teslim Edilen</th><td>{{ $reportData['assignments']['submitted'] ?? 0 }}</td></tr>
            </table>
        </div>
    @endif

    @if(in_array('performance', $selectedFields, true) && !empty($reportData['performance']))
        <div class="section">
            <h3>Performans</h3>
            <table>
                <tr><th>Ortalama Puan</th><td>{{ $reportData['performance']['avg_score'] ?? '-' }}</td></tr>
            </table>
        </div>
    @endif

    @if(in_array('meetings', $selectedFields, true) && !empty($reportData['meetings']))
        <div class="section">
            <h3>Son Gorusme</h3>
            <table>
                <tr><th>Tarih</th><td>{{ optional($reportData['meetings']['last_meeting'] ?? null)->meeting_at ? \\Illuminate\\Support\\Carbon::parse($reportData['meetings']['last_meeting']->meeting_at)->format('d.m.Y H:i') : '-' }}</td></tr>
                <tr><th>Durum</th><td>{{ optional($reportData['meetings']['last_meeting'] ?? null)->status ?? '-' }}</td></tr>
                <tr><th>Not</th><td>{{ optional($reportData['meetings']['last_meeting'] ?? null)->notes ?? '-' }}</td></tr>
            </table>
        </div>
    @endif

    @if(in_array('status', $selectedFields, true) && !empty($reportData['status']))
        <div class="section">
            <h3>Sistem Durumu</h3>
            <table>
                <tr><th>Hesap Durumu</th><td>{{ !empty($reportData['status']['is_active']) ? 'Aktif' : 'Pasif' }}</td></tr>
                <tr><th>Dogum Tarihi</th><td>{{ $reportData['status']['birth_date'] ?? '-' }}</td></tr>
            </table>
        </div>
    @endif

    <p class="muted" style="margin-top:16px;">Bu rapor sistem tarafindan otomatik uretilmistir.</p>
</body>
</html>
