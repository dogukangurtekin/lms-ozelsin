<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kitaplar</h2>
            @if(auth()->user()->hasRole(['admin','teacher']))
            <a href="{{ route('books.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">Yeni Kitap</a>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('status'))<div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('status') }}</div>@endif
            <div class="bg-white shadow sm:rounded-lg p-4">
                <form class="grid md:grid-cols-3 gap-3 mb-4" method="GET" action="{{ route('books.index') }}">
                    <input type="text" name="grade_level" placeholder="Sinif" value="{{ request('grade_level') }}" class="border rounded p-2">
                    <input type="text" name="lesson" placeholder="Ders" value="{{ request('lesson') }}" class="border rounded p-2">
                    <button class="bg-gray-800 text-white rounded p-2">Filtrele</button>
                </form>

                <div class="mobile-table-wrap">
                <table class="min-w-full text-sm">
                    <thead><tr class="text-left border-b"><th class="p-2">Baslik</th><th class="p-2">Sinif</th><th class="p-2">Ders</th><th class="p-2">Olusturan</th><th class="p-2">Islem</th></tr></thead>
                    <tbody>
                    @forelse($books as $book)
                        <tr class="border-b">
                            <td class="p-2">{{ $book->title }}</td>
                            <td class="p-2">{{ $book->grade_level }}</td>
                            <td class="p-2">{{ $book->lesson }}</td>
                            <td class="p-2">{{ $book->creator?->name }}</td>
                            <td class="p-2 flex flex-wrap gap-2">
                                <a href="{{ route('books.show', $book) }}" class="text-emerald-600">Goruntule</a>
                                <a href="{{ route('books.details', $book) }}" class="text-sky-600">Kitap Detayi</a>
                                @if(auth()->user()->hasRole(['admin','teacher']))
                                    <a href="{{ route('books.edit', $book) }}" class="text-blue-600">Duzenle</a>
                                    <form method="POST" action="{{ route('books.destroy', $book) }}">@csrf @method('DELETE') <button class="text-red-600" onclick="return confirm('Silinsin mi?')">Sil</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td class="p-2" colspan="5">Kayit yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                </div>

                <div class="mt-4">{{ $books->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
