<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LessonController extends Controller
{
    public function index()
    {
        $lessons = Lesson::with('teachers:id,name')->orderBy('name')->get();
        $teachers = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return view('lessons.index', compact('lessons', 'teachers'));
    }

    public function store(Request $request)
    {
        $hasShortName = Schema::hasColumn('lessons', 'short_name');
        $data = $request->validate([
            'name' => 'required|string|max:120|unique:lessons,name',
            'short_name' => 'nullable|string|max:30',
            'code' => 'nullable|string|max:40|unique:lessons,code',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $name = trim((string) $data['name']);
        $shortName = isset($data['short_name']) ? trim((string) $data['short_name']) : '';
        $code = isset($data['code']) ? trim((string) $data['code']) : '';
        $description = isset($data['description']) ? trim((string) $data['description']) : '';

        $payload = [
            'name' => $name,
            'code' => $code !== '' ? $code : null,
            'description' => $description !== '' ? $description : null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
        if ($hasShortName) {
            $payload['short_name'] = $shortName !== '' ? $shortName : null;
        }

        Lesson::create($payload);

        return back()->with('status', 'Ders eklendi.');
    }

    public function assignTeacher(Request $request, Lesson $lesson)
    {
        $data = $request->validate([
            'teacher_id' => 'required|exists:users,id',
        ]);

        $lesson->teachers()->syncWithoutDetaching([(int) $data['teacher_id']]);
        return back()->with('status', 'Ogretmen-ders eslestirmesi kaydedildi.');
    }

    public function update(Request $request, Lesson $lesson)
    {
        $hasShortName = Schema::hasColumn('lessons', 'short_name');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('lessons', 'name')->ignore($lesson->id)],
            'short_name' => 'nullable|string|max:30',
            'code' => ['nullable', 'string', 'max:40', Rule::unique('lessons', 'code')->ignore($lesson->id)],
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $name = trim((string) $data['name']);
        $shortName = isset($data['short_name']) ? trim((string) $data['short_name']) : '';
        $code = isset($data['code']) ? trim((string) $data['code']) : '';
        $description = isset($data['description']) ? trim((string) $data['description']) : '';

        $payload = [
            'name' => $name,
            'code' => $code !== '' ? $code : null,
            'description' => $description !== '' ? $description : null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
        if ($hasShortName) {
            $payload['short_name'] = $shortName !== '' ? $shortName : null;
        }

        $lesson->update($payload);

        return back()->with('status', 'Ders guncellendi.');
    }

    public function destroy(Lesson $lesson)
    {
        $lesson->delete();
        return back()->with('status', 'Ders silindi.');
    }

    public function import(Request $request)
    {
        $hasShortName = Schema::hasColumn('lessons', 'short_name');
        $rows = $this->extractRowsFromRequest($request);
        if (empty($rows)) {
            return $this->failure($request, 'Dosya okunamadı veya satır bulunamadı.');
        }

        $created = 0;
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $code = trim((string) ($row['code'] ?? ''));
            if (Lesson::where('name', $name)->exists()) {
                continue;
            }
            if ($code !== '' && Lesson::where('code', $code)->exists()) {
                continue;
            }

            $payload = [
                'name' => $name,
                'code' => $code !== '' ? $code : null,
                'description' => $row['description'] ?? null,
                'is_active' => (bool) (($row['is_active'] ?? '1') === '1' || ($row['is_active'] ?? true) === true),
            ];
            if ($hasShortName) {
                $payload['short_name'] = ! empty($row['short_name']) ? trim((string) $row['short_name']) : null;
            }

            Lesson::create($payload);
            $created++;
        }

        if ($created === 0) {
            return $this->failure($request, 'Yeni ders eklenmedi. Ders adları/kodlar tekrar ediyor olabilir.');
        }
        return $this->success($request, "Toplu ders kaydı tamamlandı. Eklenen: {$created}");
    }

    public function downloadTemplate(): StreamedResponse
    {
        $rows = [
            ['ders_adi', 'kisa_ders_adi', 'ders_kodu', 'aciklama', 'aktif_mi'],
            ['Matematik', 'MAT', 'MAT-01', 'Temel Matematik Dersi', '1'],
        ];

        return response()->streamDownload(function () use ($rows) {
            foreach ($rows as $row) {
                echo implode("\t", $row)."\r\n";
            }
        }, 'ders_sablonu.xls', [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
        ]);
    }

    private function extractRowsFromRequest(Request $request): array
    {
        $jsonRows = $request->input('parsed_rows_json');
        if (is_string($jsonRows) && $jsonRows !== '') {
            $decoded = json_decode($jsonRows, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map(
                    fn($row) => is_array($row) ? $this->normalizeRowKeys($row) : null,
                    $decoded
                )));
            }
        }

        $file = $this->validateImportFile($request);
        return $this->parseImportFile($file);
    }

    private function validateImportFile(Request $request): UploadedFile
    {
        $request->validate(['import_file' => 'required|file|max:5120']);
        /** @var UploadedFile $file */
        $file = $request->file('import_file');
        $ext = strtolower((string) $file->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'txt', 'xls', 'xlsx'], true)) {
            abort(422, 'Desteklenmeyen dosya tipi.');
        }
        return $file;
    }

    private function parseImportFile(UploadedFile $file): array
    {
        $content = file_get_contents($file->getRealPath()) ?: '';
        return $this->parseDelimitedContent($content, true);
    }

    private function parseDelimitedContent(string $content, bool $tryHtmlTable = false): array
    {
        $content = trim($content);
        if ($content === '') return [];

        if ($tryHtmlTable && str_contains(strtolower($content), '<table')) {
            return $this->parseHtmlTableContent($content);
        }

        $lines = preg_split('/\r\n|\n|\r/', $content) ?: [];
        if (empty($lines)) return [];

        $delimiter = $this->detectDelimiter($lines[0]);
        $headers = array_map(fn($h) => $this->normalizeHeader((string) $h), str_getcsv($lines[0], $delimiter));
        $rows = [];
        foreach (array_slice($lines, 1) as $line) {
            if (trim($line) === '') continue;
            $values = str_getcsv($line, $delimiter);
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = isset($values[$i]) ? trim((string) $values[$i]) : null;
            }
            $rows[] = $row;
        }
        return $rows;
    }

    private function parseHtmlTableContent(string $content): array
    {
        $rows = [];
        $headers = [];
        preg_match_all('/<tr[^>]*>(.*?)<\/tr>/is', $content, $trMatches);
        foreach ($trMatches[1] as $trIndex => $trInner) {
            preg_match_all('/<t[hd][^>]*>(.*?)<\/t[hd]>/is', $trInner, $cellMatches);
            $cells = array_map(static fn($cell) => trim(strip_tags(html_entity_decode($cell))), $cellMatches[1]);
            if ($trIndex === 0) {
                $headers = array_map(fn($h) => $this->normalizeHeader((string) $h), $cells);
                continue;
            }
            if (empty($cells)) continue;
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = $cells[$i] ?? null;
            }
            $rows[] = $row;
        }
        return $rows;
    }

    private function detectDelimiter(string $line): string
    {
        $delimiters = [',', ';', "\t", '|'];
        $scores = [];
        foreach ($delimiters as $d) $scores[$d] = count(str_getcsv($line, $d));
        arsort($scores);
        return (string) array_key_first($scores);
    }

    private function normalizeHeader(string $header): string
    {
        $h = preg_replace('/^\xEF\xBB\xBF/u', '', $header) ?? $header;
        $h = mb_strtolower(trim($h));
        $h = str_replace(['ç', 'ğ', 'ı', 'İ', 'ö', 'ş', 'ü'], ['c', 'g', 'i', 'i', 'o', 's', 'u'], $h);
        $h = str_replace([' ', '-', '/'], '_', $h);
        $h = preg_replace('/_+/', '_', $h) ?? $h;
        $h = trim($h, "_ \t\n\r\0\x0B");

        return [
            'ders_adi' => 'name',
            'ad' => 'name',
            'name' => 'name',
            'ders_kodu' => 'code',
            'kisa_ders_adi' => 'short_name',
            'kisa_ad' => 'short_name',
            'short_name' => 'short_name',
            'kod' => 'code',
            'code' => 'code',
            'aciklama' => 'description',
            'description' => 'description',
            'aktif_mi' => 'is_active',
            'is_active' => 'is_active',
        ][$h] ?? $h;
    }

    private function normalizeRowKeys(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[$this->normalizeHeader((string) $key)] = is_string($value) ? trim($value) : $value;
        }
        return $normalized;
    }

    private function success(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }
        return back()->with('status', $message);
    }

    private function failure(Request $request, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }
        return back()->withErrors(['import_file' => $message]);
    }
}
