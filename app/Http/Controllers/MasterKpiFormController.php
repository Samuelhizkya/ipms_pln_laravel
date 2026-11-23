<?php

namespace App\Http\Controllers;

use App\Models\MasterKpi;
use App\Models\RealisasiKpi;
use App\Models\TargetKpi;
use App\Models\Ulp;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class MasterKpiFormController extends Controller
{
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

    private function form(MasterKpi $record)
    {
        $parents = MasterKpi::query()
            ->orderBy('kode_kpi')
            ->get(['id_kpi', 'kode_kpi', 'nama_kpi']);

        $used = $this->isUsed($record);

        $version = $record->exists
            ? $this->version($record)
            : '';

        return view('kpi.master-form', compact(
            'record',
            'parents',
            'used',
            'version'
        ));
    }

    public function create()
    {
        return $this->form(new MasterKpi([
            'bobot' => '0.00',
            'status' => 'nonaktif',
        ]));
    }

    public function edit(int $kpi)
    {
        return $this->form(MasterKpi::findOrFail($kpi));
    }

    private function validated(
        Request $request,
        ?MasterKpi $record = null
    ): array {
        // Normalisasi hanya nilai string, bukan array yang tidak valid.
        foreach (['kode_kpi', 'nama_kpi', 'satuan'] as $field) {
            if (is_string($request->input($field))) {
                $request->merge([
                    $field => trim($request->input($field)),
                ]);
            }
        }

        if (!$record && is_string($request->input('kode_kpi'))) {
            $request->merge([
                'kode_kpi' => strtoupper($request->input('kode_kpi')),
            ]);
        }

        $rules = [
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
            'status' => [
                'required',
                Rule::in(['aktif', 'nonaktif']),
            ],
        ];

        if ($record) {
            $rules['version'] = ['required', 'string', 'size:64'];
        } else {
            $rules['kode_kpi'] = [
                'required',
                'string',
                'max:30',
                'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('master_kpi', 'kode_kpi'),
            ];

            $rules['id_parent'] = [
                'nullable',
                'integer',
                Rule::exists('master_kpi', 'id_kpi'),
            ];
        }

        return $request->validate($rules, [
            'kode_kpi.required' => 'Kode KPI wajib diisi.',
            'kode_kpi.unique' => 'Kode KPI sudah digunakan.',
            'kode_kpi.regex' =>
                'Kode hanya boleh berisi huruf, angka, tanda - atau _.',
            'nama_kpi.required' => 'Nama KPI wajib diisi.',
            'bobot.required' => 'Bobot wajib diisi.',
            'bobot.numeric' => 'Bobot harus berupa angka.',
            'bobot.min' => 'Bobot tidak boleh negatif.',
            'bobot.max' => 'Bobot maksimal 100%.',
            'bobot.decimal' => 'Bobot maksimal dua angka desimal.',
            'polaritas.in' => 'Pilihan polaritas tidak valid.',
            'status.in' => 'Pilihan status tidak valid.',
            'id_parent.exists' => 'KPI induk tidak tersedia.',
            'version.required' => 'Buka kembali formulir edit.',
            'version.size' => 'Versi formulir tidak valid.',
        ]);
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
                        . 'agar pencocokan Excel tidak ambigu.',
                ]);
            }
        }
    }

    private function lockUnits(): void
    {
        /*
         * Impor dan edit data sebelumnya juga mengunci ULP.
         * Urutan ID dipertahankan untuk mengurangi konflik penguncian.
         */
        Ulp::query()
            ->orderBy('id_ulp')
            ->lockForUpdate()
            ->get(['id_ulp']);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        try {
            DB::transaction(function () use ($data) {
                $this->lockUnits();

                $this->ensureUniqueName($data['nama_kpi']);

                if (!empty($data['id_parent'])) {
                    $parent = MasterKpi::query()
                        ->whereKey($data['id_parent'])
                        ->lockForUpdate()
                        ->first();

                    if (!$parent) {
                        throw ValidationException::withMessages([
                            'id_parent' => 'KPI induk sudah tidak tersedia.',
                        ]);
                    }
                }

                MasterKpi::create([
                    'kode_kpi' => $data['kode_kpi'],
                    'nama_kpi' => $data['nama_kpi'],
                    'id_parent' => $data['id_parent'] ?? null,
                    'satuan' => $data['satuan'] ?? null,
                    'bobot' => $data['bobot'],
                    'polaritas' => $data['polaritas'] ?? null,
                    'status' => $data['status'],
                ]);
            }, 3);
        } catch (ValidationException $e) {
            throw $e;
        } catch (QueryException $e) {
            Log::error('Tambah Master KPI gagal.', ['exception' => $e]);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Master KPI gagal disimpan. Kode mungkin sudah '
                    . 'digunakan atau terdapat kendala database.'
                );
        } catch (Throwable $e) {
            Log::error('Tambah Master KPI gagal.', ['exception' => $e]);

            return back()
                ->withInput()
                ->with('error', 'Master KPI gagal disimpan.');
        }

        return redirect()
            ->route('master-kpi.index')
            ->with('success', 'Master KPI berhasil ditambahkan.');
    }

    public function update(Request $request, int $kpi)
    {
        $record = MasterKpi::findOrFail($kpi);
        $data = $this->validated($request, $record);

        try {
            DB::transaction(function () use ($kpi, $data) {
                $this->lockUnits();

                $record = MasterKpi::query()
                    ->whereKey($kpi)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!hash_equals(
                    $this->version($record),
                    $data['version']
                )) {
                    throw ValidationException::withMessages([
                        'version' =>
                            'Data telah berubah. Muat ulang formulir '
                            . 'sebelum menyimpan kembali.',
                    ]);
                }

                $definitionChanged =
                    (string) $record->nama_kpi !== $data['nama_kpi']
                    || (string) $record->satuan
                        !== (string) ($data['satuan'] ?? '')
                    || (string) $record->polaritas
                        !== (string) ($data['polaritas'] ?? '')
                    || number_format((float) $record->bobot, 2, '.', '')
                        !== number_format((float) $data['bobot'], 2, '.', '');

                if ($definitionChanged && $this->isUsed($record)) {
                    throw ValidationException::withMessages([
                        'nama_kpi' =>
                            'KPI sudah memiliki target atau realisasi. '
                            . 'Definisi KPI tidak dapat diubah melalui '
                            . 'form ini; status tetap dapat diperbarui.',
                    ]);
                }

                if (
                    (string) $record->nama_kpi !== $data['nama_kpi']
                    || (
                        $data['status'] === 'aktif'
                        && $record->status !== 'aktif'
                    )
                ) {
                    $this->ensureUniqueName(
                        $data['nama_kpi'],
                        (int) $record->id_kpi
                    );
                }

                // Kode dan id_parent tidak diambil dari request edit.
                $record->fill([
                    'nama_kpi' => $data['nama_kpi'],
                    'satuan' => $data['satuan'] ?? null,
                    'bobot' => $data['bobot'],
                    'polaritas' => $data['polaritas'] ?? null,
                    'status' => $data['status'],
                ]);

                $record->save();
            }, 3);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Edit Master KPI gagal.', [
                'id_kpi' => $kpi,
                'exception' => $e,
            ]);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Perubahan Master KPI gagal disimpan. '
                    . 'Data sebelumnya tetap dipertahankan.'
                );
        }

        return redirect()
            ->route('master-kpi.index')
            ->with('success', 'Master KPI berhasil diperbarui.');
    }
}