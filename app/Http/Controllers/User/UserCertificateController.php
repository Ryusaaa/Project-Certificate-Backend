<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserCertificate;
use App\Models\CertificateDownload;
use Illuminate\Support\Facades\Auth;

class UserCertificateController extends Controller
{
    /**
     * Get all certificates for the authenticated user
     */
    public function index()
    {
        $certificates = Auth::user()->certificates()
            ->with(['certificateDownload' => function($query) {
                $query->select('id', 'token', 'filename', 'recipient_name', 'certificate_number', 'expires_at');
            }])
            ->where('status', 'active')
            ->get()
            ->map(function($cert) {
                $download = $cert->certificateDownload;
                return [
                    'id' => $cert->id,
                    'recipient_name' => $download->recipient_name,
                    'certificate_number' => $download->certificate_number,
                    'assigned_at' => $cert->assigned_at->format('Y-m-d H:i:s'),
                    'expires_at' => $download->expires_at?->format('Y-m-d H:i:s'),
                    'view_url' => '/storage/certificates/generated/' . $download->filename,
                    'download_url' => '/sertifikat-templates/download/' . $download->token,
                    'status' => $cert->status
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $certificates
        ]);
    }

    /**
     * Assign certificates to users
     */
    public function assignCertificates(Request $request)
    {
        $validated = $request->validate([
            'assignments' => 'required|array',
            'assignments.*.user_id' => 'required|exists:users,id',
            'assignments.*.certificate_download_id' => 'required|exists:certificate_downloads,id'
        ]);

        foreach ($validated['assignments'] as $assignment) {
            UserCertificate::create([
                'user_id' => $assignment['user_id'],
                'certificate_download_id' => $assignment['certificate_download_id'],
                'assigned_at' => now(),
                'status' => 'active'
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => count($validated['assignments']) . ' sertifikat berhasil diassign'
        ]);
    }

    /**
     * Revoke certificate access
     */
    public function revokeCertificate($id)
    {
        $certificate = UserCertificate::findOrFail($id);
        
        // Only allow revoking if user is admin or owns the certificate
        if (Auth::id() !== $certificate->user_id && !Auth::user()->isAdmin()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $certificate->update(['status' => 'revoked']);

        return response()->json([
            'status' => 'success',
            'message' => 'Akses sertifikat berhasil dicabut'
        ]);
    }
}
