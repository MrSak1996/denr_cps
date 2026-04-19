<?php

namespace App\Http\Controllers\UserManagement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class UserController extends Controller
{
    public function getUserList(Request $request)
    {
        $users = DB::table('users as u')
            ->leftJoin('tbl_office as o', 'o.id', '=', 'u.office_id')
            ->leftJoin('tbl_roles as r', 'r.id', '=', 'u.role_id')
            ->select(
                'u.id',
                'u.name',
                'u.email',
                'u.office_id',
                'u.role_id',

                // Optional readable fields for frontend
                'o.office_title as office_name',
                'r.role_title as role_name',

                'u.created_at'
            )
            ->orderBy('u.role_id', 'asc')
            ->get();

        return response()->json([
            'data' => $users
        ]);
    }

    public function edit_user($id)
    {
        $userData = DB::table('users as u')
            ->leftJoin('tbl_office as o', 'o.id', '=', 'u.office_id')
            ->leftJoin('tbl_roles as r', 'r.id', '=', 'u.role_id')
            ->select(
                'u.id',
                'u.name',
                'u.email',
                'u.office_id',
                'u.role_id',
                'o.office_title as office_title',
                'r.role_title as role_title',
                'u.created_at'
            )
            ->where('u.id', $id) // ✅ filter by ID
            ->first(); // ✅ get single record

        // ✅ fetch roles
        $roles = DB::table('tbl_roles')
            ->select('id', 'role_title')
            ->orderBy('role_title', 'asc')
            ->get();
        $office = DB::table('tbl_office')
            ->select('id', 'office_title')
            ->orderBy('office_title', 'asc')
            ->get();

        return Inertia::render('user-management/form/edit', [
            'data' => $userData,
            'roles' => $roles,
            'office' => $office
        ]);
    }
    public function update(Request $request, $id)
    {
        DB::table('users')
            ->where('id', $id)
            ->update([
                'name' => $request->name,
                'email' => $request->email,
                'office_id' => $request->office_id,
                'role_id' => $request->role_id,
            ]);

        return redirect()->route('user-management.index')
            ->with('success', 'User updated successfully');
    }
}
