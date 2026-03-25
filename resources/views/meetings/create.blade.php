<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Gorusme Olustur</h2></x-slot>
    <div class="py-6"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        @if($errors->any())
            <div class="mb-3 rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        <form method="POST" action="{{ route('meetings.store') }}" class="bg-white p-6 rounded shadow space-y-3">@csrf
            <input type="datetime-local" name="meeting_at" value="{{ old('meeting_at') }}" class="w-full border rounded p-2" required>
            <select name="student_id" class="w-full border rounded p-2">
                <option value="">Ogrenci sec</option>
                @foreach($students as $s)<option value="{{ $s->id }}" @selected(old('student_id') == $s->id)>{{ $s->name }}</option>@endforeach
            </select>
            <select name="parent_id" class="w-full border rounded p-2">
                <option value="">Veli sec</option>
                @foreach($parents as $p)<option value="{{ $p->id }}" @selected(old('parent_id') == $p->id)>{{ $p->name }}</option>@endforeach
            </select>
            <select name="status" class="w-full border rounded p-2">
                <option value="scheduled" @selected(old('status', 'scheduled') === 'scheduled')>scheduled</option>
                <option value="completed" @selected(old('status') === 'completed')>completed</option>
                <option value="cancelled" @selected(old('status') === 'cancelled')>cancelled</option>
            </select>
            <textarea name="notes" class="w-full border rounded p-2" placeholder="Notlar">{{ old('notes') }}</textarea>
            <button class="bg-blue-600 text-white px-4 py-2 rounded">Kaydet</button>
        </form>
    </div></div>
</x-app-layout>
