<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 16px 18px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #0f172a;
        }

        .header {
            margin-bottom: 10px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .subtitle {
            font-size: 12px;
            margin-bottom: 2px;
        }

        .meta {
            font-size: 9px;
            color: #475569;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        th, td {
            border: 1px solid #cbd5e1;
            padding: 5px 4px;
            text-align: center;
            vertical-align: middle;
            word-wrap: break-word;
        }

        th {
            background: #e2e8f0;
            font-weight: bold;
        }

        .day-col {
            width: 11%;
            font-weight: bold;
            background: #f8fafc;
        }

        .slot-col {
            width: auto;
        }

        .slot-title {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .slot-meta {
            font-size: 9px;
            color: #334155;
            line-height: 1.25;
        }

        .break-cell {
            background: #fef3c7;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">{{ $title }}</div>
        <div class="subtitle">{{ $subtitle }}</div>
        <div class="meta">Olusturulma: {{ $generatedAt->format('d.m.Y H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th class="day-col">Gun</th>
                @foreach($timeline as $col)
                    <th class="slot-col {{ $col['type'] === 'lunch' ? 'break-cell' : '' }}">
                        <div>{{ $col['type'] === 'lunch' ? $col['title'] : ($col['period_no'] . '. Ders') }}</div>
                        <div>{{ substr($col['start'], 0, 5) }} - {{ substr($col['end'], 0, 5) }}</div>
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($selectedDays as $day)
                <tr>
                    <td class="day-col">{{ $dayOptions->firstWhere('id', $day)['name'] ?? $day }}</td>
                    @foreach($timeline as $col)
                        @if($col['type'] === 'lunch')
                            <td class="break-cell">OGLE ARASI</td>
                        @else
                            @php $slot = $programMap[$day][$col['period_no']] ?? null; @endphp
                            <td>
                                @if($slot)
                                    <div class="slot-title">{{ $slot->lesson?->short_name ?? $slot->lesson?->name ?? $slot->lesson_name }}</div>
                                    @if($mode === 'teacher')
                                        <div class="slot-meta">{{ $slot->class?->name ?? '-' }}</div>
                                    @else
                                        <div class="slot-meta">{{ $slot->teacher?->name ?? '-' }}</div>
                                    @endif
                                @else
                                    <div class="slot-meta">-</div>
                                @endif
                            </td>
                        @endif
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
