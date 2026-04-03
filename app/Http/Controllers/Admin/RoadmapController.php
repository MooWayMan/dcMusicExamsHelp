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
                    ['title' => 'Task notes/journal feature', 'done' => true],
                    ['title' => 'Accessibility audit fixes (contrast, aria, labels)', 'done' => true],
                    ['title' => 'Dashboard time filters (quarter/year)', 'done' => true],
                    ['title' => 'Pest test suite (30+ tests)', 'done' => true],
                ],
            ],
            [
                'number' => 2,
                'title' => 'Launch Ready',
                'status' => 'active',
                'subtitle' => 'Content, visuals & business decisions',
                'milestones' => [
                    ['title' => 'Landing page content (why use this, incentives, FAQs)', 'done' => true],
                    ['title' => 'Landing page icon cards & visual polish', 'done' => true],
                    ['title' => 'Student Hall of Fame banner (Canva)', 'done' => true],
                    ['title' => 'Teacher Certificate of Appreciation (Canva)', 'done' => true],
                    ['title' => 'Student Certificate of Achievement — Distinction (Canva)', 'done' => true],
                    ['title' => 'Teacher Appreciation badges 10+/20+/30+ (Canva)', 'done' => true],
                    ['title' => 'Centre code gradient banners on landing page', 'done' => true],
                    ['title' => 'FAQ + Ready to Get Started combined layout', 'done' => true],
                    ['title' => 'Student Certificate of Achievement — Merit (Canva)', 'done' => false],
                    ['title' => 'Rewards graphic with correct logo', 'done' => false],
                    ['title' => 'Decide pricing and commission levels', 'done' => false],
                    ['title' => 'Restyle auth pages with brand constructors', 'done' => false],
                    ['title' => 'QR code redirect system (musicexams.help/go/...)', 'done' => false],
                    ['title' => 'Update YouTube channel branding', 'done' => false],
                ],
            ],
            [
                'number' => 3,
                'title' => 'Go Live',
                'status' => 'upcoming',
                'subtitle' => 'Launch to teachers & start marketing',
                'milestones' => [
                    ['title' => 'Create MusicExams.help Facebook page', 'done' => false],
                    ['title' => 'Create MusicExams.help LinkedIn profile', 'done' => false],
                    ['title' => 'Email campaign to inform teachers of new brand', 'done' => false],
                    ['title' => 'First social media content (Search/Outreach/Suggested)', 'done' => false],
                    ['title' => 'Promote centre code 120 across all channels', 'done' => false],
                    ['title' => 'Clean up HubSpot contacts (72 mixed records)', 'done' => false],
                    ['title' => 'Update old flyer QR codes to point to new site', 'done' => false],
                ],
            ],
            [
                'number' => 4,
                'title' => 'Growth',
                'status' => 'upcoming',
                'subtitle' => 'Features, revenue streams & community',
                'milestones' => [
                    ['title' => 'Build Student Hall of Fame page', 'done' => false],
                    ['title' => 'Build Thank You page (all students listed)', 'done' => false],
                    ['title' => 'Build Awards page (full rewards programme showcase)', 'done' => false],
                    ['title' => 'Syllabus finder with teacher comments & voting', 'done' => false],
                    ['title' => 'Affiliate book links per syllabus (10% ebook commission)', 'done' => false],
                    ['title' => 'Campaigns system (Grade 1 Challenge etc.)', 'done' => false],
                    ['title' => 'Offer INSET/CPD training sessions to schools', 'done' => false],
                    ['title' => 'Quarterly prize draws & gift tokens', 'done' => false],
                    ['title' => 'Social media content calendar', 'done' => false],
                    ['title' => 'Issue digital badges to teachers', 'done' => false],
                    ['title' => 'F2F exam day thank you cards (printed)', 'done' => false],
                    ['title' => 'Parent email follow-up flyers (digital candidates)', 'done' => false],
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
                    ['title' => 'Faber trade discount book sales (33%)', 'done' => false],
                    ['title' => 'Promote Trinity Access Fund (2027)', 'done' => false],
                    ['title' => 'Admin panel as reusable CRM / potential SaaS', 'done' => false],
                    ['title' => 'Build passive income from digital exam commissions', 'done' => false],
                ],
            ],
        ];

        return Inertia::render('admin/Roadmap/Index', [
            'phases' => $phases,
        ]);
    }
}
