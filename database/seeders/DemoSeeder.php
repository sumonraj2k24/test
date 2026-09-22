<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Core\Database;

final class DemoSeeder
{
    public static function run(): void
    {
        $now = gmdate('Y-m-d H:i:s');
        $hash = password_hash('meridian', PASSWORD_DEFAULT);
        $days = static fn (int $offset, string $time = '15:00:00'): string => gmdate('Y-m-d', time() + $offset * 86400) . ' ' . $time;

        $users = [
            'mira' => self::user('Mira Chen', 'mira@meridian.test', $hash, 'admin', 'Platform steward', 'Keeps the studio honest: approvals, payouts, and the lights on.', $now),
            'amara' => self::user('Amara Okonkwo', 'amara@meridian.test', $hash, 'instructor', 'Product designer', 'Designs interfaces that argue, then teaches other people to do it without theatrics.', $now),
            'jonah' => self::user('Jonah Ellis', 'jonah@meridian.test', $hash, 'instructor', 'Backend engineer', 'Writes PHP that can be operated on a Tuesday, not just demoed on a Thursday.', $now),
            'priya' => self::user('Priya Raman', 'priya@meridian.test', $hash, 'instructor', 'Data lead', 'Treats a query as a sentence someone will have to defend.', $now),
            'leo' => self::user('Leo March', 'leo@meridian.test', $hash, 'instructor', 'Photographer', 'Works in available light and refuses the phrase “just fix it in post.”', $now),
            'helen' => self::user('Helen Cho', 'helen@meridian.test', $hash, 'instructor', 'Offer strategist', 'Helps studios price work people actually finish paying for.', $now),
            'idris' => self::user('Idris Hale', 'idris@meridian.test', $hash, 'instructor', 'Essayist', 'Edits sentences until they can stand up without a slide behind them.', $now),
            'nora' => self::user('Nora Adelayo', 'nora@meridian.test', $hash, 'student', 'Designing a small studio', 'Taking Interface as Argument and the PHP studio course.', $now),
            'chris' => self::user('Chris Pell', 'chris@meridian.test', $hash, 'student', 'Analyst', 'Finished SQL That Tells the Truth and still checks the joins.', $now),
            'mina' => self::user('Mina Park', 'mina@meridian.test', $hash, 'student', 'Weekend photographer', 'Learning to see before buying another lens.', $now),
            'samir' => self::user('Samir Shah', 'samir@meridian.test', $hash, 'student', 'Writer between drafts', 'In the essay workshop, currently losing an argument with a quiz.', $now),
            'lina' => self::user('Lina Berg', 'lina@meridian.test', $hash, 'student', 'Ceramicist and teacher', 'Applied to teach a materials course.', $now),
        ];

        self::permissions();
        Database::insert('instructor_applications', [
            'user_id' => $users['lina'],
            'expertise' => 'Materials and making',
            'years_experience' => 8,
            'statement' => 'I have taught clay to adults for eight years in a shared studio. I want a course that treats process notes as seriously as finished objects — firing logs, failure, and the decision to stop.',
            'sample_url' => 'https://example.com/lina-studio',
            'status' => 'pending',
            'created_at' => $days(-2, '11:20:00'),
        ]);

        $cats = [
            'design' => self::category('Design', 'design', 'Interfaces, critique, and the decisions underneath both.', '#1f4f45', 1),
            'development' => self::category('Development', 'development', 'Systems, not scripts. Code you can still read next year.', '#243044', 2),
            'data' => self::category('Data', 'data', 'Queries, dashboards, and the courage to show the denominator.', '#2a5d72', 3),
            'business' => self::category('Business', 'business', 'Offers, pricing, and the unglamorous work of getting paid.', '#8a4b2f', 4),
            'photography' => self::category('Photography', 'photography', 'Light you already have, used on purpose.', '#8a6a2f', 5),
            'writing' => self::category('Writing', 'writing', 'Essays, edits, and sentences that can travel alone.', '#5c3d4e', 6),
        ];

        $interface = self::course($users['amara'], $cats['design'], [
            'title' => 'Interface as Argument',
            'slug' => 'interface-as-argument',
            'subtitle' => 'Make screens that take a position, then defend it in critique.',
            'level' => 'advanced',
            'price_cents' => 8900,
            'compare_cents' => 12000,
            'thumbnail' => '/assets/covers/interface.svg',
            'is_featured' => 1,
            'status' => 'published',
            'published_at' => $days(-40),
            'seo_title' => 'Interface as Argument — Meridian',
            'seo_description' => 'A studio course on designing interfaces that argue, with critique, a quiz, and a live room.',
            'requirements' => "A product you can screenshot\nComfort reading a layout out loud\nNo design-tool loyalty required",
            'outcomes' => "Read an interface as a claim, not a skin\nBuild a critique that names the decision, not the taste\nShip a one-screen audit another person can follow\nJoin a live critique without hiding behind jargon",
            'description' => "Most interface courses teach components. This one teaches argument. You will learn to say what a screen is claiming, who it is willing to inconvenience, and how to change the structure when the claim is weak.\n\nThe work is written, drawn, and discussed. There is a quiz that checks whether you can read a layout, an audit assignment, and a live critique hour. Section three is dripped so the cohort arrives together.",
        ], [
            ['Seeing the claim', 0, [
                ['An interface is a claim', 'article', 18, 1, 'What a screen is willing to say out loud.', self::lessonClaim()],
                ['Three quiet products', 'article', 16, 0, 'A close reading of products that refuse decoration.', self::lessonQuiet()],
                ['Reading an interface', 'quiz', 12, 0, 'A short check before you audit anything.', 'Answer from the readings, not from instinct.', [
                    'pass' => 70, 'minutes' => 10, 'attempts' => 3,
                    'questions' => [
                        ['A primary button that is visually quieter than a secondary link usually means what?', 'single', ['The hierarchy is accidental', 'The screen is arguing that the quiet action matters more', 'The brand color ran out', 'Users prefer low contrast'], '1'],
                        ['Which of these is a structural decision, not a stylistic one?', 'multi', ['Moving the only irreversible action out of the first viewport', 'Changing the accent from gold to blue', 'Requiring a confirmation that names the consequence', 'Swapping a geometric sans for another geometric sans'], ['0', '2']],
                        ['In one word, what should a critique name first?', 'text', [], ['decision']],
                    ],
                ]],
            ]],
            ['Structure before decoration', 0, [
                ['Grids that refuse decoration', 'article', 20, 0, 'Alignment as an argument about importance.', self::lessonGrids()],
                ['Type as wayfinding', 'article', 14, 0, 'Size, weight, and the path a tired eye takes.', self::lessonType()],
                ['Audit one screen', 'assignment', 40, 0, 'A written audit, not a redesign.', 'Pick one screen you use weekly. Do not redesign it yet.', [
                    'due' => 7,
                    'instructions' => "Write 400–700 words.\n\n1. State the claim the screen is making, in one sentence.\n2. Name three structural decisions that support or betray that claim.\n3. Propose one change that is not a color change.\n\nAttach a screenshot description if you cannot upload the image.",
                ]],
            ]],
            ['Live studio', 14, [
                ['Critique hour', 'live', 60, 0, 'A live room. Bring one screen and a sentence.', 'We look at one screen per person. The sentence has to name a decision.'],
                ['A checklist you can reuse', 'document', 10, 0, 'The studio checklist, written so you can run it alone.', self::lessonChecklist()],
            ]],
        ]);

        $critique = self::course($users['amara'], $cats['design'], [
            'title' => 'Critique Without Cruelty',
            'slug' => 'critique-without-cruelty',
            'subtitle' => 'A free briefing on feedback that changes the work.',
            'level' => 'beginner',
            'price_cents' => 0,
            'thumbnail' => '/assets/covers/critique.svg',
            'is_featured' => 0,
            'status' => 'published',
            'published_at' => $days(-20),
            'requirements' => "Willingness to be specific\nNo portfolio required",
            'outcomes' => "Separate taste from consequence\nGive a note the maker can act on\nReceive a hard note without collapsing the room",
            'description' => "A short, free course on the difference between a verdict and a note. Use it before you join a live critique, or before you run one.",
        ], [
            ['The note', 0, [
                ['Verdicts are cheap', 'article', 8, 1, 'Why “I do not like it” ends the conversation.', "A verdict spends the room's attention and returns nothing the maker can use. A note names the decision, the person it affects, and the consequence if it stays.\n\nPractice the shape: *When the form submits on Enter, a person paying an invoice can lose a line they have not reviewed.* That is a note. *This feels off* is a mood."],
                ['How to receive one', 'article', 8, 0, 'What to write down before you defend yourself.', "Write the note down before you answer it. If you cannot restate it, you are defending a feeling, not the work.\n\nAsk one question: is this about the claim, or about the maker's taste? Only the first kind changes the next version."],
                ['A three-line note', 'quiz', 6, 0, 'Check the shape of a useful note.', 'Pick the note a maker can act on.', [
                    'pass' => 70, 'minutes' => 5, 'attempts' => 5,
                    'questions' => [
                        ['Which note is actionable?', 'single', ['Make it pop', 'The pay button is below a newsletter checkbox, so the costly action is easier to miss than the cheap one', 'Love the vibe', 'Not sure, maybe try something else'], '1'],
                    ],
                ]],
            ]],
        ]);

        $php = self::course($users['jonah'], $cats['development'], [
            'title' => 'PHP for Systems, Not Scripts',
            'slug' => 'php-for-systems',
            'subtitle' => 'Boundaries, money in integers, and code you can operate.',
            'level' => 'intermediate',
            'price_cents' => 7900,
            'thumbnail' => '/assets/covers/php.svg',
            'is_featured' => 1,
            'enforce_sequence' => 1,
            'status' => 'published',
            'published_at' => $days(-28),
            'requirements' => "PHP 8.2 or newer on your machine\nYou have shipped at least one form that talked to a database",
            'outcomes' => "Draw the boundary between a request and a rule\nStore money as integers and say why\nTrace a checkout without reading the whole framework\nKnow what a backup is for before you need one",
            'description' => "This course is for people who have outgrown a pile of include files. We build the habits of a small system: a front controller, a service that owns a rule, queries that do not surprise you, and an operations story that includes backups.\n\nLessons are sequential. You cannot skip the money chapter to get to the interesting part. The money chapter is the interesting part.",
        ], [
            ['Boundaries', 0, [
                ['Scripts accumulate, systems decide', 'article', 16, 1, 'The moment a folder of files becomes a liability.', self::lessonScripts()],
                ['A request’s real life', 'article', 18, 0, 'From the URL to the rule and back to HTML.', self::lessonRequest()],
                ['Where does this belong?', 'quiz', 10, 0, 'Place the responsibility.', 'Sequential course — this gates the next section.', [
                    'pass' => 70, 'minutes' => 8, 'attempts' => 3,
                    'questions' => [
                        ['A commission split should live where?', 'single', ['In the template, next to the price', 'In a payment service, applied when the order is created', 'In a JavaScript file so the browser can help', 'In a comment'], '1'],
                        ['Which values belong in a settings table rather than a deploy?', 'multi', ['Platform commission percent', 'The database password', 'Whether course approval is required', 'SMTP host'], ['0', '2', '3']],
                    ],
                ]],
            ]],
            ['The modular core', 0, [
                ['Controllers stay thin', 'article', 14, 0, 'A controller may redirect. It should not invent policy.', "A controller authenticates, validates, and calls a service. If you can describe the method as “the rules for getting paid,” it does not belong in the controller.\n\nThin is not empty. Thin means the next person can find the rule by the name of the service, not by searching templates."],
                ['Money in integers', 'article', 12, 0, 'Cents, commissions, and the rounding you have to own.', self::lessonMoney()],
                ['Trace a checkout', 'assignment', 30, 0, 'Write the path of a dollar.', 'No code required. A trace is the work.', [
                    'due' => 5,
                    'instructions' => "Describe a checkout from “Pay” to “enrolled” in numbered steps. Mark which step charges the card, which step splits commission, and which step must be idempotent. 250 words is enough if they are the right words.",
                ]],
            ]],
            ['Operations', 0, [
                ['Backups are a feature', 'article', 12, 0, 'If you cannot restore it, you do not have it.', "A backup that has never been restored is a rumor. Schedule the copy, keep a retention count, and take one automatically before a schema update.\n\nPut the cron token in settings, not in the repository. The button in admin is for humans. The cron endpoint is for the machine that does not forget."],
                ['Office hours', 'live', 45, 0, 'Bring a boundary you cannot name.', 'We will draw one request on the board and decide what is allowed to know about money.'],
            ]],
        ]);

        $sql = self::course($users['priya'], $cats['data'], [
            'title' => 'SQL That Tells the Truth',
            'slug' => 'sql-that-tells-the-truth',
            'subtitle' => 'Filters, joins, and queries you can defend in a meeting.',
            'level' => 'beginner',
            'price_cents' => 4900,
            'thumbnail' => '/assets/covers/sql.svg',
            'is_featured' => 1,
            'status' => 'published',
            'published_at' => $days(-50),
            'requirements' => "A spreadsheet you no longer trust\nNo prior SQL required",
            'outcomes' => "Write a filter that matches the question asked\nJoin without double-counting\nExplain a number before you put it on a slide",
            'description' => "A practical course for people who have been asked for “the number” and felt the spreadsheet flinch. You will write queries that survive a follow-up question.",
        ], [
            ['Sentences', 0, [
                ['Tables are sentences', 'article', 14, 1, 'A row is a claim about one thing.', "A table is a pile of sentences with the same shape. `orders` says: this person paid this amount at this time through this gateway. If a column cannot finish that sentence, it belongs somewhere else.\n\nBefore you write SQL, write the sentence. Then the WHERE clause is obvious, and the argument in the meeting is shorter."],
                ['Filters before flourishes', 'article', 12, 0, 'The WHERE clause is the ethics.', "Most wrong dashboards are not wrong because of the chart. They are wrong because paid and refunded sat in the same sum.\n\nFilter to the status you mean. Name the window. Then decorate. A beautiful chart of the wrong set is a confident mistake."],
                ['Truthful filters', 'quiz', 10, 0, 'Catch the query that lies politely.', 'One attempt is enough if you read the sentence first.', [
                    'pass' => 80, 'minutes' => 8, 'attempts' => 4,
                    'questions' => [
                        ['You need revenue. Which filter belongs in the query?', 'single', ['All orders, because finance can sort it out', "status = 'paid'", 'The largest order, as a sample', 'Orders with a coupon, because those are real customers'], '1'],
                        ['A join from orders to order items doubled revenue. What happened?', 'single', ['SQLite cannot sum', 'The join multiplied rows before the aggregate', 'Currency conversion', 'The index was missing'], '1'],
                        ['What word should you be able to say before you GROUP BY?', 'text', [], ['sentence', 'question']],
                    ],
                ]],
            ]],
            ['The meeting', 0, [
                ['Show the denominator', 'article', 10, 0, 'A rate without a base is a costume.', "Completion rate is completed divided by enrolled, for a window you can name. If you drop the people who have not finished, the rate becomes a compliment.\n\nPut the denominator in the footnote. If the footnote embarrasses the chart, the chart was early."],
                ['Write the query you would defend', 'assignment', 25, 0, 'One question, one query, one caveat.', 'Bring a question from your own work if you have one.', [
                    'due' => 6,
                    'instructions' => "State a business question in one sentence. Write the SQL, or precise pseudocode, that answers it. Add one caveat: what the query refuses to claim.",
                ]],
            ]],
        ]);

        $dash = self::course($users['priya'], $cats['data'], [
            'title' => 'Dashboards That Don’t Lie',
            'slug' => 'dashboards-that-dont-lie',
            'subtitle' => 'Charts for operators, not for applause.',
            'level' => 'intermediate',
            'price_cents' => 6900,
            'thumbnail' => '/assets/covers/dashboards.svg',
            'is_featured' => 0,
            'status' => 'published',
            'published_at' => $days(-12),
            'requirements' => "SQL That Tells the Truth, or equivalent scar tissue",
            'outcomes' => "Pick five numbers and retire the rest\nAnnotate a change so next month’s reader is not guessing\nRefuse a vanity metric in writing",
            'description' => "A sequel to the SQL course. We design a one-screen operating view: revenue, enrollments, completion, and the thing that is on fire. Jonah Ellis joins as a collaborator on the instrumentation notes.",
        ], [
            ['The one screen', 0, [
                ['Five numbers', 'article', 12, 1, 'If everything is highlighted, nothing is.', "An operating dashboard answers: are we okay, and where do I look if we are not? Five numbers can do that. Thirty numbers are a brochure.\n\nChoose numbers a person can act on this week. Rating average is a weather report. Pending payouts are a task."],
                ['Annotation is part of the chart', 'article', 10, 0, 'The dip needs a sentence.', "When revenue drops, the chart should carry the reason you already know: a refund, a paused campaign, a course unpublished. Future you will not remember.\n\nLeave the sentence in the system, not in a chat log."],
                ['Vanity or signal?', 'quiz', 8, 0, 'Sort the metrics.', 'Be unkind to compliments.', [
                    'pass' => 70, 'minutes' => 6, 'attempts' => 3,
                    'questions' => [
                        ['Which metric is a signal for a course studio?', 'multi', ['Completion rate with the denominator', 'Homepage bounce rate with no segment', 'Instructor earnings available for payout', 'Social mentions of the logo'], ['0', '2']],
                    ],
                ]],
            ]],
        ]);

        $offers = self::course($users['helen'], $cats['business'], [
            'title' => 'Offers People Finish',
            'slug' => 'offers-people-finish',
            'subtitle' => 'Price the work so the right people can say yes.',
            'level' => 'intermediate',
            'price_cents' => 6400,
            'thumbnail' => '/assets/covers/offers.svg',
            'is_featured' => 1,
            'status' => 'published',
            'published_at' => $days(-18),
            'requirements' => "Something you already sell, or a course you intend to",
            'outcomes' => "Separate the offer from the audience\nWrite a price you can explain without apologizing\nUse a coupon without training people to wait",
            'description' => "Pricing is a design problem with a number on it. This course is about offers that match the work: who it is for, what is included, and what a discount is allowed to mean.",
        ], [
            ['The offer', 0, [
                ['Who it is not for', 'article', 12, 1, 'Exclusion is a kindness.', "An offer that tries to serve everyone becomes a brochure. Write the person who should not buy it, and the page gets quieter.\n\nMeridian courses do this in the requirements list. Yours can do it in a sentence under the price."],
                ['Coupons are a dialect', 'article', 10, 0, 'A code teaches people how to wait.', "A standing 20% code teaches the list to hesitate. A code with an end date and a reason — a cohort, a repair, a first course — teaches them the price is real.\n\nIf you cannot say the reason, do not mint the code."],
                ['Price the cohort', 'quiz', 8, 0, 'A small check on offer design.', 'Short on purpose.', [
                    'pass' => 70, 'minutes' => 5, 'attempts' => 3,
                    'questions' => [
                        ['A coupon with no expiry and no reason mostly does what?', 'single', ['Rewards loyalty', 'Trains buyers to wait', 'Improves completion', 'Lowers support load'], '1'],
                    ],
                ]],
            ]],
        ]);

        $light = self::course($users['leo'], $cats['photography'], [
            'title' => 'Available Light',
            'slug' => 'available-light',
            'subtitle' => 'See the light in the room before you add any.',
            'level' => 'beginner',
            'price_cents' => 5900,
            'thumbnail' => '/assets/covers/light.svg',
            'is_featured' => 0,
            'status' => 'published',
            'published_at' => $days(-15),
            'requirements' => "Any camera, including the one in your pocket\nA window",
            'outcomes' => "Name the direction of the light\nPlace a person without a reflector\nEdit less because you saw more",
            'description' => "A field course. The third section drips a week after enrollment so you actually go outside before the live walk.",
        ], [
            ['See it', 0, [
                ['Direction first', 'article', 12, 1, 'Where the light comes from decides the picture.', "Before you raise the camera, point at the light. Window, doorway, overcast sky, a lamp you did not plan. Direction is the whole course in one gesture.\n\nIf you cannot point, you are decorating. Put the camera down and walk the room."],
                ['One window, three positions', 'article', 14, 0, 'A drill you can do before dinner.', "Stand the subject facing the window, beside it, and with the window behind them. Make one frame of each. Do not fix exposure in your head — look at the face.\n\nThe third position is not a mistake. It is a choice you should make on purpose, with the face still readable or deliberately not."],
            ]],
            ['The walk', 7, [
                ['Live: a window walk', 'live', 50, 0, 'We shoot the same window and compare frames.', 'Bring three frames from the drill. We will talk about direction, not gear.'],
                ['What to delete', 'article', 8, 0, 'Editing begins as refusal.', "Delete the frame where you cannot say where the light was. Keep the one where the face is a decision. You will have fewer pictures and a clearer contact sheet."],
            ]],
        ]);

        $essay = self::course($users['idris'], $cats['writing'], [
            'title' => 'The Essay Workshop',
            'slug' => 'the-essay-workshop',
            'subtitle' => 'Sentences that can travel without a slide behind them.',
            'level' => 'all',
            'price_cents' => 3900,
            'thumbnail' => '/assets/covers/essay.svg',
            'is_featured' => 0,
            'status' => 'published',
            'published_at' => $days(-9),
            'requirements' => "A paragraph you are willing to cut\nNo publication history required",
            'outcomes' => "Find the sentence the essay is hiding behind\nCut a paragraph that was only atmosphere\nSubmit a page someone can argue with",
            'description' => "A workshop in public. You will write less than you expect and mean more of it. The quiz is deliberately fussy. That is the point.",
        ], [
            ['The sentence', 0, [
                ['Find the one that works', 'article', 11, 1, 'Most drafts bury the sentence they came to say.', "Read your draft and mark the sentence you would keep if the rest had to go. If you cannot find it, you have notes, not an essay.\n\nMove that sentence up. See what becomes unnecessary once it is allowed to lead."],
                ['Cut the weather', 'article', 9, 0, 'Atmosphere is not an argument.', "A paragraph about the light in the room is often the writer waiting to be brave. Keep one concrete detail. Cut the rest until a reader can disagree with you.\n\nDisagreement is a sign the essay arrived."],
                ['Which sentence leads?', 'quiz', 8, 0, 'Pick the sentence that can lead.', 'Samir is still in conversation with this one.', [
                    'pass' => 75, 'minutes' => 7, 'attempts' => 3,
                    'questions' => [
                        ['Which sentence can lead an essay?', 'single', ['The afternoon was golden and full of possibility', 'A payout request is a moral document: it says the work was finished enough to be paid', 'In today’s world, content is king', 'I have always loved learning'], '1'],
                        ['A paragraph that only sets a mood should usually be…', 'text', [], ['cut']],
                    ],
                ]],
            ]],
            ['The page', 0, [
                ['Submit a page', 'assignment', 35, 0, 'One page. A claim. No preface.', 'Idris grades these in the studio.', [
                    'due' => 10,
                    'instructions' => "Submit one page. The first sentence must be a claim a stranger could argue with. No introduction about why you are writing.",
                ]],
            ]],
        ]);

        self::course($users['jonah'], $cats['development'], [
            'title' => 'Quiet Queues',
            'slug' => 'quiet-queues',
            'subtitle' => 'Background work that does not page you at midnight.',
            'level' => 'advanced',
            'price_cents' => 7200,
            'thumbnail' => '/assets/covers/queues.svg',
            'status' => 'pending',
            'requirements' => "PHP for Systems, or a system you already operate",
            'outcomes' => "Name a job that must be idempotent\nDecide what is allowed to retry",
            'description' => "Submitted for review. A course on queues, retries, and the emails you should not send twice.",
        ], [
            ['Draft section', 0, [
                ['Retry is a product decision', 'article', 12, 0, 'Not every failure deserves another attempt.', "A payment capture should not be retried blindly. A thumbnail resize can. The course, once approved, will separate those with examples from a learning platform’s own checkout."],
            ]],
        ]);

        Database::insert('course_instructors', [
            'course_id' => $dash,
            'user_id' => $users['jonah'],
            'role' => 'collaborator',
            'created_at' => $now,
        ]);

        self::settings($now);
        self::pages($now);
        self::commerce($users, [
            'interface' => $interface,
            'php' => $php,
            'sql' => $sql,
            'dash' => $dash,
            'light' => $light,
            'essay' => $essay,
            'critique' => $critique,
            'offers' => $offers,
        ], $days);
        self::community($users, $interface, $sql, $php, $light, $days);
        self::progress($users, [
            'interface' => $interface,
            'php' => $php,
            'sql' => $sql,
            'dash' => $dash,
            'light' => $light,
            'essay' => $essay,
            'critique' => $critique,
        ], $days);

        Database::insert('contact_messages', [
            'name' => 'Owen Blake',
            'email' => 'owen@example.com',
            'subject' => 'Invoice for a team seat',
            'body' => 'We are five analysts. Is there a way to pay once and enroll the group in SQL That Tells the Truth?',
            'created_at' => $days(-1, '09:12:00'),
        ]);
        Database::insert('subscribers', ['email' => 'owen@example.com', 'name' => 'Owen Blake', 'status' => 'active', 'created_at' => $days(-1, '09:12:00')]);
        Database::insert('subscribers', ['email' => 'nora@meridian.test', 'name' => 'Nora Adelayo', 'status' => 'active', 'created_at' => $days(-6)]);
        Database::insert('audit_logs', [
            'user_id' => $users['mira'],
            'action' => 'course.approve',
            'subject' => 'available-light',
            'meta' => json_encode(['note' => 'Seeded studio history'], JSON_UNESCAPED_UNICODE),
            'created_at' => $days(-15, '16:00:00'),
        ]);
    }

