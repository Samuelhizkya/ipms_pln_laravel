<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MasterKpi;
use App\Models\RealisasiKpi;
use App\Models\TargetKpi;
use App\Models\Ulp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MobileMasterKpiController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            $request->user()?->role === 'admin',
            403,
            'Pengelolaan Master KPI hanya tersedia untuk Admin.'
        );
    }

    private function isUsed(MasterKpi $record): bool
    {
        if (!$record->exists) {
            return false;
        }

        return TargetKpi::where('id_kpi', $record->id_kpi)->exists()
            || RealisasiKpi::where('id_kpi', $record->id_kpi)->exists();
    }

    private function version(MasterKpi $record): string
    {
        $attributes = $record->getRawOriginal();

        ksort($attributes);

        return hash(
            'sha256',
            json_encode($attributes, JSON_THROW_ON_ERROR)
        );
    }

    private function normalizeName(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = preg_replace('/\s+/u', ' ', $name);

        return str_replace('- ', '-', $name);
    }

    private function ensureUniqueName(
        string $name,
        ?int $exceptId = null
    ): void {
        $normalized = $this->normalizeName($name);

        $query = MasterKpi::query();

        if ($exceptId !== null) {
            $query->where('id_kpi', '<>', $exceptId);
        }

        foreach ($query->get(['id_kpi', 'nama_kpi']) as $item) {
            if (
                $this->normalizeName((string) $item->nama_kpi)
                === $normalized
            ) {
                throw ValidationException::withMessages([
                    'nama_kpi' =>
                        'Nama KPI sudah digunakan. Gunakan nama unik '
                        . 'agar pencocokan saat impor tidak ambigu.',
                ]);
            }
        }
    }

    private function lockUnits(): void
    {
        // Mengikuti urutan penguncian pada alur impor/edit website.
        Ulp::query()
            ->orderBy('id_ulp')
            ->lockForUpdate()
            ->get(['id_ulp']);
    }

    private function definition(Request $request): array
    {
        foreach (['nama_kpi', 'satuan'] as $field) {
            if (is_string($request->input($field))) {
                $request->merge([
                    $field => trim($request->input($field)),
                ]);
            }
        }

        return $request->validate([
            'nama_kpi' => ['required', 'string', 'max:255'],
            'satuan' => ['nullable', 'string', 'max:50'],
            'bobot' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
                'decimal:0,2',
            ],
            'polaritas' => [
                'nullable',
                Rule::in(['positif', 'negatif']),
            ],
        ], [
            'nama_kpi.required' => 'Nama KPI wajib diisi.',
            'nama_kpi.max' => 'Nama KPI maksimal 255 karakter.',
            'satuan.max' => 'Satuan maksimal 50 karakter.',
            'bobot.required' => 'Bobot wajib diisi.',
            'bobot.numeric' => 'Bobot harus berupa angka.',
            'bobot.min' => 'Bobot tidak boleh negatif.',
            'bobot.max' => 'Bobot maksimal 100%.',
            'bobot.decimal' => 'Bobot maksimal dua angka desimal.',
            'polaritas.in' => 'Pilihan polaritas tidak valid.',
        ]);
    }

    private function formData(MasterKpi $record): array
    {
        return [
            'record' => $record,
            'used' => $this->isUsed($record),
            'version' => $record->exists
                ? $this->version($record)
                : null,
            'parents' => MasterKpi::query()
                ->orderBy('kode_kpi')
                ->get(['id_kpi', 'kode_kpi', 'nama_kpi']),
        ];
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        return response()->json([
            'success' => true,
            'data' => MasterKpi::query()
                ->orderBy('kode_kpi')
                ->get(),
        ]);
    }

    public function createForm(Request $request)
    {
        $this->authorizeAdmin($request);

        $record = new MasterKpi([
            'bobot' => '0.00',
            'status' => 'nonaktif',
            'polaritas' => null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->formData($record),
        ]);
    }

    public function editForm(Request $request, int $kpi)
    {
        $this->authorizeAdmin($request);

        return response()->json([
            'success' => true,
            'data' => $this->formData(
                MasterKpi::findOrFail($kpi)
            ),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAdmin($request);

        if (is_string($request->input('kode_kpi'))) {
            $request->merge([
                'kode_kpi' => strtoupper(
                    trim($request->input('kode_kpi'))
                ),
            ]);
        }

        $data = array_merge(
            $this->definition($request),
            $request->validate([
                'kode_kpi' => [
                    'required',
                    'string',
                    'max:30',
                    'regex:/^[A-Z0-9_-]+$/',
                    Rule::unique('master_kpi', 'kode_kpi'),
                ],
                'id_parent' => [
                    'nullable',
                    'integer',
                    Rule::exists('master_kpi', 'id_kpi'),
                ],
                'status' => [
                    'required',
                    Rule::in(['aktif', 'nonaktif']),
                ],
            ], [
                'kode_kpi.required' => 'Kode KPI wajib diisi.',
                'kode_kpi.unique' => 'Kode KPI sudah digunakan.',
                'kode_kpi.regex' =>
                    'Kode hanya boleh berisi huruf, angka, - atau _.',
                'id_parent.exists' => 'KPI induk tidak tersedia.',
                'status.in' => 'Status tidak valid.',
            ])
        );

        $record = DB::transaction(function () use ($data) {
            $this->lockUnits();

            $this->ensureUniqueName($data['nama_kpi']);

            if (
                MasterKpi::where('kode_kpi', $data['kode_kpi'])
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'kode_kpi' => 'Kode KPI sudah digunakan.',
                ]);
            }

            if (!empty($data['id_parent'])) {
                $parent = MasterKpi::query()
                    ->whereKey($data['id_parent'])
                    ->lockForUpdate()
                    ->first();

                abort_unless(
                    $parent,
                    409,
                    'KPI induk sudah tidak tersedia. Buka ulang formulir.'
                );
            }

            return MasterKpi::create([
                'kode_kpi' => $data['kode_kpi'],
                'nama_kpi' => $data['nama_kpi'],
                'id_parent' => $data['id_parent'] ?? null,
                'satuan' => $data['satuan'] ?? null,
                'bobot' => $data['bobot'],
                'polaritas' => $data['polaritas'] ?? null,
                'status' => $data['status'],
            ]);
        }, 3);

        return response()->json([
            'success' => true,
            'message' => 'Master KPI berhasil ditambahkan.',
            'data' => ['id_kpi' => $record->id_kpi],
        ], 201);
    }

    public function update(Request $request, int $kpi)
    {
        $this->authorizeAdmin($request);

        $input = $request->validate([
            'version' => ['required', 'string', 'size:64'],
            'status' => [
                'required',
                Rule::in(['aktif', 'nonaktif']),
            ],
        ]);

        DB::transaction(function () use ($request, $kpi, $input) {
            $this->lockUnits();

            $record = MasterKpi::query()
                ->whereKey($kpi)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                hash_equals(
                    $this->version($record),
                    $input['version']
                ),
                409,
                'Data sudah berubah. Tutup lalu buka kembali formulir.'
            );

            $used = $this->isUsed($record);

            if ($used) {
                // Formulir untuk KPI yang sudah digunakan
                // hanya boleh mengirim versi dan status.
                abort_if(
                    $request->hasAny([
                        'nama_kpi',
                        'satuan',
                        'bobot',
                        'polaritas',
                        'kode_kpi',
                        'id_parent',
                    ]),
                    409,
                    'KPI sudah memiliki target atau realisasi. '
                    . 'Buka ulang formulir; hanya status yang dapat diubah.'
                );

                if (
                    $input['status'] === 'aktif'
                    && $record->status !== 'aktif'
                ) {
                    $this->ensureUniqueName(
                        (string) $record->nama_kpi,
                        (int) $record->id_kpi
                    );
                }
            } else {
                $definition = $this->definition($request);

                $this->ensureUniqueName(
                    $definition['nama_kpi'],
                    (int) $record->id_kpi
                );

                // Kode dan induk tidak dapat diubah melalui edit.
                $record->fill([
                    'nama_kpi' => $definition['nama_kpi'],
                    'satuan' => $definition['satuan'] ?? null,
                    'bobot' => $definition['bobot'],
                    'polaritas' => $definition['polaritas'] ?? null,
                ]);
            }

            $record->status = $input['status'];
            $record->save();
        }, 3);

        return response()->json([
            'success' => true,
            'message' => 'Master KPI berhasil diperbarui.',
        ]);
    }
}