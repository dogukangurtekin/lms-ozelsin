<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Yeni Kitap</h2></x-slot>
    <div class="py-6"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
    @if($errors->any())
        <div class="mb-4 rounded border border-red-200 bg-red-50 p-3 text-red-700 text-sm">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form method="POST" action="{{ route('books.store') }}" enctype="multipart/form-data" class="bg-white p-6 rounded shadow space-y-3">@csrf
        <input name="title" value="{{ old('title') }}" class="w-full border rounded p-2" placeholder="Baslik" required>
        <textarea name="description" class="w-full border rounded p-2" placeholder="Aciklama">{{ old('description') }}</textarea>
        <select name="grade_level" class="w-full border rounded p-2" required>
            <option value="">Sinif secin</option>
            @foreach($gradeLevels as $gradeLevel)
                <option value="{{ $gradeLevel }}" @selected(old('grade_level') == $gradeLevel)>{{ $gradeLevel }}</option>
            @endforeach
        </select>
        <select name="lesson" class="w-full border rounded p-2" required>
            <option value="">Ders secin</option>
            @foreach($lessons as $lesson)
                <option value="{{ $lesson->name }}" @selected(old('lesson') == $lesson->name)>{{ $lesson->name }}</option>
            @endforeach
        </select>
        <label class="block">Kapak (Opsiyonel) <input type="file" name="cover_image" class="w-full"></label>
        <button class="bg-blue-600 text-white px-4 py-2 rounded">Kaydet</button>
    </form></div></div>
</x-app-layout>
