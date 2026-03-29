<x-app-layout>
    <x-slot name="header">Ödevler</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(auth()->user()->hasRole(['admin','teacher']))
                <div class="mb-4">
                    <a href="{{ route('assignments.create') }}" class="inline-flex bg-blue-600 text-white px-4 py-2 rounded">Yeni Ödev</a>
                </div>
            @endif

            @if(session('status'))
                <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('status') }}</div>
            @endif

            <div class="bg-white p-4 rounded shadow mobile-table-wrap">
                <table class="min-w-full text-sm stack-list-mobile">
                    <thead>
                        <tr class="border-b text-left">
                            <th class="p-2">Başlık</th>
                            <th class="p-2">Teslim</th>
                            <th class="p-2">Atayan</th>
                            <th class="p-2">Durum</th>
                            <th class="p-2">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($assignments as $assignment)
                        <tr class="border-b">
                            <td class="p-2">{{ $assignment->title }}</td>
                            <td class="p-2">{{ optional($assignment->due_at)->format('d.m.Y H:i') }}</td>
                            <td class="p-2">{{ $assignment->teacher?->name }}</td>
                            <td class="p-2">
                                @if(auth()->user()->hasRole('student'))
                                    @php $mine = $assignment->submissions->where('student_id', auth()->id())->first(); @endphp
                                    @if($mine)
                                        <span class="text-emerald-600">Teslim Edildi</span>
                                    @elseif(optional($assignment->due_at)->isPast())
                                        <span class="text-rose-600">Gecikti</span>
                                    @else
                                        <span class="text-amber-600">Bekliyor</span>
                                    @endif
                                @else
                                    <span class="text-slate-600">{{ $assignment->submissions->count() }} teslim</span>
                                @endif
                            </td>
                            <td class="p-2 align-middle text-left">
                                <div class="flex flex-wrap items-center justify-start gap-2">
                                    <a href="{{ route('assignments.show', $assignment) }}" class="lms-action-btn view">Detay</a>
                                    @if(auth()->user()->hasRole(['admin','teacher']))
                                        <a href="{{ route('assignments.edit', $assignment) }}" class="lms-action-btn edit">Düzenle</a>
                                        <form method="POST" action="{{ route('assignments.destroy', $assignment) }}" class="inline-flex items-center m-0 p-0">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="lms-action-btn delete" onclick="return confirm('Silinsin mi?')">Sil</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="p-2" colspan="5">Kayıt yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="mt-4">{{ $assignments->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>


