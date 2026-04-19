<?php

namespace App\Http\Controllers\Assessment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Application\ChainsawIndividualApplication;

use Illuminate\Support\Facades\DB;

class AssessmentController extends Controller
{
    // -------------------------------
    // STATUS CONSTANTS
    // -------------------------------
    const STATUS_DRAFT = 1;
    const STATUS_FOR_REVIEW_EVALUATION = 2;

    const STATUS_ENDORSED_CENRO_RPS_CHIEF = 3;
    const STATUS_ENDORSED_CENRO_OFFICER = 4;
    const STATUS_ENDORSED_PENRO_TECHNICAL = 5;
    const STATUS_ENDORSED_PENRO_CHIEF_RPS = 6;
    const STATUS_ENDORSED_PENRO_CHIEF_TSD = 7;
    const STATUS_ENDORSED_PENRO_OFFICER = 8;
    const STATUS_ENDORSED_REGIONAL_TECHNICAL_STAFF = 9;
    const STATUS_ENDORSED_FUS_CHIEF = 10;
    const STATUS_ENDORSED_LPDD_CHIEF = 11;
    const STATUS_ENDORSED_ARDTS = 12;
    const STATUS_ENDORSED_RED = 13;

    const STATUS_RECEIVED_CENRO_RPS_CHIEF = 14;
    const STATUS_RECEIVED_CENRO_OFFICER = 15;
    const STATUS_RECEIVED_PENRO_TECHNICAL = 16;
    const STATUS_RECEIVED_PENRO_CHIEF_RPS = 17;
    const STATUS_RECEIVED_PENRO_CHIEF_TSD = 18;
    const STATUS_RECEIVED_PENRO_OFFICER = 19;
    const STATUS_RECEIVED_REGIONAL_TECHNICAL_STAFF = 20;
    const STATUS_RECEIVED_FUS_CHIEF = 21;
    const STATUS_RECEIVED_LPDD_CHIEF = 22;
    const STATUS_RECEIVED_ARDTS = 23;
    const STATUS_RECEIVED_RED = 24;

    const STATUS_RETURNED_TO_CENRO_TECHNICAL = 25;
    const STATUS_RETURNED_TO_PENRO_TECHNICAL = 26;
    const STATUS_RETURNED_TO_REGIONAL_TECHNICAL = 27;

    const STATUS_APPROVED_BY_RED = 28;

    // -------------------------------
    // ROLE CONSTANTS
    // -------------------------------
    const TECHNICAL_STAFF = 1;
    const CHIEF_RPS = 2;
    const CENRO_OFFICER = 3;
    const PENRO_TECHNICAL_STAFF = 4;
    const PENRO_CHIEF_RPS = 5;
    const PENRO_CHIEF_TSD = 6;
    const PENRO_OFFICER = 7;
    const REGIONAL_TECHNICAL_STAFF = 8;
    const FUS_CHIEF = 9;
    const LPDD_CHIEF = 10;
    const ARDTS = 11;
    const RED = 12;

    // private function workflowConfig()
    // {
    //     return [

    //         // 🔁 Role order
    //         'sequence' => [
    //             self::TECHNICAL_STAFF,
    //             self::CHIEF_RPS,
    //             self::CENRO_OFFICER,
    //             self::PENRO_TECHNICAL_STAFF,
    //             self::PENRO_CHIEF_RPS,
    //             self::PENRO_CHIEF_TSD,
    //             self::PENRO_OFFICER,
    //             self::REGIONAL_TECHNICAL_STAFF,
    //             self::FUS_CHIEF,
    //             self::LPDD_CHIEF,
    //             self::ARDTS,
    //             self::RED,
    //         ],

