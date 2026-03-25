<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">Odev Detayi</h2></x-slot>
    <div class="py-6"><div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
        <div class="bg-white p-4 rounded shadow">
            <h3 class="text-lg font-semibold">{{ $assignment->title }}</h3>
            <p class="text-gray-700">{{ $assignment->description }}</p>
            <p class="text-sm text-gray-500 mt-2">Son teslim: {{ optional($assignment->due_at)->format('d.m.Y H:i') }}</p>
            @if($assignment->attachment)
                <a href="{{ Storage::url($assignment->attachment) }}" target="_blank" class="inline-block mt-3 text-blue-600">Ek Dosyayi Ac</a>
            @endif
        </div>

        @if(auth()->user()->hasRole('student'))
        <form method="POST" action="{{ route('assignments.submit', $assignment) }}" enctype="multipart/form-data" class="bg-white p-4 rounded shadow space-y-3">@csrf
            @php $mySubmission = $assignment->submissions->where('student_id', auth()->id())->first(); @endphp
            @if($mySubmission)
                <div class="text-sm text-emerald-700">Bu odevi daha once teslim ettiniz. Yeni dosya yuklerseniz guncellenir.</div>
            @endif
            <label class="block">Teslim Dosyasi <input type="file" name="submission_file" required class="w-full"></label>
            <textarea name="comment" class="w-full border rounded p-2" placeholder="Not"></textarea>
            <button class="bg-blue-600 text-white px-4 py-2 rounded">Teslim Et</button>
        </form>
        @endif

        @if(auth()->user()->hasRole(['admin','teacher']))
        <div class="bg-white p-4 rounded shadow">
            <h4 class="font-semibold mb-3">Teslimler</h4>
            @forelse($assignment->submissions as $submission)
                <div class="border rounded p-3 mb-3">
                    <div class="text-sm">Ogrenci: {{ $submission->student?->name }}</div>
                    <div class="text-sm">Puan: {{ $submission->score ?? '-' }}</div>
                    @if($submission->submission_file)
                        <a href="{{ Storage::url($submission->submission_file) }}" target="_blank" class="text-blue-600 text-sm">Teslim Dosyasini Ac</a>
                    @endif
                    <form method="POST" action="{{ route('assignment-submissions.grade', $submission) }}" class="grid md:grid-cols-3 gap-2 mt-2">@csrf
                        <input type="number" min="0" max="100" name="score" value="{{ $submission->score }}" class="border rounded p-2" placeholder="Puan" required>
                        <input type="text" name="teacher_feedback" value="{{ $submission->teacher_feedback }}" class="border rounded p-2" placeholder="Yorum">
                        <button class="bg-green-600 text-white rounded p-2">Kaydet</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-gray-500">Henuz teslim yok.</p>
            @endforelse
        </div>
        @endif
    </div></div>
</x-app-layout>
