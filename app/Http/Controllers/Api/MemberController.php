<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 5);
        $search  = $request->get('search');

        $members = Member::query()
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $members->items(),
            'meta' => [
                'current_page' => $members->currentPage(),
                'last_page' => $members->lastPage(),
                'per_page' => $members->perPage(),
                'total' => $members->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',

            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $member = Member::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'position' => $validated['position'] ?? null,
            'department' => $validated['department'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Anggota berhasil ditambahkan',
            'data' => $member->load('user'),
        ], 201);
    }

    public function show(string $id)
    {
        $member = Member::with('user')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $member,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $member = Member::findOrFail($id);
        $user = $member->user;

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',

            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],

            'password' => 'nullable|min:6',
            'position' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }

        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $member->update([
            'name' => $validated['name'] ?? $member->name,
            'position' => $validated['position'] ?? $member->position,
            'department' => $validated['department'] ?? $member->department,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Anggota berhasil diupdate',
            'data' => $member->load('user'),
        ]);
    }

    public function destroy(string $id)
    {
        $member = Member::findOrFail($id);
        $member->user()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Anggota berhasil dihapus',
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
                $user = User::create([
                    'name' => $row[0],
                    'email' => $row[1],
                    'password' => Hash::make($row[2] ?? 'password123'),
                ]);

                Member::create([
                    'user_id' => $user->id,
                    'name' => $row[0],
                    'position' => $row[3] ?? null,
                    'department' => $row[4] ?? null,
                ]);

                $success++;
            } catch (\Exception $e) {
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Import selesai',
            'inserted' => $success,
            'failed' => $failed,
        ]);
    }
}
