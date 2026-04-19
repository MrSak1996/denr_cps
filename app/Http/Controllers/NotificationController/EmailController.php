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
            // ✅ validate incoming data
            $request->validate([
                'email' => 'required|email',
                'applicant_name' => 'required|string',
                'address' => 'required|string',
                'application_no' => 'required|string',
            ]);

            // ✅ get data from request (FROM VUE)
            $redEmail = $request->email;
            $applicantName = $request->applicant_name;
            $address = $request->address;
            $applicationNo = $request->application_no;

            // optional (if you have company_name later)
            $companyName = $request->company_name ?? 'N/A';

            // ✅ save notification (FIXED: removed undefined $permitNo)
            NotificationModel::create([
                'email' => $redEmail,
                'permit_no' => $applicationNo,
                'expires_at' => Carbon::now()->addDays(7),
            ]);

            // ✅ message body
            $messageBody = "
Dear Regional Executive Director,

This is to inform you that a new Permit to Purchase application has been submitted and is pending your approval.

Application Details:
Application Number: $applicationNo
Applicant Name: $applicantName
Company Name: $companyName
Address: $address

Kindly review and approve the application at your earliest convenience.

Thank you for your attention.

Best regards,
Chainsaw Permitting System
Department of Environment and Natural Resources
";

            // ✅ send email
            Mail::raw($messageBody, function ($message) use ($redEmail, $applicationNo) {
                $message->to($redEmail)
                    ->subject("Permit Application #$applicationNo - For Approval");
            });

            return response()->json([
                'status' => true,
                'message' => 'Notification sent successfully!',
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