    private static function user(string $name, string $email, string $hash, string $role, string $headline, string $bio, string $now): int
    {
        return Database::insert('users', [
            'name' => $name,
            'email' => $email,
            'password' => $hash,
            'role' => $role,
            'headline' => $headline,
            'bio' => $bio,
            'status' => 'active',
            'payout_method' => $role === 'instructor' ? 'paypal' : null,
            'payout_details' => $role === 'instructor' ? $email : null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private static function category(string $name, string $slug, string $description, string $accent, int $position): int
    {
        return Database::insert('categories', compact('name', 'slug', 'description', 'accent', 'position'));
    }

    private static function course(int $instructorId, int $categoryId, array $meta, array $sections): int
    {
        $now = gmdate('Y-m-d H:i:s');
        $minutes = 0;
        foreach ($sections as $section) {
            foreach ($section[2] as $lesson) {
                $minutes += (int) $lesson[2];
            }
        }
        $id = Database::insert('courses', [
            'instructor_id' => $instructorId,
            'category_id' => $categoryId,
            'title' => $meta['title'],
            'slug' => $meta['slug'],
            'subtitle' => $meta['subtitle'],
            'description' => $meta['description'],
            'level' => $meta['level'],
            'language' => 'English',
            'price_cents' => $meta['price_cents'],
            'compare_cents' => $meta['compare_cents'] ?? null,
            'thumbnail' => $meta['thumbnail'],
            'status' => $meta['status'],
            'is_featured' => $meta['is_featured'] ?? 0,
            'enforce_sequence' => $meta['enforce_sequence'] ?? 0,
            'duration_minutes' => $minutes,
            'requirements' => $meta['requirements'],
            'outcomes' => $meta['outcomes'],
            'seo_title' => $meta['seo_title'] ?? $meta['title'],
            'seo_description' => $meta['seo_description'] ?? $meta['subtitle'],
            'published_at' => $meta['published_at'] ?? null,
            'created_at' => $meta['published_at'] ?? $now,
            'updated_at' => $now,
        ]);
        foreach ($sections as $sIndex => $section) {
            $sectionId = Database::insert('sections', [
                'course_id' => $id,
                'title' => $section[0],
                'position' => $sIndex + 1,
                'drip_days' => $section[1],
            ]);
            foreach ($section[2] as $lIndex => $lesson) {
                $lessonId = Database::insert('lessons', [
                    'course_id' => $id,
                    'section_id' => $sectionId,
                    'title' => $lesson[0],
                    'type' => $lesson[1],
                    'duration_minutes' => $lesson[2],
                    'is_preview' => $lesson[3],
                    'summary' => $lesson[4],
                    'content' => $lesson[5],
                    'position' => $lIndex + 1,
                ]);
                if ($lesson[1] === 'quiz') {
                    $quizMeta = $lesson[6];
                    $quizId = Database::insert('quizzes', [
                        'lesson_id' => $lessonId,
                        'course_id' => $id,
                        'title' => $lesson[0],
                        'description' => $lesson[5],
                        'pass_percent' => $quizMeta['pass'],
                        'time_limit_minutes' => $quizMeta['minutes'],
                        'attempts_allowed' => $quizMeta['attempts'],
                    ]);
                    foreach ($quizMeta['questions'] as $qIndex => $question) {
                        $options = [];
                        foreach ($question[2] as $i => $text) {
                            $options[] = ['id' => (string) $i, 'text' => $text];
                        }
                        $correct = $question[3];
                        if (!is_array($correct)) {
                            $correct = [$correct];
                        }
                        Database::insert('quiz_questions', [
                            'quiz_id' => $quizId,
                            'question' => $question[0],
                            'type' => $question[1],
                            'options_json' => json_encode($options, JSON_UNESCAPED_UNICODE),
                            'correct_json' => json_encode(array_map('strval', $correct), JSON_UNESCAPED_UNICODE),
                            'points' => 1,
                            'position' => $qIndex + 1,
                        ]);
                    }
                }
                if ($lesson[1] === 'assignment') {
                    $metaA = $lesson[6];
                    Database::insert('assignments', [
                        'lesson_id' => $lessonId,
                        'course_id' => $id,
                        'title' => $lesson[0],
                        'instructions' => $metaA['instructions'],
                        'due_days' => $metaA['due'],
                        'max_score' => 100,
                    ]);
                }
            }
        }
        return $id;
    }

    private static function permissions(): void
    {
        $roles = [
            ['student', 'Student', 1],
            ['instructor', 'Instructor', 1],
            ['admin', 'Administrator', 1],
        ];
        foreach ($roles as [$slug, $name, $system]) {
            Database::insert('roles', ['slug' => $slug, 'name' => $name, 'is_system' => $system]);
        }
        $permissions = [
            ['admin.access', 'Enter the admin studio', 'Admin'],
            ['users.manage', 'Manage users', 'Admin'],
            ['applications.review', 'Review instructor applications', 'Admin'],
            ['courses.review', 'Approve courses', 'Admin'],
            ['courses.manage', 'Edit any course', 'Admin'],
            ['categories.manage', 'Manage categories', 'Admin'],
            ['orders.manage', 'Manage orders', 'Admin'],
            ['payouts.manage', 'Manage payouts', 'Admin'],
            ['content.manage', 'Manage pages and appearance', 'Admin'],
            ['newsletter.send', 'Send newsletters', 'Admin'],
            ['media.manage', 'Manage the media library', 'Admin'],
            ['settings.manage', 'Change system settings', 'Admin'],
            ['roles.manage', 'Edit role permissions', 'Admin'],
            ['backups.run', 'Run backups', 'Admin'],
            ['updates.apply', 'Apply system updates', 'Admin'],
            ['analytics.view', 'View platform analytics', 'Admin'],
            ['studio.access', 'Enter the instructor studio', 'Studio'],
            ['courses.create', 'Create and edit own courses', 'Studio'],
            ['earnings.view', 'View earnings and request payouts', 'Studio'],
            ['live.manage', 'Schedule live classes', 'Studio'],
        ];
        $ids = [];
        foreach ($permissions as [$slug, $name, $group]) {
            $ids[$slug] = Database::insert('permissions', [
                'slug' => $slug,
                'name' => $name,
                'group_name' => $group,
            ]);
        }
        $map = [
            'admin' => array_keys($ids),
            'instructor' => ['studio.access', 'courses.create', 'earnings.view', 'live.manage', 'analytics.view'],
            'student' => [],
        ];
        foreach ($map as $role => $slugs) {
            foreach ($slugs as $slug) {
                Database::insert('role_permissions', [
                    'role' => $role,
                    'permission_id' => $ids[$slug],
                ]);
            }
        }
    }

    private static function settings(string $now): void
    {
        $pairs = [
            'site_name' => 'Meridian',
            'tagline' => 'A studio for serious learning.',
            'announcement' => 'Cohort notes are open. Live critique is on the calendar.',
            'hero_kicker' => 'Studio · est. as a classroom, run as a workshop',
            'hero_title' => 'Learn from people who still do the work.',
            'hero_lede' => 'Courses with a point of view, live rooms on Zoom, and certificates you can verify. No slideshows pretending to be teaching.',
            'footer_blurb' => 'Meridian is a learning studio for design, code, data, pictures, and sentences. Instructors keep a point of view. Students keep the work.',
            'primary_color' => '#1f4f45',
            'accent_color' => '#b5812d',
            'currency' => 'USD',
            'platform_commission_percent' => '20',
            'course_management_mode' => 'collaborative',
            'require_course_approval' => '1',
            'seo_title' => 'Meridian — a studio for serious learning',
            'seo_description' => 'Browse courses, join live classes, and study with instructors who still practice.',
            'support_email' => 'studio@meridian.test',
            'storage_disk' => 'local',
            'max_upload_mb' => '20',
            'pay_stripe_enabled' => '1',
            'pay_paypal_enabled' => '1',
            'pay_mollie_enabled' => '1',
            'pay_paystack_enabled' => '1',
            'paypal_mode' => 'sandbox',
            'smtp_port' => '587',
            'smtp_encryption' => 'tls',
            'smtp_from' => 'studio@meridian.test',
            'smtp_from_name' => 'Meridian Studio',
            'backup_interval_hours' => '24',
            'backup_retention' => '8',
            'cron_token' => 'meridian-cron-demo',
            'db_version' => '1.0.0',
            'app_url' => '',
            'nav_links' => json_encode([
                ['label' => 'Courses', 'href' => '/courses'],
                ['label' => 'Live', 'href' => '/live'],
                ['label' => 'Teach', 'href' => '/teach'],
            ], JSON_UNESCAPED_SLASHES),
        ];
        foreach ($pairs as $key => $value) {
            Database::insert('settings', ['key' => $key, 'value' => $value]);
        }
        Database::insert('system_updates', [
            'version' => '1.0.0',
            'notes' => 'Initial studio install: roles, courses, payments, live rooms, and backups.',
            'applied_at' => $now,
            'applied_by' => null,
        ]);
        Database::insert('coupons', [
            'code' => 'WELCOME10',
            'type' => 'percent',
            'value' => 10,
            'active' => 1,
            'uses' => 1,
        ]);
        Database::insert('coupons', [
            'code' => 'STUDIO20',
            'type' => 'percent',
            'value' => 20,
            'active' => 1,
            'max_uses' => 50,
            'uses' => 0,
            'expires_at' => gmdate('Y-m-d H:i:s', time() + 40 * 86400),
        ]);
    }

    private static function pages(string $now): void
    {
        $pages = [
            ['About the studio', 'about', 1, 1, "Meridian is a small learning studio. Instructors are practitioners. Courses are sequenced on purpose. Live rooms are part of the teaching, not a webinar bolted on at the end.\n\n## How a course gets here\n\nAn instructor applies. An administrator reads the application. A course is drafted in the curriculum builder, submitted, and published only after review — unless the studio is set to publish without a queue.\n\n## What you keep\n\nYour progress, your notes (after the 1.1 update), your certificate code, and the right to verify that code in public."],
            ['Privacy', 'privacy', 1, 0, "We store the account you create, the courses you enroll in, quiz attempts, assignment text, and messages you send inside the studio.\n\nPayment card numbers are not stored. A sandbox checkout keeps only a last-four, and only if you type one.\n\nGoogle sign-in, when configured, receives the email and name Google shares. Zoom, when configured, receives the meeting topic and time.\n\n## Mail\n\nIf SMTP is empty, newsletter copies are written to the studio mail log instead of leaving the server."],
            ['Terms', 'terms', 1, 0, "Enrolling gives you access to the course materials for as long as the studio runs and your account is in good standing. A refund, issued by an administrator, withdraws that access.\n\nCertificates certify completion of the lessons on this platform. They are not an accredited degree.\n\nDo not upload work you do not have the right to share. Critique stays on the work."],
            ['Studio conduct', 'conduct', 0, 0, "Critique names a decision. It does not name a person as the problem.\n\nLive rooms start on time. If you cannot attend, the notes stay on the course forum.\n\nInstructors answer messages about the work. The studio does not promise a reply to anything else within a day, but it does promise the message will be readable."],
        ];
        foreach ($pages as $i => [$title, $slug, $footer, $nav, $content]) {
            Database::insert('pages', [
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'seo_title' => $title . ' · Meridian',
                'seo_description' => $title . ' for the Meridian learning studio.',
                'status' => 'published',
                'show_in_footer' => $footer,
                'show_in_nav' => $nav,
                'position' => $i + 1,
                'updated_at' => $now,
            ]);
        }
    }

    private static function commerce(array $users, array $courses, callable $days): void
    {
        $make = function (int $userId, string $number, string $gateway, string $when, array $lines, ?string $coupon = null, int $discount = 0) use ($days): int {
            $subtotal = 0;
            foreach ($lines as $line) {
                $subtotal += $line['price'];
            }
            $total = $subtotal - $discount;
            $orderId = Database::insert('orders', [
                'user_id' => $userId,
                'number' => $number,
                'subtotal_cents' => $subtotal,
                'discount_cents' => $discount,
                'total_cents' => $total,
                'currency' => 'USD',
                'gateway' => $gateway,
                'gateway_ref' => strtoupper($gateway) . '-' . $number,
                'coupon_code' => $coupon,
                'status' => 'paid',
                'created_at' => $when,
                'paid_at' => $when,
            ]);
            foreach ($lines as $line) {
                $net = $line['net'];
                $fee = (int) round($net * 0.20);
                Database::insert('order_items', [
                    'order_id' => $orderId,
                    'course_id' => $line['course'],
                    'price_cents' => $net,
                    'commission_rate' => 20,
                    'platform_fee_cents' => $fee,
                    'instructor_earning_cents' => $net - $fee,
                    'instructor_id' => $line['instructor'],
                ]);
            }
            Database::insert('transactions', [
                'order_id' => $orderId,
                'gateway' => $gateway,
                'gateway_ref' => strtoupper($gateway) . '-' . $number,
                'amount_cents' => $total,
                'status' => 'paid',
                'last4' => $line['last4'] ?? ($gateway === 'stripe' ? '4242' : null),
                'payload_json' => json_encode(['source' => 'seed'], JSON_UNESCAPED_UNICODE),
                'created_at' => $when,
            ]);
            return $orderId;
        };

        $o1 = $make($users['nora'], 'MRD-2401-NORA1', 'stripe', $days(-12, '14:10:00'), [[
            'course' => $courses['interface'], 'price' => 8900, 'net' => 8010, 'instructor' => $users['amara'], 'last4' => '4242',
        ]], 'WELCOME10', 890);
        $o2 = $make($users['nora'], 'MRD-2402-NORA2', 'paypal', $days(-8, '18:02:00'), [[
            'course' => $courses['php'], 'price' => 7900, 'net' => 7900, 'instructor' => $users['jonah'],
        ]]);
        $o3 = $make($users['chris'], 'MRD-2308-CHRIS', 'paystack', $days(-30, '10:24:00'), [[
            'course' => $courses['sql'], 'price' => 4900, 'net' => 4900, 'instructor' => $users['priya'],
        ]]);
        $o4 = $make($users['chris'], 'MRD-2403-CHRIS', 'stripe', $days(-6, '11:40:00'), [[
            'course' => $courses['dash'], 'price' => 6900, 'net' => 6900, 'instructor' => $users['priya'], 'last4' => '4242',
        ]]);
        $o5 = $make($users['mina'], 'MRD-2404-MINA', 'mollie', $days(-2, '16:18:00'), [[
            'course' => $courses['light'], 'price' => 5900, 'net' => 5900, 'instructor' => $users['leo'],
        ]]);
        $o6 = $make($users['samir'], 'MRD-2405-SAMIR', 'paypal', $days(-6, '08:55:00'), [[
            'course' => $courses['essay'], 'price' => 3900, 'net' => 3900, 'instructor' => $users['idris'],
        ]]);

        $enroll = function (int $user, int $course, ?int $order, string $when, int $progress, ?string $done = null, ?string $code = null) {
            Database::insert('enrollments', [
                'user_id' => $user,
                'course_id' => $course,
                'order_id' => $order,
                'progress_percent' => $progress,
                'completed_at' => $done,
                'certificate_code' => $code,
                'enrolled_at' => $when,
            ]);
        };
        $enroll($users['nora'], $courses['interface'], $o1, $days(-12, '14:11:00'), 50);
        $enroll($users['nora'], $courses['php'], $o2, $days(-8, '18:03:00'), 25);
        $enroll($users['nora'], $courses['critique'], null, $days(-5, '09:00:00'), 100, $days(-4, '12:00:00'), 'MRD-9K2M-CRIT');
        $enroll($users['chris'], $courses['sql'], $o3, $days(-30, '10:25:00'), 100, $days(-18, '17:40:00'), 'MRD-4N7Q-SQL2');
        $enroll($users['chris'], $courses['dash'], $o4, $days(-6, '11:41:00'), 33);
        $enroll($users['mina'], $courses['light'], $o5, $days(-2, '16:19:00'), 50);
        $enroll($users['samir'], $courses['essay'], $o6, $days(-6, '08:56:00'), 50);
        $enroll($users['amara'], $courses['php'], null, $days(-7, '13:00:00'), 25);

        foreach ([$courses['interface'] => 2, $courses['php'] => 2, $courses['sql'] => 1, $courses['dash'] => 1, $courses['light'] => 1, $courses['essay'] => 1, $courses['critique'] => 1] as $courseId => $count) {
            Database::update('courses', ['students_count' => $count], 'id = ?', [$courseId]);
        }

        Database::insert('payouts', [
            'instructor_id' => $users['amara'],
            'amount_cents' => 5000,
            'status' => 'paid',
            'method' => 'paypal',
            'details' => 'amara@meridian.test',
            'note' => 'March studio payout',
            'requested_at' => $days(-20, '09:00:00'),
            'processed_at' => $days(-18, '15:00:00'),
            'processed_by' => $users['mira'],
        ]);
        Database::insert('payouts', [
            'instructor_id' => $users['jonah'],
            'amount_cents' => 4000,
            'status' => 'requested',
            'method' => 'paypal',
            'details' => 'jonah@meridian.test',
            'note' => 'First payout request',
            'requested_at' => $days(-1, '19:12:00'),
        ]);

        Database::insert('wishlists', ['user_id' => $users['nora'], 'course_id' => $courses['sql'], 'created_at' => $days(-3)]);
        Database::insert('wishlists', ['user_id' => $users['mina'], 'course_id' => $courses['offers'], 'created_at' => $days(-1)]);
        Database::insert('cart_items', ['user_id' => $users['mina'], 'course_id' => $courses['dash'], 'created_at' => $days(-1, '20:00:00')]);

        Database::insert('reviews', [
            'user_id' => $users['nora'],
            'course_id' => $courses['interface'],
            'rating' => 5,
            'comment' => 'The audit assignment is the course. The readings stop you from hiding in the font menu.',
            'created_at' => $days(-3, '21:00:00'),
        ]);
        Database::insert('reviews', [
            'user_id' => $users['chris'],
            'course_id' => $courses['sql'],
            'rating' => 5,
            'comment' => 'I took a number back to a meeting and could say the sentence out loud. That has not happened before.',
            'created_at' => $days(-16, '12:00:00'),
        ]);
        Database::insert('reviews', [
            'user_id' => $users['mina'],
            'course_id' => $courses['light'],
            'rating' => 4,
            'comment' => 'The window drill is annoyingly effective. I deleted half my roll.',
            'created_at' => $days(-1, '08:20:00'),
        ]);
        Database::insert('reviews', [
            'user_id' => $users['samir'],
            'course_id' => $courses['essay'],
            'rating' => 4,
            'comment' => 'The quiz is fussy in the way a good editor is fussy. I have not passed it yet.',
            'created_at' => $days(-2, '22:10:00'),
        ]);
        Database::update('courses', ['rating_avg' => 5, 'rating_count' => 1], 'id = ?', [$courses['interface']]);
        Database::update('courses', ['rating_avg' => 5, 'rating_count' => 1], 'id = ?', [$courses['sql']]);
        Database::update('courses', ['rating_avg' => 4, 'rating_count' => 1], 'id = ?', [$courses['light']]);
        Database::update('courses', ['rating_avg' => 4, 'rating_count' => 1], 'id = ?', [$courses['essay']]);
    }

    private static function community(array $users, int $interface, int $sql, int $php, int $light, callable $days): void
    {
        $liveUpcoming = Database::insert('live_classes', [
            'course_id' => $interface,
            'instructor_id' => $users['amara'],
            'title' => 'Critique hour',
            'description' => 'Bring one screen and a sentence that names a decision. We will not talk about fonts until the claim is clear.',
            'starts_at' => $days(4, '16:00:00'),
            'duration_minutes' => 60,
            'status' => 'scheduled',
            'created_at' => $days(-2),
        ]);
        $livePast = Database::insert('live_classes', [
            'course_id' => $php,
            'instructor_id' => $users['jonah'],
            'title' => 'Office hours: boundaries',
            'description' => 'We drew a checkout on the board and decided the template is not allowed to know the commission rate.',
            'starts_at' => $days(-10, '17:00:00'),
            'duration_minutes' => 45,
            'status' => 'completed',
            'zoom_join_url' => '',
            'created_at' => $days(-12),
        ]);
        $liveWalk = Database::insert('live_classes', [
            'course_id' => $light,
            'instructor_id' => $users['leo'],
            'title' => 'Window walk',
            'description' => 'Three frames, one window, no new gear. Drip opens this section a week after you enroll.',
            'starts_at' => $days(6, '15:00:00'),
            'duration_minutes' => 50,
            'status' => 'scheduled',
            'created_at' => $days(-1),
        ]);
        Database::execute('UPDATE lessons SET live_class_id = ? WHERE course_id = ? AND title = ?', [$liveUpcoming, $interface, 'Critique hour']);
        Database::execute('UPDATE lessons SET live_class_id = ? WHERE course_id = ? AND title = ?', [$livePast, $php, 'Office hours']);
        Database::execute('UPDATE lessons SET live_class_id = ? WHERE course_id = ? AND title = ?', [$liveWalk, $light, 'Live: a window walk']);

        $thread = Database::insert('threads', [
            'course_id' => $interface,
            'user_id' => $users['nora'],
            'title' => 'Is a confirmation dialog a structural decision?',
            'body' => 'I keep wanting to call it polish. The reading says if it names a consequence, it is structure. My audit is stuck on a delete button.',
            'pinned' => 1,
            'created_at' => $days(-2, '19:40:00'),
            'updated_at' => $days(-2, '20:05:00'),
        ]);
        Database::insert('posts', [
            'thread_id' => $thread,
            'user_id' => $users['amara'],
            'body' => 'If removing it would let someone destroy work without noticing, it is structure. Write that sentence in the audit and stop apologizing for it.',
            'created_at' => $days(-2, '20:05:00'),
        ]);
        $thread2 = Database::insert('threads', [
            'course_id' => $sql,
            'user_id' => $users['chris'],
            'title' => 'Where I put refunds',
            'body' => 'I excluded refunded orders from revenue and put the count in the footnote. Priya, is the footnote enough or should refunds be their own number?',
            'pinned' => 0,
            'created_at' => $days(-14, '11:15:00'),
            'updated_at' => $days(-14, '13:02:00'),
        ]);
        Database::insert('posts', [
            'thread_id' => $thread2,
            'user_id' => $users['priya'],
            'body' => 'Footnote if the meeting is about revenue. Own number if someone in the room can issue the refund. You had both kinds of meeting. Say which one you were in.',
            'created_at' => $days(-14, '13:02:00'),
        ]);

        Database::insert('messages', [
            'sender_id' => $users['nora'],
            'recipient_id' => $users['amara'],
            'course_id' => $interface,
            'subject' => 'Audit scope',
            'body' => 'May the screen be a settings page, or do you want something with a price on it?',
            'read_at' => $days(-1, '10:00:00'),
            'created_at' => $days(-1, '09:12:00'),
        ]);
        Database::insert('messages', [
            'sender_id' => $users['amara'],
            'recipient_id' => $users['nora'],
            'course_id' => $interface,
            'subject' => 'Re: Audit scope',
            'body' => 'Settings is a fine screen if the claim is about control. Do not pick it because it is easier to screenshot.',
            'read_at' => null,
            'created_at' => $days(-1, '10:02:00'),
        ]);
        Database::insert('messages', [
            'sender_id' => $users['jonah'],
            'recipient_id' => $users['nora'],
            'course_id' => $php,
            'subject' => 'Sequence',
            'body' => 'The money lesson will not open until the quiz is passed. That is the course working, not the player breaking.',
            'read_at' => null,
            'created_at' => $days(-1, '15:30:00'),
        ]);

        $notes = [
            [$users['nora'], 'message', 'Amara replied', 'Settings is a fine screen if the claim is about control.', '/messages/with/' . $users['amara'], null],
            [$users['nora'], 'live', 'Critique hour is on the calendar', 'Thursday’s live room is open to enrolled students.', '/live/room/' . $liveUpcoming, null],
            [$users['amara'], 'sale', 'New enrollment', 'Nora enrolled in Interface as Argument.', '/studio/students', $days(-12, '14:12:00')],
            [$users['mira'], 'application', 'Instructor application', 'Lina Berg applied to teach.', '/admin/applications', null],
            [$users['mira'], 'course', 'Course waiting for review', 'Quiet Queues was submitted by Jonah Ellis.', '/admin/courses', null],
            [$users['jonah'], 'payout', 'Payout requested', 'Your request is in the studio queue.', '/studio/earnings', null],
        ];
        foreach ($notes as [$user, $type, $title, $body, $link, $read]) {
            Database::insert('notifications', [
                'user_id' => $user,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'link' => $link,
                'read_at' => $read,
                'created_at' => $days(-1, '12:00:00'),
            ]);
        }
    }

    private static function progress(array $users, array $courses, callable $days): void
    {
        $completeThrough = function (int $user, int $course, int $count, string $when) use ($days): void {
            $lessons = Database::select('SELECT id FROM lessons WHERE course_id = ? ORDER BY position, id', [$course]);
            // position is per section; order by section position then lesson position
            $lessons = Database::select(
                'SELECT l.id, l.title FROM lessons l INNER JOIN sections s ON s.id = l.section_id WHERE l.course_id = ? ORDER BY s.position, l.position, l.id',
                [$course]
            );
            foreach ($lessons as $i => $lesson) {
                $done = $i < $count;
                Database::insert('lesson_progress', [
                    'user_id' => $user,
                    'lesson_id' => $lesson['id'],
                    'course_id' => $course,
                    'completed' => $done ? 1 : 0,
                    'watched_seconds' => $done ? 600 : 120,
                    'last_watched_at' => $days(-1, sprintf('%02d:10:00', 8 + $i)),
                    'completed_at' => $done ? $when : null,
                ]);
            }
            if ($lessons) {
                Database::update('enrollments', ['last_lesson_id' => (int) $lessons[min($count, count($lessons) - 1)]['id']], 'user_id = ? AND course_id = ?', [$user, $course]);
            }
        };

        $completeThrough($users['nora'], $courses['interface'], 4, $days(-3, '18:00:00'));
        $completeThrough($users['nora'], $courses['php'], 2, $days(-2, '18:00:00'));
        $completeThrough($users['nora'], $courses['critique'], 99, $days(-4, '12:00:00'));
        $completeThrough($users['chris'], $courses['sql'], 99, $days(-18, '17:00:00'));
        $completeThrough($users['chris'], $courses['dash'], 1, $days(-2, '11:00:00'));
        $completeThrough($users['mina'], $courses['light'], 2, $days(-1, '09:00:00'));
        $completeThrough($users['samir'], $courses['essay'], 2, $days(-2, '21:00:00'));
        $completeThrough($users['amara'], $courses['php'], 2, $days(-3, '13:00:00'));

        $sqlQuiz = Database::first('SELECT id, pass_percent FROM quizzes WHERE course_id = ?', [$courses['sql']]);
        if ($sqlQuiz) {
            Database::insert('quiz_attempts', [
                'quiz_id' => $sqlQuiz['id'],
                'user_id' => $users['chris'],
                'score_percent' => 100,
                'points_earned' => 3,
                'points_possible' => 3,
                'passed' => 1,
                'answers_json' => '{}',
                'started_at' => $days(-19, '16:00:00'),
                'submitted_at' => $days(-19, '16:08:00'),
            ]);
        }
        $essayQuiz = Database::first('SELECT id FROM quizzes WHERE course_id = ?', [$courses['essay']]);
        if ($essayQuiz) {
            Database::insert('quiz_attempts', [
                'quiz_id' => $essayQuiz['id'],
                'user_id' => $users['samir'],
                'score_percent' => 50,
                'points_earned' => 1,
                'points_possible' => 2,
                'passed' => 0,
                'answers_json' => '{}',
                'started_at' => $days(-2, '20:40:00'),
                'submitted_at' => $days(-2, '20:46:00'),
            ]);
        }
        $assignment = Database::first('SELECT id FROM assignments WHERE course_id = ?', [$courses['interface']]);
        if ($assignment) {
            Database::insert('assignment_submissions', [
                'assignment_id' => $assignment['id'],
                'user_id' => $users['nora'],
                'content' => "The settings screen claims that control is available, but the irreversible action — delete account — sits in the same list as “change theme.”\n\nThree decisions: the list treats danger and preference as siblings; there is no sentence naming the consequence; the confirm dialog, if I add one, would be structure rather than polish.\n\nChange: move delete into its own section and require the person to type the studio name.",
                'status' => 'graded',
                'score' => 88,
                'feedback' => 'The claim is clear and the change is structural. Cut the apology in paragraph two — there is not one, good. Next version: say who is harmed if the list stays mixed.',
                'submitted_at' => $days(-3, '17:10:00'),
                'graded_at' => $days(-2, '11:30:00'),
            ]);
        }
    }

    private static function lessonClaim(): string
    {
        return <<<'MD'
An interface is a claim about what matters. The claim can be timid, but it cannot be absent. A screen that tries not to argue still argues — usually that everything is equal, which is a way of saying the designer refused to choose.

## What to look for

- The action that is easiest to take
- The action that is hardest to undo
- The person who is inconvenienced so someone else can move faster

Write the claim in one sentence before you talk about color. *This screen argues that finishing the invoice matters more than editing it.* If you cannot write the sentence, you do not have a critique yet. You have a mood.

> Taste is what you reach for when the claim is blurry.

The rest of this course is practice in making the claim visible, then changing the structure when the claim is weak. Decoration comes last, and sometimes it does not come at all.
MD;
    }

    private static function lessonQuiet(): string
    {
        return <<<'MD'
Look at three products that feel quiet. Quiet is not the same as empty. Quiet means the hierarchy is doing the talking, so the chrome can sit down.

1. A banking transfer that names the amount in the button, not in a caption nearby.
2. A publishing tool whose irreversible action lives behind a sentence, not an icon.
3. A map that refuses to show every layer at once.

In each case, write the claim and the person it inconveniences. The person who wanted every layer, the person who wanted a cute icon, the person who wanted the button to say Continue. Those people are real. The interface decided against them on purpose.

If your own product cannot name who it inconveniences, it is still trying to be liked by a room.
MD;
    }

    private static function lessonGrids(): string
    {
        return <<<'MD'
A grid is an argument about kinship. Things that align are being asked to be compared. Things that break the alignment are being asked to be noticed.

Before you add a card, a shadow, or a tint, ask whether the alignment already says the thing. If it does, the decoration is nervous. Remove it and read the screen again from six feet away.

**A practical test:** cover the color. If the hierarchy survives, the color was optional. If it dies, the color was doing a job the structure refused.

This is not an argument for plainness as a style. It is an argument for plainness as a diagnostic.
MD;
    }

    private static function lessonType(): string
    {
        return <<<'MD'
Type is wayfinding for a tired eye. Size is the loudest signal, then weight, then color, then the words themselves — which is unfortunate, because the words are the point.

Use one family if you can. Change size before you change weight, and weight before you invent a second voice. A screen with four type styles is usually a screen that does not know what the claim is.

Read the screen as a path: where does the eye land, and is that the claim? If the eye lands on a label that says “Optional,” the type is arguing with the product.
MD;
    }

    private static function lessonChecklist(): string
    {
        return <<<'MD'
Run this alone, before you ask for a critique.

1. Write the claim in one sentence.
2. Point at the easiest action. Does it match the claim?
3. Point at the irreversible action. Is it structurally separated from preference?
4. Name the person inconvenienced.
5. Cover the color. Does the hierarchy hold?
6. Read the type as a path. Does the eye land on the claim?

If you fail 2 or 3, do not restyle. Restructure. Bring the revised screen to the live room with the sentence, not with a tour of your process.
MD;
    }

    private static function lessonScripts(): string
    {
        return <<<'MD'
A script is a file that does a job once, in front of you. A system is what that file becomes after six people have needed it to do the job differently.

You can hear the change. The file starts knowing about mail, money, and who is allowed to press the button. Those are policies. Policies hidden in templates are how a studio gets a checkout that cannot be explained.

## The move

Name the policies and give them a home:

- A router turns a URL into a call
- A controller checks the person and the input
- A service owns the rule
- A table remembers what the rule decided

If you cannot point at the rule, you do not have a system. You have a script with ambitions.
MD;
    }

    private static function lessonRequest(): string
    {
        return <<<'MD'
Follow one request without skipping.

1. The browser asks for a path.
2. The front controller boots session, database, and the person.
3. The router picks a controller method.
4. Middleware refuses the person who should not be there.
5. The controller validates and calls a service.
6. The service writes the decision.
7. A view renders the result, escaped.

Nothing in that list is allowed to invent a second copy of the commission rule. If the template computes money, the request has leaked.

Draw this for your own checkout before you add a gateway. The gateway is a guest. It does not get to own the enrollment.
MD;
    }

    private static function lessonMoney(): string
    {
        return <<<'MD'
Store money as integer cents. Floats are a way of being approximately paid.

A 20% commission on $89.00 is 1780 cents, and the instructor keeps 7120 if there is no coupon. A coupon has to be distributed across line items or the last item will be quietly wrong. Decide the rounding in the service that creates the order, and do not renegotiate it at payout time.

**Idempotency:** marking an order paid must be safe to repeat. Gateways retry. Buttons get clicked twice. Enrollment has a unique key for a reason.

The interesting part of payments is not the card form. It is the moment the studio decides who earned what, and the fact that you can still explain it after a refund.
MD;
    }
}
