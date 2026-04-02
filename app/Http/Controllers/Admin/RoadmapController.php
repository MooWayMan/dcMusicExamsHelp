<?php

// app/Http/Controllers/Admin/RoadmapController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class RoadmapController extends Controller
{
    public function index(): Response
    {
        $phases = [
            [
                'number' => 1,
                'title' => 'Foundation',
                'status' => 'complete',
                'subtitle' => 'Admin panel, brand identity & infrastructure',
                'milestones' => [
                    ['title' => 'Admin panel with full CRUD', 'done' => true],
                    ['title' => 'Brand identity & logo design', 'done' => true],
                    ['title' => 'Database schema & models', 'done' => true],
                    ['title' => 'Task manager with GCal sync', 'done' => true],
                    ['title' => 'Email DNS setup (MX, SPF, DMARC, DKIM)', 'done' => true],
                    ['title' => 'Constructor component library', 'done' => true],
                    ['title' => 'Page animations system', 'done' => true],
                ],
            ],
            [
                'number' => 2,
                'title' => 'Launch Ready',
                'status' => 'active',
                'subtitle' => 'Content, accessibility & business decisions',
                'milestones' => [
                    ['title' => 'Landing page content (why use this, incentives, FAQs)', 'done' => false],
                    ['title' => 'Decide pricing and commission levels', 'done' => false],
                    ['title' => 'Accessibility audit fixes (contrast, aria, labels)', 'done' => false],
                    ['title' => 'Restyle auth pages with brand constructors', 'done' => false],
                    ['title' => 'Dashboard time filters (quarter/year)', 'done' => false],
                    ['title' => 'Task notes/journal feature', 'done' => false],
                ],
            ],
            [
                'number' => 3,
                'title' => 'Go Live',
                'status' => 'upcoming',
                'subtitle' => 'Launch to teachers & start marketing',
                'milestones' => [
                    ['title' => 'Email campaign to inform teachers of new brand', 'done' => false],
                    ['title' => 'Create MusicExams.help Facebook page', 'done' => false],
                    ['title' => 'Create MusicExams.help LinkedIn profile', 'done' => false],
                    ['title' => 'Design and issue digital badges for teachers', 'done' => false],
                    ['title' => 'Promote centre code 120 across all channels', 'done' => false],
                    ['title' => 'Clean up HubSpot contacts (72 mixed records)', 'done' => false],
                ],
            ],
            [
                'number' => 4,
                'title' => 'Growth',
                'status' => 'upcoming',
                'subtitle' => 'Revenue streams & content strategy',
                'milestones' => [
                    ['title' => 'Affiliate book links per syllabus (10% ebook commission)', 'done' => false],
                    ['title' => 'Social media content calendar (Search/Outreach/Suggested)', 'done' => false],
                    ['title' => 'Offer INSET/CPD training sessions to schools', 'done' => false],
                    ['title' => 'Promote Trinity Access Fund (2027 deadline)', 'done' => false],
                    ['title' => 'Faber trade discount book sales (33%)', 'done' => false],
                    ['title' => 'Teacher reward system (prize draws, hall of fame)', 'done' => false],
                ],
            ],
            [
                'number' => 5,
                'title' => 'Scale',
                'status' => 'upcoming',
                'subtitle' => 'Analytics, expansion & passive income',
                'milestones' => [
                    ['title' => 'Dashboard analytics & reporting', 'done' => false],
                    ['title' => 'Expand teacher network beyond current schools', 'done' => false],
                    ['title' => 'Onboard internally-managed Trinity centres', 'done' => false],
                    ['title' => 'Build passive income from digital exam commissions', 'done' => false],
                    ['title' => 'Reduce dependency on teaching income', 'done' => false],
                ],
            ],
        ];

        return Inertia::render('admin/Roadmap/Index', [
            'phases' => $phases,
        ]);
    }
}