    //         // 📤 Status when CURRENT role endorses
    //         'endorse_status' => [
    //             self::TECHNICAL_STAFF       => self::STATUS_ENDORSED_CENRO_RPS_CHIEF,
    //             self::CHIEF_RPS             => self::STATUS_ENDORSED_CENRO_OFFICER,
    //             self::CENRO_OFFICER         => self::STATUS_ENDORSED_PENRO_TECHNICAL,
    //             self::PENRO_TECHNICAL_STAFF => self::STATUS_ENDORSED_PENRO_CHIEF_RPS,
    //             self::PENRO_CHIEF_RPS       => self::STATUS_ENDORSED_PENRO_CHIEF_TSD,
    //             self::PENRO_CHIEF_TSD       => self::STATUS_ENDORSED_PENRO_OFFICER,
    //             self::PENRO_OFFICER         => self::STATUS_ENDORSED_REGIONAL_TECHNICAL_STAFF,
    //             self::REGIONAL_TECHNICAL_STAFF => self::STATUS_ENDORSED_FUS_CHIEF,
    //             self::FUS_CHIEF             => self::STATUS_ENDORSED_LPDD_CHIEF,
    //             self::LPDD_CHIEF            => self::STATUS_ENDORSED_ARDTS,
    //             self::ARDTS                 => self::STATUS_ENDORSED_RED,
    //             self::RED                   => self::STATUS_APPROVED_BY_RED,
    //         ],

    //         // 📥 Status when RECEIVER clicks receive
    //         'receive_status' => [
    //             self::CHIEF_RPS                => self::STATUS_RECEIVED_CENRO_RPS_CHIEF,
    //             self::CENRO_OFFICER            => self::STATUS_RECEIVED_CENRO_OFFICER,
    //             self::PENRO_TECHNICAL_STAFF    => self::STATUS_RECEIVED_PENRO_TECHNICAL,
    //             self::PENRO_CHIEF_RPS          => self::STATUS_RECEIVED_PENRO_CHIEF_RPS,
    //             self::PENRO_CHIEF_TSD          => self::STATUS_RECEIVED_PENRO_CHIEF_TSD,
    //             self::PENRO_OFFICER            => self::STATUS_RECEIVED_PENRO_OFFICER,
    //             self::REGIONAL_TECHNICAL_STAFF => self::STATUS_RECEIVED_REGIONAL_TECHNICAL_STAFF,
    //             self::FUS_CHIEF                => self::STATUS_RECEIVED_FUS_CHIEF,
    //             self::LPDD_CHIEF               => self::STATUS_RECEIVED_LPDD_CHIEF,
    //             self::ARDTS                    => self::STATUS_RECEIVED_ARDTS,
    //             self::RED                      => self::STATUS_RECEIVED_RED,
    //         ],

    //         // 🗓 Endorsed date fields (by CURRENT role)
    //         'endorse_dates' => [
    //             self::TECHNICAL_STAFF          => 'date_endorsed_chiefrps',
    //             self::CHIEF_RPS                => 'date_endorsed_cenro_chief',
    //             self::CENRO_OFFICER            => 'date_endorsed_penro_technical',
    //             self::PENRO_TECHNICAL_STAFF    => 'date_endorsed_penro_chief_rps',
    //             self::PENRO_CHIEF_RPS          => 'date_endorsed_penro_chief_tsd',
    //             self::PENRO_CHIEF_TSD          => 'date_endorsed_penro',
    //             self::PENRO_OFFICER            => 'date_endorsed_region_technical',
    //             self::REGIONAL_TECHNICAL_STAFF => 'date_endorsed_fus_chief',
    //             self::FUS_CHIEF                => 'date_endorsed_lpddchief',
    //             self::LPDD_CHIEF               => 'date_endorsed_ardts',
    //             self::ARDTS                    => 'date_endorse_red'
    //         ],

    //         // 🗓 Received date fields (by RECEIVER role)
    //         'receive_dates' => [
    //             self::CHIEF_RPS                => 'date_received_rps_chief',
    //             self::CENRO_OFFICER            => 'date_cenro_chief_received',
    //             self::PENRO_TECHNICAL_STAFF    => 'date_received_penro_technical',
    //             self::PENRO_CHIEF_RPS          => 'date_received_penro_rps_chief',
    //             self::PENRO_CHIEF_TSD          => 'date_received_penro_tsd_chief',
    //             self::PENRO_OFFICER            => 'date_received_penro_chief',
    //             self::REGIONAL_TECHNICAL_STAFF => 'date_received_region_technical',
    //             self::FUS_CHIEF                => 'date_received_fus_chief',
    //             self::LPDD_CHIEF               => 'date_received_lpddchief',
    //             self::ARDTS                    => 'date_received_ardts',
    //             self::RED                      => 'date_received_red'
    //         ],

    //         // 🗓 Return date 


