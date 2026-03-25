<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Yeni Odev</h2></x-slot>
    <div class="py-6"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8"><form method="POST" action="{{ route('assignments.store') }}" enctype="multipart/form-data" class="bg-white p-6 rounded shadow space-y-3">@csrf
        <input name="title" class="w-full border rounded p-2" placeholder="Baslik" required>
        <textarea name="description" class="w-full border rounded p-2" placeholder="Aciklama"></textarea>
        <input type="datetime-local" name="due_at" class="w-full border rounded p-2" required>
        <select name="class_id" class="w-full border rounded p-2"><option value="">Sinif sec</option>@foreach($classes as $class)<option value="{{ $class->id }}">{{ $class->name }}</option>@endforeach</select>
        <select name="student_id" class="w-full border rounded p-2"><option value="">Ogrenciye ozel (opsiyonel)</option>@foreach($students as $student)<option value="{{ $student->id }}">{{ $student->name }}</option>@endforeach</select>
        <label class="block">Ek Dosya <input type="file" name="attachment" class="w-full"></label>
        <button class="bg-blue-600 text-white px-4 py-2 rounded">Kaydet</button>
    </form></div></div>
</x-app-layout>
