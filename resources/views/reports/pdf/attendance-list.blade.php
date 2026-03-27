<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 6px; }
        p.meta { margin: 0 0 12px; color: #6b7280; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #e5e7eb; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
    <h1>Yoklama Listesi</h1>
    <p class="meta">
        Tarih: {{ \Illuminate\Support\Carbon::parse($date)->format('d.m.Y') }} |
        Ders Sayısı: {{ $lessonCount }} |
        Oluşturulma: {{ $generatedAt->format('d.m.Y H:i') }}
    </p>
    <table>
        <thead>
            <tr>
                <th>Sınıf</th>
                <th>Ders</th>
                <th>Tarih</th>
                <th>Durum</th>
            </tr>
        </thead>
        <tbody>
        @forelse($sessions as $session)
            <tr>
                <td>{{ $session->class?->name ?? '-' }}</td>
                <td>{{ $session->lesson_name }}</td>
                <td>{{ optional($session->attendance_date)->format('d.m.Y') }}</td>
                <td>{{ $session->taken_at ? 'Yoklama alındı' : 'Kayıt yok' }}</td>
            </tr>
        @empty
            <tr><td colspan="4">Kayıt bulunamadı.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