    //         // 🗓 Returned status mapping
    //         'return_status' => [
    //             self::TECHNICAL_STAFF => self::STATUS_RETURNED_TO_CENRO_TECHNICAL,
    //             self::PENRO_TECHNICAL_STAFF => self::STATUS_RETURNED_TO_PENRO_TECHNICAL,
    //             self::REGIONAL_TECHNICAL_STAFF => self::STATUS_RETURNED_TO_REGIONAL_TECHNICAL,
    //         ],
    //     ];
    // }
    private function workflowConfig($type = 'smooth')
    {
        if ($type === 'implementing_agency') {

            // 🟢 SCENARIO 2: START FROM IMPLEMENTING AGENCY
            return [
                'sequence' => [
                    self::PENRO_TECHNICAL_STAFF,
                    self::PENRO_CHIEF_RPS,
                    self::PENRO_CHIEF_TSD,
                    self::PENRO_OFFICER,
                    self::REGIONAL_TECHNICAL_STAFF,
                    self::FUS_CHIEF,
                    self::LPDD_CHIEF,
                    self::ARDTS,
                    self::RED,
                ],

                'endorse_status' => [
                    self::PENRO_TECHNICAL_STAFF    => self::STATUS_ENDORSED_PENRO_CHIEF_RPS,
                    self::PENRO_CHIEF_RPS          => self::STATUS_ENDORSED_PENRO_CHIEF_TSD,
                    self::PENRO_CHIEF_TSD          => self::STATUS_ENDORSED_PENRO_OFFICER,
                    self::PENRO_OFFICER            => self::STATUS_ENDORSED_REGIONAL_TECHNICAL_STAFF,
                    self::REGIONAL_TECHNICAL_STAFF => self::STATUS_ENDORSED_FUS_CHIEF,
                    self::FUS_CHIEF                => self::STATUS_ENDORSED_LPDD_CHIEF,
                    self::LPDD_CHIEF               => self::STATUS_ENDORSED_ARDTS,
                    self::ARDTS                    => self::STATUS_ENDORSED_RED,
                    self::RED                      => self::STATUS_APPROVED_BY_RED,
                ],

                'receive_status' => [
                    self::PENRO_CHIEF_RPS          => self::STATUS_RECEIVED_PENRO_CHIEF_RPS,
                    self::PENRO_CHIEF_TSD          => self::STATUS_RECEIVED_PENRO_CHIEF_TSD,
                    self::PENRO_OFFICER            => self::STATUS_RECEIVED_PENRO_OFFICER,
                    self::REGIONAL_TECHNICAL_STAFF => self::STATUS_RECEIVED_REGIONAL_TECHNICAL_STAFF,
                    self::FUS_CHIEF                => self::STATUS_RECEIVED_FUS_CHIEF,
                    self::LPDD_CHIEF               => self::STATUS_RECEIVED_LPDD_CHIEF,
                    self::ARDTS                    => self::STATUS_RECEIVED_ARDTS,
                    self::RED                      => self::STATUS_RECEIVED_RED,
                ],
            ];
        }

        // 🔵 SCENARIO 1: SMOOTH TRANSACTION (DEFAULT)
        return [
            'sequence' => [
                self::TECHNICAL_STAFF,
                self::CHIEF_RPS,
                self::CENRO_OFFICER,
                self::PENRO_TECHNICAL_STAFF,
                self::PENRO_CHIEF_RPS,
                self::PENRO_CHIEF_TSD,
                self::PENRO_OFFICER,
                self::REGIONAL_TECHNICAL_STAFF,
                self::FUS_CHIEF,
                self::LPDD_CHIEF,
                self::ARDTS,
                self::RED,
            ],

            'endorse_status' => [
                self::TECHNICAL_STAFF          => self::STATUS_ENDORSED_CENRO_RPS_CHIEF,
                self::CHIEF_RPS                => self::STATUS_ENDORSED_CENRO_OFFICER,
                self::CENRO_OFFICER            => self::STATUS_ENDORSED_PENRO_TECHNICAL,
                self::PENRO_TECHNICAL_STAFF    => self::STATUS_ENDORSED_PENRO_CHIEF_RPS,
                self::PENRO_CHIEF_RPS          => self::STATUS_ENDORSED_PENRO_CHIEF_TSD,
                self::PENRO_CHIEF_TSD          => self::STATUS_ENDORSED_PENRO_OFFICER,
                self::PENRO_OFFICER            => self::STATUS_ENDORSED_REGIONAL_TECHNICAL_STAFF,
                self::REGIONAL_TECHNICAL_STAFF => self::STATUS_ENDORSED_FUS_CHIEF,
                self::FUS_CHIEF                => self::STATUS_ENDORSED_LPDD_CHIEF,
                self::LPDD_CHIEF               => self::STATUS_ENDORSED_ARDTS,
                self::ARDTS                    => self::STATUS_ENDORSED_RED,
                self::RED                      => self::STATUS_APPROVED_BY_RED,
            ],

            'receive_status' => [
                self::CHIEF_RPS                => self::STATUS_RECEIVED_CENRO_RPS_CHIEF,
                self::CENRO_OFFICER            => self::STATUS_RECEIVED_CENRO_OFFICER,
                self::PENRO_TECHNICAL_STAFF    => self::STATUS_RECEIVED_PENRO_TECHNICAL,
                self::PENRO_CHIEF_RPS          => self::STATUS_RECEIVED_PENRO_CHIEF_RPS,
                self::PENRO_CHIEF_TSD          => self::STATUS_RECEIVED_PENRO_CHIEF_TSD,
                self::PENRO_OFFICER            => self::STATUS_RECEIVED_PENRO_OFFICER,
                self::REGIONAL_TECHNICAL_STAFF => self::STATUS_RECEIVED_REGIONAL_TECHNICAL_STAFF,
                self::FUS_CHIEF                => self::STATUS_RECEIVED_FUS_CHIEF,
                self::LPDD_CHIEF               => self::STATUS_RECEIVED_LPDD_CHIEF,
                self::ARDTS                    => self::STATUS_RECEIVED_ARDTS,
                self::RED                      => self::STATUS_RECEIVED_RED,
            ],
        ];
    }

