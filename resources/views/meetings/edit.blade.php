<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Gorusme Duzenle</h2></x-slot>
    <div class="py-6"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8"><form method="POST" action="{{ route('meetings.update', $meeting) }}" class="bg-white p-6 rounded shadow space-y-3">@csrf @method('PUT')
        <input type="datetime-local" name="meeting_at" value="{{ optional($meeting->meeting_at)->format('Y-m-d\TH:i') }}" class="w-full border rounded p-2" required>
        <select name="student_id" class="w-full border rounded p-2"><option value="">Ogrenci sec</option>@foreach($students as $s)<option value="{{ $s->id }}" @selected($meeting->student_id==$s->id)>{{ $s->name }}</option>@endforeach</select>
        <select name="parent_id" class="w-full border rounded p-2"><option value="">Veli sec</option>@foreach($parents as $p)<option value="{{ $p->id }}" @selected($meeting->parent_id==$p->id)>{{ $p->name }}</option>@endforeach</select>
        <select name="status" class="w-full border rounded p-2"><option value="scheduled" @selected($meeting->status=='scheduled')>scheduled</option><option value="completed" @selected($meeting->status=='completed')>completed</option><option value="cancelled" @selected($meeting->status=='cancelled')>cancelled</option></select>
        <textarea name="notes" class="w-full border rounded p-2">{{ $meeting->notes }}</textarea>
        <button class="bg-blue-600 text-white px-4 py-2 rounded">Guncelle</button>
    </form></div></div>
</x-app-layout>
