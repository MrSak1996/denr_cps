<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;


class ImpersonationController extends Controller
{
    public function start(Request $request)
    {
        $admin = auth()->user();


        // only RTS (role 8)
        if ($admin->role_id !== 8) {
            abort(403);
        }

        $targetUser = User::findOrFail($request->id);
        

        // prevent impersonating self
        if ($targetUser->id === $admin->id) {
            abort(403, 'Cannot impersonate yourself.');
        }

        // prevent impersonating RTS/admin
        if ($targetUser->role_id === 8) {
            abort(403, 'Cannot impersonate another admin.');
        }

        // save original admin
        session([
            'impersonator_id' => $admin->id
        ]);

        // login as user
        Auth::login($targetUser);

        // IMPORTANT:
        // dynamic redirect based on role

        return response()->json([
            'success' => true,

            'redirect' => $this->getDashboardRoute(
                $targetUser->role_id
            )
        ]);
    }

    public function leave()
    {
        if (!session()->has('impersonator_id')) {
            abort(403);
        }

        $adminId = session('impersonator_id');

        Auth::loginUsingId($adminId);

        session()->forget('impersonator_id');

        return redirect('/user-management/index');
    }

    private function getDashboardRoute($roleId)
    {
        return match ($roleId) {

            1 => '/applications/pending_application',

            2 => '/dashboard/rps-chief',

            3 => '/dashboard/cenro',

            4 => '/dashboard/penro-technical',

            5 => '/dashboard/penro-rps-chief',

            6 => '/dashboard/penro-tsd-chief',

            7 => '/dashboard/penro',

            8 => '/dashboard/rts',

            9 => '/dashboard/fus',

            10 => '/dashboard/lpdd-chief',

            11 => '/dashboard/ardts',

            12 => '/dashboard/regional-executive',

            default => '/dashboard',
        };
    }
}