<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kitap Detayi</h2>
            <a href="{{ route('books.index') }}" class="text-sm text-slate-600">Listeye Don</a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 grid md:grid-cols-3 gap-6">
                <div>
                    @if($book->cover_image)
                        <img src="{{ Storage::url($book->cover_image) }}" alt="{{ $book->title }}" class="w-full rounded-lg border border-slate-200">
                    @else
                        <div class="w-full h-64 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400">Kapak Yok</div>
                    @endif
                </div>

                <div class="md:col-span-2 space-y-3">
                    <h3 class="text-2xl font-bold text-slate-900">{{ $book->title }}</h3>
                    <p class="text-slate-600">{{ $book->description ?: 'Aciklama yok.' }}</p>

                    <div class="grid sm:grid-cols-2 gap-3 text-sm">
                        <div class="rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Sinif Seviyesi:</span> <strong>{{ $book->grade_level }}</strong></div>
                        <div class="rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Ders:</span> <strong>{{ $book->lesson }}</strong></div>
                        <div class="rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Olusturan:</span> <strong>{{ $book->creator?->name }}</strong></div>
                        <div class="rounded-lg bg-slate-50 p-3"><span class="text-slate-500">Tarih:</span> <strong>{{ $book->created_at?->format('d.m.Y H:i') }}</strong></div>
                    </div>

                    @if($book->content_file)
                        <a href="{{ Storage::url($book->content_file) }}" target="_blank" class="inline-flex bg-blue-600 text-white px-4 py-2 rounded">PDF Icerigi Ac</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
