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
                    ['title' => 'Faber Discounts page (4 cards, infographic, detail sections)', 'done' => true],
                    ['title' => 'For Teachers section content & navigation', 'done' => true],
                    ['title' => 'Email templates (Gmail drafts for teacher enquiries)', 'done' => true],
                    ['title' => 'Shared Google Calendars (exam dates & offers)', 'done' => true],
                    ['title' => 'HTML email signature & Gmail settings', 'done' => true],
                    ['title' => 'Student Certificate of Achievement — Merit (Canva)', 'done' => false],
                    ['title' => 'Rewards graphic with correct logo', 'done' => false],
                    ['title' => 'Restyle auth pages with brand constructors', 'done' => false, 'description' => 'Sync restyled pages to template repo too'],
                    ['title' => 'QR code redirect system (musicexams.help/go/...)', 'done' => false, 'description' => 'Short redirect routes for QR codes on flyers, certificates, business cards — plus own /links page instead of Linktree'],
                    ['title' => 'Update YouTube channel branding', 'done' => false],
                    ['title' => 'Booking system step-by-step guides', 'done' => false, 'description' => 'Help page showing how to use all 3 Trinity booking systems (Digital, Classical & Jazz F2F, Rock & Pop F2F)'],
                    ['title' => 'Domain name & business name', 'done' => false, 'description' => 'Confirm sole trader name with accountant, connect musicexams.help domain'],
                    ['title' => 'Meta tags & Open Graph', 'done' => false, 'description' => 'Social sharing titles, descriptions, images for Facebook/LinkedIn'],
                    ['title' => 'SEO audit implementation', 'done' => false, 'description' => 'Full audit already done — apply fixes once domain is live'],
                    ['title' => 'Disable public registration for launch', 'done' => false, 'description' => 'Teacher dashboard not ready yet — no point letting people register until it has content'],
                    ['title' => 'Turn off hibernation', 'done' => false, 'description' => 'Remove sleep mode for musicexams.help at go-live'],
                ],
            ],
            [
                'number' => 3,
                'title' => 'Go Live',
                'status' => 'upcoming',
                'subtitle' => 'Launch to teachers & start marketing',
                'milestones' => [
                    ['title' => 'Create musicExams.help Facebook page', 'done' => false, 'description' => 'Priority — teacher groups (UK Music Teachers, UK&I LAR Network) are all on Facebook'],
                    ['title' => 'Create musicExams.help LinkedIn page', 'done' => false, 'description' => 'Professional credibility with teachers and schools'],
                    ['title' => 'Set up musicExams.help Instagram', 'done' => false, 'description' => 'Currently @moowayman (52 followers) — repurpose or create new'],
                    ['title' => 'Set up musicExams.help TikTok', 'done' => false, 'description' => 'Currently @moowayman (9 followers) — short exam tip videos'],
                    ['title' => 'Email campaign to inform teachers of new brand', 'done' => false],
                    ['title' => 'First social media content (Search/Outreach/Suggested)', 'done' => false, 'description' => "Jaynele-Leigh Baker's framework — Facebook + LinkedIn first"],
                    ['title' => 'Promote centre code 120 across all channels', 'done' => false],
                    ['title' => 'Clean up HubSpot contacts (72 mixed records)', 'done' => false],
                    ['title' => 'Update old flyer QR codes to point to new site', 'done' => false, 'description' => 'Old printed materials have QR codes pointing to outdated URLs'],
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
                    ['title' => 'Syllabus finder with teacher comments & voting', 'done' => false, 'description' => 'Browse by instrument + grade, community recommendations — massive USP'],
                    ['title' => 'Affiliate book links per syllabus (10% ebook commission)', 'done' => false],
                    ['title' => 'Campaigns system (Grade 1 Challenge etc.)', 'done' => false, 'description' => 'Admin panel feature — umbrella initiatives grouping tasks, goals, timelines'],
                    ['title' => 'Offer INSET/CPD training sessions to schools', 'done' => false],
                    ['title' => 'Quarterly prize draws & gift tokens', 'done' => false, 'description' => '£20 token for top scorers, split for ties (min £5 each)'],
                    ['title' => 'Social media content calendar', 'done' => false],
                    ['title' => 'Issue digital badges to teachers', 'done' => false, 'description' => 'Bronze/Silver/Gold/Platinum tiers — needs Canva graphics first'],
                    ['title' => 'F2F exam day thank you cards (printed)', 'done' => false],
                    ['title' => 'Parent email follow-up flyers (digital candidates)', 'done' => false],
                    ['title' => 'Teacher dashboard content', 'done' => false, 'description' => 'Replace placeholder — show teacher their students, results, entries, awards'],
                    ['title' => 'Teacher free webpage profiles', 'done' => false, 'description' => 'One-page profiles for teachers on musicExams.help, modelled on MooWay teacher pages'],
                    ['title' => 'Instrument family pages', 'done' => false, 'description' => '/for-teachers/brass, /woodwind, /singing, /strings, /piano, /guitar'],
                    ['title' => 'Help wizard / mini AI assistant', 'done' => false, 'description' => 'Floating help button — decision-tree wizard (Phase 1), AI chatbot (Phase 2)'],
                    ['title' => 'Email auto-reply automation', 'done' => false, 'description' => 'Categorised teacher enquiry templates via Laravel + Gmail API'],
                    ['title' => 'Weekly/monthly email digest & blog', 'done' => false, 'description' => 'Drip-feed site content to teachers — keep them coming back'],
                    ['title' => 'Book ordering guide for teachers', 'done' => false, 'description' => 'Show real postage/cost examples from Faber orders — needs invoice scans'],
                    ['title' => 'Weekly quiz feature', 'done' => false, 'description' => 'Interactive quizzes for students, teachers and parents — engagement + repeat visits'],
                    ['title' => 'Explainer videos (YouTube + HeyGen)', 'done' => false, 'description' => 'Spider-Man scripts, HeyGen for production — exam how-to content'],
                    ['title' => 'Jazz exams content area', 'done' => false, 'description' => 'Trinity jazz trumpet, ABRSM jazz up to Grade 8, Rockschool'],
                    ['title' => 'Diploma exams marketing push', 'done' => false, 'description' => 'Wirral has new grand piano for F2F piano diplomas'],
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
                    ['title' => 'Trinity Centre Finder (admin tool)', 'done' => false, 'description' => 'Searchable database of F2F centres for quick teacher queries'],
                    ['title' => 'Faber trade discount book sales (33%)', 'done' => false],
                    ['title' => 'Promote Trinity Access Fund (2027)', 'done' => false, 'description' => 'Annual grants for disadvantaged candidates (up to £300/candidate)'],
                    ['title' => 'Admin panel as reusable CRM / potential SaaS', 'done' => false, 'description' => 'Powers all 3 apps via template repo — potential subscription product'],
                    ['title' => 'Build passive income from digital exam commissions', 'done' => false, 'description' => '20% DG commission, zero overheads — scalable nationwide'],
                    ['title' => 'Paid advertising strategy', 'done' => false, 'description' => 'Proper Facebook/social targeting — learn from the £2k flop'],
                    ['title' => 'Digital theory exams (12.5% commission)', 'done' => false, 'description' => 'Explore centre code usage for theory entries'],
                    ['title' => 'Shared Google Calendars for teachers', 'done' => false, 'description' => 'Public calendars: exam dates, Faber offers, CPD — separate per exam board'],
                ],
            ],
        ];

        return Inertia::render('admin/Roadmap/Index', [
            'phases' => $phases,
        ]);
    }
}
