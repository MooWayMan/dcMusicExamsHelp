<?php

// app/Http/Controllers/DashboardRewardsController.php

namespace App\Http\Controllers;

use App\Models\ExamContact;
use App\Services\EntryCertificates;
use App\Services\QuarterCertificateBatch;
use App\Services\TeacherEntries;
use App\Services\TeacherRewards;
use Illuminate\Http\Request;

/**
 * The teacher's own appreciation certificate from the dashboard's Rewards
 * card. Like the other dashboard downloads, whose certificate it is comes
 * from the signed-in user alone; the admin preview has its own route that
 * names the contact. A quarter with no badge is a 404.
 */
class DashboardRewardsController extends Controller
{
    public function __construct(
        private readonly TeacherEntries $teacherEntries,
        private readonly TeacherRewards $rewards,
        private readonly QuarterCertificateBatch $batch,
    ) {}

    public function certificate(Request $request, int $year, int $quarter)
    {
        return $this->certificateResponse($this->teacherEntries->contactFor($request->user()), $quarter, $year);
    }

    /** Admin-only: the same, for the teacher being previewed. */
    public function certificateForContact(ExamContact $contact, int $year, int $quarter)
    {
        return $this->certificateResponse($contact, $quarter, $year);
    }

    private function certificateResponse(?ExamContact $contact, int $quarter, int $year)
    {
        abort_unless($contact, 404);

        $tier = $this->rewards->certificateFor($contact, $quarter, $year);
        abort_unless($tier, 404);

        $pdf = $this->batch->teacherCertificatePdf((string) $contact->name, $tier, $quarter, $year);
        abort_if($pdf === null, 404);

        $file = EntryCertificates::safe((string) $contact->name)."_Q{$quarter}_{$year}_".str_replace(' ', '_', $tier).'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$file.'"',
        ]);
    }
}
