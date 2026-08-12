<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $column = $request->input('column');

        $query = User::query();

        if (!empty($search) && !empty($column)) {
            $query->where($column, 'like', "%{$search}%");
        }

        $users = $query->orderBy('name')->paginate(15)->through(fn($u) => [
            'id'         => $u->id,
            'name'       => $u->name,
            'email'      => $u->email,
            'role'       => $u->role,
            'role_label' => $u->role === 'pos' ? 'POS User' : 'Admin',
            'created_at' => $u->created_at?->format('m-d-Y'),
        ]);

        $columns = [
            ['accessorKey' => 'id',         'header' => 'ID',         'isVisible' => false, 'isParameter' => false],
            ['accessorKey' => 'name',        'header' => 'NAME',       'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'email',       'header' => 'EMAIL',      'isVisible' => true,  'isParameter' => true],
            ['accessorKey' => 'role_label',  'header' => 'ROLE',       'isVisible' => true,  'isParameter' => false],
            ['accessorKey' => 'created_at',  'header' => 'CREATED AT', 'isVisible' => true,  'isParameter' => false],
        ];

        return inertia('Users/UserIndex', [
            'users'   => $users,
            'columns' => $columns,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', Password::min(8)],
            'role'     => 'required|in:admin,pos',
        ]);

        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully!');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'password' => ['nullable', Password::min(8)],
            'role'     => 'required|in:admin,pos',
        ]);

        $data = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'role'  => $validated['role'],
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully!');
    }
}
