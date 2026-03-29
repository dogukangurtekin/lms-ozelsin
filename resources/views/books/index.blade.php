<x-app-layout>
    <x-slot name="header">Kitaplar</x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(auth()->user()->hasRole(['admin','teacher']))
                <div class="mb-4">
                    <a href="{{ route('books.create') }}" class="inline-flex bg-blue-600 text-white px-4 py-2 rounded">Yeni Kitap</a>
                </div>
            @endif

            @if(session('status'))
                <div class="bg-green-100 text-green-800 p-3 rounded mb-4">{{ session('status') }}</div>
            @endif

            <div class="bg-white shadow sm:rounded-lg p-4">
                <form class="grid md:grid-cols-5 gap-3 mb-4" method="GET" action="{{ route('books.index') }}">
                    <select name="class_id" class="border rounded p-2">
                        <option value="">Sınıf (Tümü)</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}" @selected((string) request('class_id') === (string) $class->id)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                    <select name="lesson" class="border rounded p-2">
                        <option value="">Ders (Tümü)</option>
                        @foreach($lessons as $lesson)
                            <option value="{{ $lesson }}" @selected(request('lesson') === $lesson)>{{ $lesson }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="publisher" placeholder="Yayın" value="{{ request('publisher') }}" class="border rounded p-2">
                    <input type="text" name="title" placeholder="Kitap Adı" value="{{ request('title') }}" class="border rounded p-2">
                    <button class="bg-gray-800 text-white rounded p-2">Filtrele</button>
                </form>

                <div class="mobile-table-wrap">
                    <table class="min-w-full text-sm stack-list-mobile">
                        <thead>
                            <tr class="text-left border-b">
                                <th class="p-2">Başlık</th>
                                <th class="p-2">Sınıf</th>
                                <th class="p-2">Ders</th>
                                <th class="p-2">Oluşturan</th>
                                <th class="p-2">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($books as $book)
                            <tr class="border-b">
                                <td class="p-2">{{ $book->title }}</td>
                                <td class="p-2">{{ $book->grade_level }}</td>
                                <td class="p-2">{{ $book->lesson }}</td>
                                <td class="p-2">{{ $book->creator?->name }}</td>
                                <td class="p-2 align-middle text-left">
                                    <div class="flex flex-wrap items-center justify-start gap-2">
                                        <a href="{{ route('books.show', $book) }}" class="lms-action-btn view">Görüntüle</a>
                                        <a href="{{ route('books.details', $book) }}" class="lms-action-btn info">Kitap Detayı</a>
                                        @if(auth()->user()->hasRole(['admin','teacher']))
                                            <a href="{{ route('books.edit', $book) }}" class="lms-action-btn edit">Düzenle</a>
                                            <form method="POST" action="{{ route('books.destroy', $book) }}" class="inline-flex items-center m-0 p-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="lms-action-btn delete leading-none" onclick="return confirm('Silinsin mi?')">Sil</button>
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
                </div>

                <div class="mt-4">{{ $books->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>

