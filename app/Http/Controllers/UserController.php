<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
    $users = User::with('role')->latest()->paginate(10);
        return view('users.index', compact('users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
    $roles = Role::orderBy('nama_role')->get();
        return view('users.create', compact('roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama'      => 'required|string|max:255',
            'username'  => 'required|string|max:255|unique:users,username', // Ganti username jadi username
            'password'  => 'required|min:8',
            'role_id'   => 'required|exists:roles,id',
            'gudang_id' => 'nullable|exists:master_gudang,id' // Tambahkan ini
        ]);

        User::create([
            'nama'      => $request->nama,
            'username'  => $request->username,
            'password'  => Hash::make($request->password),
            'role_id'   => $request->role_id,
            'gudang_id' => $request->gudang_id, // Tambahkan ini
        ]);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
    $roles = Role::orderBy('nama_role')->get();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
    $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|username|max:255|unique:users,username,' . $user->id,
            'password' => 'nullable|min:8',
            'role_id' => 'required|exists:roles,id',
            'gudang_id' => 'nullable|exists:master_gudang,id'
        ]);

        $data = [
            'name' => $request->name,
            'username' => $request->username,
            'role_id' => $request->role_id,
            'gudang_id' => $request->gudang_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', 'User berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
    $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User berhasil dihapus.');
    }
}
