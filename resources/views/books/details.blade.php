<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kitap Detayi Yonetimi</h2>
            <a href="{{ route('books.index') }}" class="text-sm text-slate-600">Listeye Don</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if(session('status'))
                <div class="bg-green-100 text-green-800 p-3 rounded">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div class="bg-rose-100 text-rose-800 p-3 rounded">
                    <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <section class="bg-white rounded-xl border border-slate-200 p-4">
                <div class="grid md:grid-cols-4 gap-3 text-sm">
                    <div><span class="text-slate-500">Kitap:</span> <strong>{{ $book->title }}</strong></div>
                    <div><span class="text-slate-500">Sinif:</span> <strong>{{ $book->grade_level }}</strong></div>
                    <div><span class="text-slate-500">Ders:</span> <strong>{{ $book->lesson }}</strong></div>
                    <div><span class="text-slate-500">Olusturan:</span> <strong>{{ $book->creator?->name }}</strong></div>
                </div>
            </section>

            @if(auth()->user()->hasRole(['admin','teacher']))
                <section class="bg-white rounded-xl border border-slate-200 p-4">
                    <h3 class="font-semibold text-slate-800 mb-3">Yeni Kitap Detayi Ekle</h3>
                    <form method="POST" action="{{ route('books.details.store', $book) }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                        @csrf
                        <input name="unit_name" class="rounded-lg border-slate-300" placeholder="Unite" required>
                        <input name="test_name" class="rounded-lg border-slate-300" placeholder="Test Adi" required>
                        <input name="question_count" type="number" min="0" class="rounded-lg border-slate-300" placeholder="Test Soru Sayisi" required>
                        <input name="sort_order" type="number" min="0" class="rounded-lg border-slate-300" placeholder="Sira (Opsiyonel)">
                        <button class="rounded-lg bg-emerald-600 text-white px-4 py-2">Ekle</button>
                        <textarea name="answer_key" rows="3" class="md:col-span-5 rounded-lg border-slate-300" placeholder="Cevap Anahtari (or: 1:A,2:C,3:D...)" required></textarea>
                    </form>
                </section>
            @endif

            <section class="bg-white rounded-xl border border-slate-200 p-4 overflow-x-auto">
                <h3 class="font-semibold text-slate-800 mb-3">Kayitli Uniteler ve Testler</h3>
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-left border-b">
                            <th class="p-2">Unite</th>
                            <th class="p-2">Test</th>
                            <th class="p-2">Soru Sayisi</th>
                            <th class="p-2">Cevap Anahtari</th>
                            <th class="p-2">Sira</th>
                            <th class="p-2">Islem</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($book->tests as $detail)
                            <tr class="border-b align-top">
                                <td class="p-2">{{ $detail->unit_name }}</td>
                                <td class="p-2">{{ $detail->test_name }}</td>
                                <td class="p-2">{{ $detail->question_count }}</td>
                                <td class="p-2 whitespace-pre-wrap">{{ $detail->answer_key }}</td>
                                <td class="p-2">{{ $detail->sort_order }}</td>
                                <td class="p-2">
                                    @if(auth()->user()->hasRole(['admin','teacher']))
                                        <form method="POST" action="{{ route('books.details.destroy', [$book, $detail]) }}" onsubmit="return confirm('Silinsin mi?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded bg-rose-600 text-white px-3 py-1 text-xs">Sil</button>
                                        </form>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-2">Kayit yok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        </div>
    </div>
</x-app-layout>
