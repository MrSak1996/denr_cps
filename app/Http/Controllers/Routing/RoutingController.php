<?php

namespace App\Http\Controllers\Routing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RoutingController extends Controller
{
    public function show($applicationId)
    {
        $routes = DB::table('tbl_application_routing as ar')
    ->select(
        'ac.id',
        'ac.application_no',
        'ac.application_status',

        'ar.route_order',
        'ar.comments',
        'r.role_title as sender_role',
        'u.name as sender',
        'rr.role_title as receiver_role',
        'uu.name as receiver',
        'ar.action',
        'ar.remarks',
        'ar.created_at',
        'ar.updated_at',

        /* ===== WORKFLOW ORDER (NO NEW FIELDS) ===== */

        /* 1. CENRO TECH STAFF */
        'ac.date_endorsed_chiefrps',
        'ac.date_endorsed_cenro_chief',
        'ac.date_received_rps_chief',

        /* 2. CENRO CHIEF */
        'ac.date_cenro_chief_received',
        'ac.date_endorsed_penro_technical',

        /* 3. PENRO TECHNICAL */
        'ac.date_received_penro_technical',
        'ac.date_endorsed_penro_chief_rps',

        /* 4. PENRO CHIEF RPS */
        'ac.date_received_penro_rps_chief',
        'ac.date_endorsed_penro_chief_tsd',

        /* 5. PENRO CHIEF TSD */
        'ac.date_received_penro_tsd_chief',
        'ac.date_endorsed_penro',

        /* 6. PENRO */
        'ac.date_endorsed_penro',
        'ac.date_received_penro_chief',

        /* 7. REGIONAL TECHNICAL STAFF */
        'ac.date_endorsed_region_technical',
        'ac.date_received_region_technical',

        /* 8. FUS CHIEF */
        'ac.date_endorsed_fus_chief',
        'ac.date_received_fus_chief',

          /* 9. LPDD CHIEF */
        'ac.date_endorsed_lpddchief',
        'ac.date_received_lpddchief',

          /* 10. ARDTS */
        'ac.date_endorsed_ardts',
        'ac.date_received_ardts',

          /* 11. RED */
          'ac.date_endorse_red',
          'ac.date_received_red',

        /* RETURN */
        'ac.date_returned'
    )
    ->leftJoin('users as u', 'u.id', '=', 'ar.sender_id')
    ->leftJoin('tbl_roles as r', 'r.id', '=', 'u.role_id')
    ->leftJoin('users as uu', 'uu.id', '=', 'ar.receiver_id')
    ->leftJoin('tbl_roles as rr', 'rr.id', '=', 'uu.role_id')
    ->leftJoin('tbl_application_checklist as ac', 'ac.id', '=', 'ar.application_id')
    ->where('ar.application_id', $applicationId)
    ->orderBy('ar.route_order', 'desc') // 🔴 correct flow order
    ->get();

        return response()->json($routes);
    }

    public function getCommentsByID($applicationId)
    {
        $data = DB::table('tbl_application_routing as ar')
            ->select([
                'ar.id',
                'ac.application_no',
                'u.name as action_officer',
                'r.role_title as sender_role',
                'ar.comments',
                's.status_title',
                'ar.created_at',
            ])
            ->leftJoin('users as u', 'u.id', '=', 'ar.sender_id')
            ->leftJoin('tbl_application_checklist as ac', 'ac.id', '=', 'ar.application_id')
            ->leftJoin('tbl_status as s', 's.id', '=', 'ac.application_status')
            ->leftJoin('tbl_roles as r', 'r.id', '=', 'u.role_id')

            ->where('ar.application_id', $applicationId)
            ->where('ar.route_order', 0)
            ->orderBy('ar.id', 'DESC')
            ->get();

        return response()->json($data);
    }
}
