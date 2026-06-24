<?php

namespace App\Http\Controllers\NotificationController;

use App\Http\Controllers\Controller;
use App\Models\Notification\NotificationModel;
use App\Models\Application\ChainsawIndividualApplication;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;


class EmailController extends Controller
{

    /**
     * Send OTP to email
     */



    public function sendEmail(Request $request)
    {
        try {

            $request->validate([
                'email' => 'required|email',
                'applicant_name' => 'required|string',
                'application_no' => 'required|string',
                'address' => 'nullable|string',
                'role_id' => 'required'
            ]);

            $to = $request->email;
            $applicantName = $request->applicant_name;
            $applicationNo = $request->application_no;
            $address = $request->address ?? 'N/A';
            $companyName = $request->company_name ?? 'N/A';
            $role_id = $request->role_id;


            NotificationModel::create([
                'email' => $to,
                'permit_no' => $applicationNo,
                'expires_at' => Carbon::now()->addDays(7),
            ]);
            if ($role_id == 11) {
                $recipient = 'ARD for Technical Services';
            } else if ($role_id == 12) {
                $recipient = 'Regional Executive Director';
            } else {
                $recipient = '';
            }
            $messageBody =
                "Dear {$recipient},

                A new Permit to Purchase application has been submitted and is awaiting your approval.

                ======================================================
                APPLICATION DETAILS
                ======================================================

                Application Number : {$applicationNo}
                Applicant Name     : {$applicantName}
                Company Name       : {$companyName}
                Address            : {$address}

                Please log in to the Chainsaw Permitting System to review and approve this application.

                Thank you.

                Chainsaw Purchase System
                Department of Environment and Natural Resources

                

                Sent: " . now();

            // Mail::raw($messageBody, function ($message) use ($to, $applicationNo) {

            //     $message->to($to)
            //         ->subject("Chainsaw Purchase Sysem No. {$applicationNo} - Approval Required");
            // });

            Mail::raw($messageBody, function ($message) use ($to, $applicationNo) {
                $message->to($to)
                    ->cc([
                        'fus.lpdd4a@gmail.com',
                        'r4a@denr.gov.ph',
                        'kimsacluti10101996@gmail.com',
                    ])
                    ->subject("Chainsaw Purchase System No. {$applicationNo} - Approval Required");
            });


            return response()->json([
                'status' => true,
                'message' => 'Email notification sent successfully.',
                'time' => now()->toDateTimeString(),
            ], 200);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        // Validate request
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6'
        ]);

        $otpRecord = Otp::where('email', $request->email)
            ->where('otp', $request->otp)
            ->where('expires_at', '>=', Carbon::now())
            ->where('is_verified', false)
            ->first();

        if (!$otpRecord) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired OTP.'
            ], 400);
        }

        // Mark as verified
        $otpRecord->update([
            'is_verified' => true
        ]);

        return response()->json([
            'status' => true,
            'message' => 'OTP verified successfully!'
        ], 200);
    }
}
