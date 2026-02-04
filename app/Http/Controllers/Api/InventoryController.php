<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Member;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 5);
        $search  = $request->get('search');

        $inventories = Inventory::with('member')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $inventories->items(),
            'meta' => [
                'current_page' => $inventories->currentPage(),
                'last_page'    => $inventories->lastPage(),
                'per_page'     => $inventories->perPage(),
                'total'        => $inventories->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'specification' => 'nullable|string',

            'status' => [
                'required',
                Rule::in(['baik', 'rusak', 'dilelang', 'tidak_dipakai']),
            ],

            'member_id' => 'nullable|exists:members,id',
            'department' => 'nullable|string|max:255',
        ]);

        $inventory = Inventory::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inventory berhasil ditambahkan',
            'data' => $inventory->load('member'),
        ], 201);
    }

    public function show(string $id)
    {
        $inventory = Inventory::with('member')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $inventory,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $inventory = Inventory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:255',
            'specification' => 'nullable|string',

            'status' => [
                'sometimes',
                Rule::in(['baik', 'rusak', 'dilelang', 'tidak_dipakai']),
            ],

            'member_id' => 'nullable|exists:members,id',
            'department' => 'nullable|string|max:255',
        ]);

        $inventory->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Inventory berhasil diupdate',
            'data' => $inventory->load('member'),
        ]);
    }

    public function destroy(string $id)
    {
        Inventory::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inventory berhasil dihapus',
        ]);
    }

    public function analytics()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'baik' => Inventory::where('status', 'baik')->count(),
                'rusak' => Inventory::where('status', 'rusak')->count(),
                'dilelang' => Inventory::where('status', 'dilelang')->count(),
                'tidak_dipakai' => Inventory::where('status', 'tidak_dipakai')->count(),
            ]
        ]);
    }
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx',
        ]);

        $rows = Excel::toArray([], $request->file('file'))[0];

        unset($rows[0]);

        $success = 0;
        $failed = 0;

        foreach ($rows as $row) {
            try {
                $member = null;

                if (!empty($row[5])) {
                    $member = Member::whereHas('user', function ($q) use ($row) {
                        $q->where('email', $row[5]);
                    })->first();
                }

                Inventory::create([
                    'name' => $row[0],
                    'type' => $row[1] ?? null,
                    'serial_number' => $row[2] ?? null,
                    'specification' => $row[3] ?? null,
                    'status' => $row[4] ?? 'baik',
                    'member_id' => $member?->id,
                    'department' => $member?->department,
                ]);

                $success++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Import inventory selesai',
            'inserted' => $success,
            'failed' => $failed,
        ]);
    }
}
