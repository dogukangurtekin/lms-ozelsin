<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Yeni Ödev Oluştur</h2></x-slot>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden" x-data="assignmentWizard()" x-init="initDates()">
        <div class="bg-slate-900 px-4 sm:px-6 py-4 text-white font-semibold flex items-center justify-between gap-2">
            <span>Yeni Ödev Oluştur</span>
            <span class="text-xs text-slate-300">Adımlı Oluşturma</span>
        </div>

        <form method="POST" action="{{ route('assignments.wizard.store') }}" enctype="multipart/form-data" x-ref="wizardForm">
            @csrf

            <div class="px-4 sm:px-6 pt-4 pb-3 border-b border-slate-200 bg-white">
                <div class="flex flex-col sm:flex-row sm:items-start gap-2 sm:gap-3 text-center text-sm">
                    <template x-for="(label, idx) in steps" :key="idx">
                        <div class="w-full sm:min-w-[150px] sm:w-auto">
                            <div class="mx-auto h-10 w-10 sm:h-12 sm:w-12 md:h-14 md:w-14 rounded-full border-2 flex items-center justify-center text-sm sm:text-base md:text-lg font-bold transition"
                                 :class="step > idx+1 ? 'bg-emerald-500 border-emerald-500 text-white' : (step === idx+1 ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-slate-300 text-slate-600')">
                                <span x-text="step > idx+1 ? '✓' : (idx+1)"></span>
                            </div>
                            <p class="mt-2 font-semibold text-slate-700 text-xs sm:text-sm md:text-base" x-text="label"></p>
                        </div>
                    </template>
                </div>
            </div>

            <div class="p-4 sm:p-6 min-h-[420px] sm:min-h-[520px] bg-slate-50/60 border-y border-slate-200">
                <section x-show="step===1" style="display:none;" class="space-y-5">
                    <h3 class="text-xl font-semibold text-slate-800">Dönem ve Ayarlar</h3>
                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Dönem</label>
                            <input name="period" x-model="form.period" class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500" placeholder="2025-2026" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Kime Atanacak?</label>
                            <select name="assign_scope" x-model="form.assign_scope" class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="student">Öğrenci Bazlı</option>
                                <option value="class">Sınıf Bazlı</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Ödev Tipi</label>
                            <select name="assignment_type" x-model="form.assignment_type" class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500" required>
                                <option value="kitaptan_test">Kitaptan Test</option>
                                <option value="soru_havuzu">Soru Havuzu Testi</option>
                                <option value="konu_bazli">Konu Bazlı</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Öğrenci Tipi</label>
                        <select name="student_type" x-model="form.student_type" class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="dersime_girenler">Dersime Girenler</option>
                            <option value="danismanlarim">Danışmanlarım</option>
                        </select>
                    </div>
                </section>

                <section x-show="step===2" style="display:none;" class="space-y-5">
                    <h3 class="text-xl font-semibold text-slate-800">Öğrenci / Sınıf Seçimi</h3>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Sınıf</label>
                        <select name="class_id" x-model="form.class_id" class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="all">Tüm Sınıflar</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="form.assign_scope==='student'" class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="font-semibold text-slate-700 mb-2">Öğrenciler</p>
                        <template x-if="form.class_id">
                            <div>
                                <input type="text" x-model="studentFilter" placeholder="Öğrenci ara..." class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500 mb-3">
                                <div class="max-h-72 overflow-auto space-y-2">
                                    @foreach($students as $student)
                                        <label
                                            x-show="matchStudent('{{ strtolower($student->name) }}') && (String(form.class_id) === 'all' || @js($student->classes->pluck('id')->map(fn($id) => (string) $id)->values()->all()).includes(String(form.class_id)))"
                                            class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50"
                                        >
                                            <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="rounded border-slate-300">
                                            <span>{{ $student->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

                <section x-show="step===3" style="display:none;" class="space-y-5">
                    <h3 class="text-xl font-semibold text-slate-800">Ders Seçimi</h3>
                    <input type="text" x-model="lessonFilter" placeholder="Ders ara..." class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                    <div class="grid md:grid-cols-2 gap-2 max-h-80 overflow-auto">
                        @foreach($lessons as $lesson)
                            <label x-show="matchLesson('{{ strtolower($lesson->name) }}')" class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 hover:bg-slate-50">
                                <input type="radio" name="lesson_id" x-model="form.lesson_id" value="{{ $lesson->id }}" class="border-slate-300" required>
                                <span>{{ $lesson->name }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Derse Ait Kitaplar</label>
                        <select name="book_id" x-model="form.book_id" class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Kitap Seçin</option>
                            <template x-for="book in filteredBooks()" :key="'book-'+book.id">
                                <option :value="book.id" x-text="book.title"></option>
                            </template>
                        </select>
                    </div>
                </section>

                <section x-show="step===4" style="display:none;" class="space-y-5">
                    <h3 class="text-xl font-semibold text-slate-800">İçerik</h3>
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <p class="font-semibold text-slate-700 mb-2">Seçilen Kitabın Testleri</p>
                        <template x-if="!form.book_id">
                            <p class="text-sm text-slate-500">Önce bir kitap seçiniz.</p>
                        </template>
                        <template x-if="form.book_id">
                            <div class="max-h-56 overflow-auto space-y-2">
                                <template x-for="test in filteredTests()" :key="'step4-test-'+test.id">
                                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 hover:bg-slate-50">
                                        <input type="radio" name="book_test_id" :value="test.id" x-model="form.book_test_id" class="border-slate-300">
                                        <span x-text="(test.unit_name ? test.unit_name + ' - ' : '') + test.test_name"></span>
                                    </label>
                                </template>
                            </div>
                        </template>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Ödev Açıklaması / Notu</label>
                        <textarea name="description" rows="10" class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500" placeholder="Açıklama yazın..."></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Ek Dosya</label>
                        <input type="file" name="attachment" class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </section>

                <section x-show="step===5" style="display:none;" class="space-y-5">
                    <h3 class="text-xl font-semibold text-slate-800">Detaylar</h3>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Ödev Başlığı</label>
                        <input name="title" class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500" required>
                    </div>
                    <div class="grid md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Başlama Tarihi</label>
                            <input type="datetime-local" name="start_at" x-model="form.start_at" class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Teslim Tarihi</label>
                            <input type="datetime-local" name="due_at" x-model="form.due_at" class="w-full rounded-lg border-slate-300 focus:border-blue-500 focus:ring-blue-500" required>
                        </div>
                    </div>
                    <p class="text-sm text-slate-600" x-text="dateRangeText()"></p>
                </section>
            </div>

            <div class="border-t border-slate-200 px-4 sm:px-6 py-4 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2 bg-white">
                <button type="button" @click="prev()" :disabled="step===1" class="rounded-lg border border-slate-300 bg-white text-slate-700 px-4 py-2 text-sm font-medium disabled:opacity-50">Geri</button>
                <div class="flex items-center justify-end gap-2">
                    <a href="{{ route('assignments.index') }}" class="rounded-lg border border-slate-300 bg-white text-slate-700 px-4 py-2 text-sm font-medium">İptal</a>
                    <button type="button"
                            @click="primaryAction()"
                            class="rounded-lg px-4 py-2 text-sm font-medium"
                            :style="step===5 ? 'background:#16a34a;color:#ffffff;border:1px solid #15803d;' : 'background:#2563eb;color:#ffffff;border:1px solid #1d4ed8;'"
                            x-text="step===5 ? 'Kaydet' : 'İleri'">
                    </button>
                </div>
            </div>
        </form>
    </div>

    <script>
        function assignmentWizard() {
            return {
                step: 1,
                steps: ['Dönem & Ayarlar', 'Öğrenci/Sınıf', 'Ders', 'İçerik', 'Detaylar'],
                studentFilter: '',
                lessonFilter: '',
                form: {
                    period: new Date().getFullYear() + '-' + (new Date().getFullYear() + 1),
                    assign_scope: 'student',
                    assignment_type: 'kitaptan_test',
                    student_type: 'dersime_girenler',
                    class_id: 'all',
                    lesson_id: '',
                    book_id: '',
                    book_test_id: '',
                    start_at: '',
                    due_at: '',
                },
                books: @json($booksForJs),
                initDates() {
                    const now = new Date();
                    const due = new Date(now.getTime());
                    due.setDate(due.getDate() + 7);
                    this.form.start_at = this.toDatetimeLocal(now);
                    this.form.due_at = this.toDatetimeLocal(due);
                },
                toDatetimeLocal(date) {
                    const pad = (n) => String(n).padStart(2, '0');
                    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
                },
                dateRangeText() {
                    if (!this.form.start_at || !this.form.due_at) return 'Tarih aralığı: -';
                    const start = new Date(this.form.start_at);
                    const due = new Date(this.form.due_at);
                    if (Number.isNaN(start.getTime()) || Number.isNaN(due.getTime())) return 'Tarih aralığı: -';
                    const diffMs = due.getTime() - start.getTime();
                    const days = Math.max(0, Math.round(diffMs / 86400000));
                    return `Tarih aralığı: ${days} gün`;
                },
                next() { if (this.step < 5) this.step++; },
                prev() { if (this.step > 1) this.step--; },
                primaryAction() {
                    if (this.step < 5) {
                        this.next();
                        return;
                    }
                    this.$refs.wizardForm.submit();
                },
                matchStudent(name) { return name.includes(this.studentFilter.toLowerCase()); },
                matchLesson(name) { return name.includes(this.lessonFilter.toLowerCase()); },
                selectedLessonName() {
                    const selected = @json($lessonsForJs);
                    const lesson = selected.find((l) => String(l.id) === String(this.form.lesson_id || ''));
                    return lesson ? lesson.name.toLowerCase().trim() : '';
                },
                filteredBooks() {
                    const lesson = this.selectedLessonName();
                    if (!lesson) return [];
                    const list = this.books.filter((b) => String(b.lesson || '').toLowerCase().trim() === lesson);
                    if (!list.find((b) => String(b.id) === String(this.form.book_id))) {
                        this.form.book_id = '';
                        this.form.book_test_id = '';
                    }
                    return list;
                },
                filteredTests() {
                    if (!this.form.book_id) return [];
                    const book = this.books.find((b) => String(b.id) === String(this.form.book_id));
                    if (!book) return [];
                    if (!book.tests.find((t) => String(t.id) === String(this.form.book_test_id))) {
                        this.form.book_test_id = '';
                    }
                    return book.tests || [];
                },
            }
        }
    </script>
</x-app-layout>
