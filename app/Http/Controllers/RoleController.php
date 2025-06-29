<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $this->authorize('viewAny', Role::class);

        return Role::all();
    }

    public function store(Request $request)
    {
        $this->authorize('create', Role::class);

        $data = $request->validate([
            'name' => ['required'],
            'code' => ['required'],
            'permission' => ['required'],
        ]);

        return Role::create($data);
    }

    public function show(Role $role)
    {
        $this->authorize('view', $role);

        return $role;
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize('update', $role);

        $data = $request->validate([
            'name' => ['required'],
            'code' => ['required'],
            'permission' => ['required'],
        ]);

        $role->update($data);

        return $role;
    }

    public function destroy(Role $role)
    {
        $this->authorize('delete', $role);

        $role->delete();

        return response()->json();
    }
}
