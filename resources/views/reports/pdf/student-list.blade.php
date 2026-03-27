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
    <h1>Öğrenci Listesi</h1>
    <p class="meta">
        Sınıf: {{ $selectedClass?->name ?? 'Tüm Sınıflar' }} |
        Toplam: {{ $students->count() }} |
        Oluşturulma: {{ $generatedAt->format('d.m.Y H:i') }}
    </p>
    <table>
        <thead>
            <tr>
                <th>Ad Soyad</th>
                <th>Numara</th>
                <th>Sınıf</th>
                <th>E-posta</th>
            </tr>
        </thead>
        <tbody>
        @forelse($students as $student)
            <tr>
                <td>{{ $student->user?->name }}</td>
                <td>{{ $student->student_number ?? '-' }}</td>
                <td>{{ $student->class?->name ?? '-' }}</td>
                <td>{{ $student->user?->email ?? '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="4">Kayıt bulunamadı.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