    // -------------------------------
    // SAVE ASSESSMENT & ENDORSE
    // -------------------------------
    public function saveAssessment(Request $request)
    {
        DB::beginTransaction();
        try {
            $userRole     = $request->role_id;
            $workflowType = $request->workflow_type ?? 'smooth';
            $config       = $this->workflowConfig($workflowType);
            // $config       = $this->workflowConfig();

            // 1️⃣ Save checklist entries
            foreach ($request->assessments as $assessment) {
                DB::table('tbl_app_checklist_entry')->updateOrInsert(
                    [
                        'parent_id'  => $request->application_id,
                        'chklist_id' => $assessment['permit_checklist_id'],
                    ],
                    [
                        'answer'     => $assessment['assessment'] === 'passed' ? 'yes' : 'no',
                        'remarks'    => $assessment['remarks'],
                        'assessment' => $assessment['assessment'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            // 2️⃣ Save findings only
            DB::table('tbl_application_checklist')
                ->where('id', $request->application_id)
                ->update([
                    'findings'        => $request->onsite['findings'],
                    'recommendations' => $request->onsite['recommendations'],
                    'updated_at'      => now(),
                ]);

            // 3️⃣ Determine next role
            $currentIndex = array_search($userRole, $config['sequence']);
            $nextRole     = $config['sequence'][$currentIndex + 1] ?? null;

            if (!$nextRole) {

                // 🔥 Generate Permit Number when RED approves
                $permitNo = $this->generatePermitNumber($request->application_id);

                DB::table('tbl_application_checklist')
                    ->where('id', $request->application_id)
                    ->update([
                        'application_status' => self::STATUS_APPROVED_BY_RED,
                        'permit_no'          => $permitNo,
                        'date_approved_red'  => now(),
                        'updated_at'         => now(),
                    ]);

                // ✅ INSERT ROUTING HISTORY FOR RED APPROVAL
                $route_order = DB::table('tbl_application_routing')
                    ->where('application_id', $request->application_id)
                    ->count() + 1;

                DB::table('tbl_application_routing')->insert([
                    'application_id' => $request->application_id,
                    'sender_id'      => $request->userId,   // RED user
                    'receiver_id'    => $request->userId,               // no next receiver (final)
                    'action'         => 'Approved by RED',
                    'remarks'        => "Permit No: {$permitNo}",
                    'is_read'        => 1,
                    'route_order'    => $route_order,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                DB::commit();

                return response()->json([
                    'status'  => 'success',
                    'message' => "Application approved by RED. Permit No: {$permitNo}",
                ], 200);
            }

            // 4️⃣ Update status and stamp endorse date
            $nextStatus = $config['endorse_status'][$userRole] ?? null;
            if (!$nextStatus) throw new \Exception("No endorse status mapped for role {$userRole}");

            $updateData = ['application_status' => $nextStatus, 'updated_at' => now()];
            if (isset($config['endorse_dates'][$userRole])) {
                $updateData[$config['endorse_dates'][$userRole]] = now();
            }

            DB::table('tbl_application_checklist')
                ->where('id', $request->application_id)
                ->update($updateData);

            // 5️⃣ Insert routing record
            $receiver = DB::table('users')->where('role_id', $nextRole)->orderBy('id', 'asc')->first();
            if (!$receiver) throw new \Exception("No user found for role {$nextRole}");

            $route_order = DB::table('tbl_application_routing')
                ->where('application_id', $request->application_id)
                ->count() + 1;

            $roleLabels = [
                self::TECHNICAL_STAFF          => 'Technical Staff',
                self::CHIEF_RPS                => 'Chief, RPS',
                self::CENRO_OFFICER            => 'CENRO Chief',
                self::PENRO_TECHNICAL_STAFF    => 'PENRO Technical Staff',
                self::PENRO_CHIEF_RPS          => 'PENRO Chief, RPS',
                self::PENRO_CHIEF_TSD          => 'PENRO Chief, TSD',
                self::PENRO_OFFICER            => 'PENRO Chief',
                self::REGIONAL_TECHNICAL_STAFF => 'Regional Technical Staff',
                self::FUS_CHIEF                => 'FUS Chief',
                self::LPDD_CHIEF               => 'LPDD Chief',
                self::ARDTS                    => 'ARDTS',
                self::RED                      => 'RED',
            ];

            DB::table('tbl_application_routing')->insert([
                'application_id' => $request->application_id,
                'sender_id'      => $request->userId,
                'receiver_id'    => $receiver->id,
                'action'         => "Submitted to " . ($roleLabels[$nextRole] ?? 'Next Level'),
                'remarks'        => "Waiting to be received by " . ($roleLabels[$nextRole] ?? 'Next Level'),
                'is_read'        => 0,
                'route_order'    => $route_order,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            DB::commit();
            return response()->json([
                'status'  => 'success',
                'message' => "Assessment saved and routed to {$roleLabels[$nextRole]} successfully.",
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // -------------------------------
    // RECEIVE APPLICATION
    // -------------------------------
    public function receive(Request $request)
    {
        DB::beginTransaction();

        try {
            $userRole = $request->role_id;
            $config   = $this->workflowConfig();

            // -----------------------------
            // 1️⃣ Get the receive status from workflowConfig
            // -----------------------------
            $receiveStatus = $config['receive_status'][$userRole] ?? null;
            if (!$receiveStatus) {
                throw new \Exception("No receive status mapped for role {$userRole}");
            }

            // -----------------------------
            // 2️⃣ Prepare data for updating checklist
            // -----------------------------
            $updateData = [
                'application_status' => $receiveStatus,
                'updated_at'         => now(),
            ];

            // Stamp the date_received_* field automatically
            if (isset($config['receive_dates'][$userRole])) {
                $updateData[$config['receive_dates'][$userRole]] = now();
            }

            // -----------------------------
            // 3️⃣ Update the application checklist status
            // -----------------------------
            DB::table('tbl_application_checklist')
                ->where('id', $request->id)
                ->update($updateData);

            // -----------------------------
            // 4️⃣ Insert routing record
            // -----------------------------
            // For receive action, the sender is the previous user (provided as userId)
            // The receiver is the current user (who just clicked receive)
            $route_order = DB::table('tbl_application_routing')
                ->where('application_id', $request->id)
                ->count() + 1;

            $roleLabels = [
                self::TECHNICAL_STAFF          => 'Technical Staff',
                self::CHIEF_RPS                => 'Chief, RPS',
                self::CENRO_OFFICER            => 'CENRO Chief',
                self::PENRO_TECHNICAL_STAFF    => 'PENRO Technical Staff',
                self::PENRO_CHIEF_RPS          => 'PENRO Chief, RPS',
                self::PENRO_CHIEF_TSD          => 'PENRO Chief, TSD',
                self::PENRO_OFFICER            => 'PENRO Chief',
                self::REGIONAL_TECHNICAL_STAFF => 'Regional Technical Staff',
                self::FUS_CHIEF                => 'FUS Chief',
                self::LPDD_CHIEF               => 'LPDD Chief',
                self::ARDTS                    => 'ARDTS',
                self::RED                      => 'RED',
            ];

            DB::table('tbl_application_routing')->insert([
                'application_id' => $request->id,
                'sender_id'      => $request->user_id,        // the user who sent or routed it
                'receiver_id'    => auth()->user()->id,     // current user receiving the app
                'action'         => "Received by " . ($roleLabels[$userRole] ?? 'Staff'),
                'remarks'        => "Application received by " . ($roleLabels[$userRole] ?? 'Staff'),
                'is_read'        => 0,
                'route_order'    => $route_order,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Application received successfully.'
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    // -------------------------------
    // RETURN APPLICATION
    // -------------------------------
    public function return(Request $request)
    {
        DB::beginTransaction();

        try {
            $userRole = $request->role_id;
            $config   = $this->workflowConfig();

            /*
        |--------------------------------------------------------------------------
        | 1️⃣ ROLE LABEL (for remarks history)
        |--------------------------------------------------------------------------
        */
            $roleLabels = [
                self::TECHNICAL_STAFF          => 'Technical Staff',
                self::CHIEF_RPS                => 'Chief, RPS',
                self::CENRO_OFFICER            => 'CENRO Chief',
                self::PENRO_TECHNICAL_STAFF    => 'PENRO Technical Staff',
                self::PENRO_CHIEF_RPS          => 'PENRO Chief, RPS',
                self::PENRO_CHIEF_TSD          => 'PENRO Chief, TSD',
                self::PENRO_OFFICER            => 'PENRO Chief',
                self::REGIONAL_TECHNICAL_STAFF => 'Regional Technical Staff',
                self::FUS_CHIEF                => 'FUS Chief',
                self::LPDD_CHIEF               => 'LPDD Chief',
                self::ARDTS                    => 'ARDTS',
                self::RED                      => 'RED',
            ];

            $roleLabel = $roleLabels[$userRole] ?? 'Staff';

            /*
        |--------------------------------------------------------------------------
        | 2️⃣ SAVE ASSESSMENTS WITH MERGED REMARKS
        |--------------------------------------------------------------------------
        */
            foreach ($request->assessments as $assessment) {

                if (
                    $assessment['assessment'] === 'failed' &&
                    empty(trim($assessment['remarks'] ?? ''))
                ) {
                    throw new \Exception("Remarks is required for Not Compliance.");
                }

                $existingRemarks = DB::table('tbl_app_checklist_entry')
                    ->where('parent_id', $request->id)
                    ->where('chklist_id', $assessment['permit_checklist_id'])
                    ->value('remarks');

                $remarksArray = [];

                if ($existingRemarks) {
                    foreach (explode("\n", $existingRemarks) as $line) {
                        if (!str_starts_with($line, $roleLabel . ':')) {
                            $remarksArray[] = $line;
                        }
                    }
                }

                $remarksArray[] = $roleLabel . ': ' . ($assessment['remarks'] ?? 'no data');

                DB::table('tbl_app_checklist_entry')->updateOrInsert(
                    [
                        'parent_id'  => $request->id,
                        'chklist_id' => $assessment['permit_checklist_id'],
                    ],
                    [
                        'answer'     => $assessment['assessment'] === 'passed' ? 'yes' : 'no',
                        'remarks'    => implode("\n\n", $remarksArray,),
                        'assessment' => $assessment['assessment'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            /*
        |--------------------------------------------------------------------------
        | 3️⃣ SAVE FINDINGS / RECOMMENDATIONS
        |--------------------------------------------------------------------------
        */
            DB::table('tbl_application_checklist')
                ->where('id', $request->id)
                ->update([
                    'findings'        => $request->onsite['findings'] ?? null,
                    'recommendations' => $request->onsite['recommendations'] ?? null,
                    'updated_at'      => now(),
                ]);

            /*
        |--------------------------------------------------------------------------
        | 4️⃣ DETERMINE RETURN TARGET ROLE (WORKFLOW)
        |--------------------------------------------------------------------------
        */
            $returnStatus = null;
            $targetRole   = null;

            switch ($userRole) {

                // CENRO level → return to Technical Staff
                case self::CHIEF_RPS:
                case self::CENRO_OFFICER:
                    $targetRole   = self::TECHNICAL_STAFF;
                    $returnStatus = self::STATUS_RETURNED_TO_CENRO_TECHNICAL;
                    break;

                // PENRO level → return to PENRO Technical
                case self::PENRO_CHIEF_RPS:
                case self::PENRO_CHIEF_TSD:
                case self::PENRO_OFFICER:
                    $targetRole   = self::PENRO_TECHNICAL_STAFF;
                    $returnStatus = self::STATUS_RETURNED_TO_PENRO_TECHNICAL;
                    break;

                // REGIONAL level → return to Regional Technical
                case self::FUS_CHIEF:
                case self::LPDD_CHIEF:
                case self::ARDTS:
                case self::RED:
                    $targetRole   = self::REGIONAL_TECHNICAL_STAFF;
                    $returnStatus = self::STATUS_RETURNED_TO_REGIONAL_TECHNICAL;
                    break;

                default:
                    throw new \Exception('This role cannot return applications.');
            }

            /*
        |--------------------------------------------------------------------------
        | 5️⃣ UPDATE APPLICATION STATUS TO RETURNED
        |--------------------------------------------------------------------------
        */
            DB::table('tbl_application_checklist')
                ->where('id', $request->id)
                ->update([
                    'application_status' => $returnStatus,
                    'updated_at'         => now(),
                ]);

            /*
        |--------------------------------------------------------------------------
        | 6️⃣ FIND RECEIVER (TARGET TECHNICAL ROLE)
        |--------------------------------------------------------------------------
        */
            $receiver = DB::table('users')
                ->where('role_id', $targetRole)
                ->orderBy('id', 'asc')
                ->first();

            if (!$receiver) {
                throw new \Exception("No user found for role {$targetRole}");
            }

            /*
        |--------------------------------------------------------------------------
        | 7️⃣ INSERT ROUTING RECORD
        |--------------------------------------------------------------------------
        */
            $route_order = DB::table('tbl_application_routing')
                ->where('application_id', $request->id)
                ->count() + 1;

            DB::table('tbl_application_routing')->insert([
                'application_id' => $request->id,
                'sender_id'      => $request->user_id,
                'receiver_id'    => $receiver->id,
                'action'         => "Returned to " . ($roleLabels[$targetRole] ?? 'Technical Level'),
                'remarks'        => "Waiting to be received by " . ($roleLabels[$targetRole] ?? 'Technical Level'),
                'is_read'        => 0,
                'route_order'    => $route_order,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            DB::commit();

            return response()->json([
                'status'  => 'success',
                'message' => 'Application returned with assessment successfully.',
            ], 200);
        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function generatePermitNumber(int $applicationId): string
    {
        // 1️⃣ Get application_no
        $applicationNo = DB::table('tbl_application_checklist')
            ->where('id', $applicationId)
            ->value('application_no');

        if (!$applicationNo) {
            throw new \Exception('Application number not found.');
        }

        // 2️⃣ Extract suffix from application_no (first letter before dash)
        $suffix = explode('-', $applicationNo)[0]; // L, Q, R, etc.

        if (!$suffix) {
            throw new \Exception('Invalid application number format.');
        }

        // 3️⃣ Build base format
        $datePart = now()->format('mdY'); // MMDDYYYY
        $baseFormat = "DENR-IV-A-{$datePart}";

        // 4️⃣ Get latest permit for the same date
        $latestPermit = DB::table('tbl_application_checklist')
            ->where('permit_no', 'like', "{$baseFormat}-%")
            ->orderBy('permit_no', 'desc')
            ->lockForUpdate()
            ->first();

        // 5️⃣ Get next sequence
        if ($latestPermit && preg_match('/-(\d{2})[A-Z]$/', $latestPermit->permit_no, $matches)) {
            $nextSequence = intval($matches[1]) + 1;
        } else {
            $nextSequence = 1;
        }

        $sequence = str_pad($nextSequence, 2, '0', STR_PAD_LEFT);

        // 6️⃣ Final permit number
        return "{$baseFormat}-{$sequence}{$suffix}";
    }



    public function returnApplication(Request $request)
    {
        DB::beginTransaction();

        try {

            /*
        |--------------------------------------------------------------------------
        | 1️⃣ GET ROLE LABEL OF CURRENT USER
        |--------------------------------------------------------------------------
        */
            $roleLabel = match ($request->role_id) {
                1 => 'Technical Staff',
                2 => 'CHIEF RPS',
                3 => 'CENRO Chief',
                4 => 'PENRO Technical Staff',
                5 => 'CHIEF RPS',
                6 => 'CHIEF TSD',
                7 => 'PENRO CHIEF',
                8 => 'Regional Technical Staff',
                9 => 'FUS Chief',
                10 => 'LPDD Chief',
                11 => 'ARDTS',
                12 => 'RED',
                default => 'Staff',
            };

            /*
        |--------------------------------------------------------------------------
        | 2️⃣ LOOP ALL ASSESSMENTS
        |--------------------------------------------------------------------------
        */
            foreach ($request->assessments as $assessment) {

                // ❗ Require remarks when NOT COMPLIANCE
                if (
                    $assessment['assessment'] === 'failed' &&
                    empty(trim($assessment['remarks'] ?? ''))
                ) {
                    throw new \Exception("Remarks is required for Not Compliance.");
                }

                /*
            |--------------------------------------------------------------------------
            | 3️⃣ GET EXISTING REMARKS (IF ANY)
            |--------------------------------------------------------------------------
            */
                $existingRemarks = DB::table('tbl_app_checklist_entry')
                    ->where('parent_id', $request->application_id)
                    ->where('chklist_id', $assessment['permit_checklist_id'])
                    ->value('remarks');

                $remarksArray = [];

                if ($existingRemarks) {
                    $lines = explode("\n", $existingRemarks);

                    // keep other roles, remove current role line
                    foreach ($lines as $line) {
                        if (!str_starts_with($line, $roleLabel . ':')) {
                            $remarksArray[] = $line;
                        }
                    }
                }

                /*
            |--------------------------------------------------------------------------
            | 4️⃣ APPEND CURRENT ROLE REMARK
            |--------------------------------------------------------------------------
            */
                $remarksArray[] = $roleLabel . ': ' . ($assessment['remarks'] ?? 'no data');

                $newRemarks = implode("\n", $remarksArray);

                /*
            |--------------------------------------------------------------------------
            | 5️⃣ SAVE CHECKLIST ENTRY
            |--------------------------------------------------------------------------
            */
                DB::table('tbl_app_checklist_entry')->updateOrInsert(
                    [
                        'parent_id'  => $request->application_id,
                        'chklist_id' => $assessment['permit_checklist_id'],
                    ],
                    [
                        'answer'     => $assessment['assessment'] === 'passed' ? 'yes' : 'no',
                        'remarks'    => $newRemarks, // ✅ merged remarks
                        'assessment' => $assessment['assessment'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            /*
        |--------------------------------------------------------------------------
        | 6️⃣ UPDATE APPLICATION STATUS (RETURN TO TECH STAFF)
        |--------------------------------------------------------------------------
        */
            $rps_chief = DB::table('users')
                ->where('office_id', 6)
                ->where('role_id', self::TECHNICAL_STAFF)
                ->orderBy('id', 'asc')
                ->first();

            $route_order = DB::table('tbl_application_routing')
                ->where('application_id', $request->application_id)
                ->count() + 1;

            DB::table('tbl_application_checklist')->updateOrInsert(
                [
                    'id' => $request->application_id
                ],
                [
                    'application_status' => self::STATUS_RETURN_TO_TECHNICAL_STAFF,
                    'findings'        => $request->onsite['findings'] ?? null,
                    'recommendations' => $request->onsite['recommendations'] ?? null,
                    'updated_at'      => now(),
                    'created_at'      => now(),
                ]
            );

            DB::table('tbl_application_routing')->insert([
                'application_id' => $request->application_id,
                'sender_id'      => $request->user_id,
                'receiver_id'    => $rps_chief->id,
                'action'         => 'Returned to Technical Staff',
                'remarks'        => 'Waiting to be received by Technical Staff',
                'is_read'        => 0,
                'route_order'    => $route_order,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Assessment saved successfully'
            ], 200);
        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Failed to save assessment',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
}
