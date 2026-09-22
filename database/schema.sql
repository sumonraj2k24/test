CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT,
    role TEXT NOT NULL DEFAULT 'student',
    headline TEXT,
    bio TEXT,
    avatar_path TEXT,
    google_id TEXT,
    status TEXT NOT NULL DEFAULT 'active',
    payout_method TEXT,
    payout_details TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT
);

CREATE TABLE roles (
    slug TEXT PRIMARY KEY,
    name TEXT NOT NULL,
    is_system INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    slug TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    group_name TEXT NOT NULL
);

CREATE TABLE role_permissions (
    role TEXT NOT NULL,
    permission_id INTEGER NOT NULL,
    PRIMARY KEY (role, permission_id)
);

CREATE TABLE instructor_applications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    expertise TEXT NOT NULL,
    years_experience INTEGER,
    statement TEXT NOT NULL,
    sample_url TEXT,
    status TEXT NOT NULL DEFAULT 'pending',
    review_note TEXT,
    reviewed_by INTEGER,
    created_at TEXT NOT NULL,
    reviewed_at TEXT
);

CREATE TABLE categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT,
    accent TEXT,
    position INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE courses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    instructor_id INTEGER NOT NULL,
    category_id INTEGER,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    subtitle TEXT,
    description TEXT,
    level TEXT NOT NULL DEFAULT 'all',
    language TEXT NOT NULL DEFAULT 'English',
    price_cents INTEGER NOT NULL DEFAULT 0,
    compare_cents INTEGER,
    thumbnail TEXT,
    preview_video TEXT,
    status TEXT NOT NULL DEFAULT 'draft',
    is_featured INTEGER NOT NULL DEFAULT 0,
    enforce_sequence INTEGER NOT NULL DEFAULT 0,
    rating_avg REAL NOT NULL DEFAULT 0,
    rating_count INTEGER NOT NULL DEFAULT 0,
    students_count INTEGER NOT NULL DEFAULT 0,
    duration_minutes INTEGER NOT NULL DEFAULT 0,
    requirements TEXT,
    outcomes TEXT,
    seo_title TEXT,
    seo_description TEXT,
    rejection_note TEXT,
    published_at TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT
);

CREATE TABLE course_instructors (
    course_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    role TEXT NOT NULL DEFAULT 'collaborator',
    created_at TEXT,
    PRIMARY KEY (course_id, user_id)
);

CREATE TABLE sections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    position INTEGER NOT NULL DEFAULT 0,
    drip_days INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE lessons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    section_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    type TEXT NOT NULL DEFAULT 'article',
    summary TEXT,
    content TEXT,
    video_url TEXT,
    attachment_path TEXT,
    duration_minutes INTEGER NOT NULL DEFAULT 0,
    is_preview INTEGER NOT NULL DEFAULT 0,
    position INTEGER NOT NULL DEFAULT 0,
    drip_at TEXT,
    live_class_id INTEGER
);

CREATE TABLE quizzes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lesson_id INTEGER NOT NULL UNIQUE,
    course_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT,
    pass_percent INTEGER NOT NULL DEFAULT 70,
    time_limit_minutes INTEGER NOT NULL DEFAULT 0,
    attempts_allowed INTEGER NOT NULL DEFAULT 3
);

CREATE TABLE quiz_questions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quiz_id INTEGER NOT NULL,
    question TEXT NOT NULL,
    type TEXT NOT NULL DEFAULT 'single',
    options_json TEXT,
    correct_json TEXT,
    points INTEGER NOT NULL DEFAULT 1,
    position INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE quiz_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    quiz_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    score_percent INTEGER NOT NULL DEFAULT 0,
    points_earned INTEGER NOT NULL DEFAULT 0,
    points_possible INTEGER NOT NULL DEFAULT 0,
    passed INTEGER NOT NULL DEFAULT 0,
    answers_json TEXT,
    started_at TEXT,
    submitted_at TEXT NOT NULL
);

CREATE TABLE assignments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    lesson_id INTEGER NOT NULL UNIQUE,
    course_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    instructions TEXT,
    due_days INTEGER NOT NULL DEFAULT 7,
    max_score INTEGER NOT NULL DEFAULT 100
);

CREATE TABLE assignment_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    assignment_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    content TEXT,
    file_path TEXT,
    score INTEGER,
    feedback TEXT,
    status TEXT NOT NULL DEFAULT 'submitted',
    submitted_at TEXT NOT NULL,
    graded_at TEXT,
    UNIQUE (assignment_id, user_id)
);

CREATE TABLE enrollments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,
    order_id INTEGER,
    progress_percent INTEGER NOT NULL DEFAULT 0,
    completed_at TEXT,
    certificate_code TEXT,
    last_lesson_id INTEGER,
    enrolled_at TEXT NOT NULL,
    UNIQUE (user_id, course_id)
);

CREATE TABLE lesson_progress (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    lesson_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,
    completed INTEGER NOT NULL DEFAULT 0,
    watched_seconds INTEGER NOT NULL DEFAULT 0,
    last_watched_at TEXT,
    completed_at TEXT,
    UNIQUE (user_id, lesson_id)
);

CREATE TABLE reviews (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,
    rating INTEGER NOT NULL,
    comment TEXT,
    created_at TEXT NOT NULL,
    UNIQUE (user_id, course_id)
);

CREATE TABLE wishlists (
    user_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,
    created_at TEXT,
    PRIMARY KEY (user_id, course_id)
);

CREATE TABLE cart_items (
    user_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,
    created_at TEXT,
    PRIMARY KEY (user_id, course_id)
);

CREATE TABLE coupons (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    code TEXT NOT NULL UNIQUE,
    type TEXT NOT NULL DEFAULT 'percent',
    value INTEGER NOT NULL,
    active INTEGER NOT NULL DEFAULT 1,
    expires_at TEXT,
    max_uses INTEGER,
    uses INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE orders (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    number TEXT NOT NULL UNIQUE,
    subtotal_cents INTEGER NOT NULL,
    discount_cents INTEGER NOT NULL DEFAULT 0,
    total_cents INTEGER NOT NULL,
    currency TEXT NOT NULL DEFAULT 'USD',
    gateway TEXT,
    gateway_ref TEXT,
    coupon_code TEXT,
    status TEXT NOT NULL DEFAULT 'pending',
    created_at TEXT NOT NULL,
    paid_at TEXT
);

CREATE TABLE order_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    course_id INTEGER NOT NULL,
    price_cents INTEGER NOT NULL,
    commission_rate INTEGER NOT NULL,
    platform_fee_cents INTEGER NOT NULL,
    instructor_earning_cents INTEGER NOT NULL,
    instructor_id INTEGER NOT NULL
);

CREATE TABLE transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    order_id INTEGER NOT NULL,
    gateway TEXT NOT NULL,
    gateway_ref TEXT,
    amount_cents INTEGER NOT NULL,
    status TEXT NOT NULL,
    last4 TEXT,
    payload_json TEXT,
    created_at TEXT NOT NULL
);

CREATE TABLE payouts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    instructor_id INTEGER NOT NULL,
    amount_cents INTEGER NOT NULL,
    status TEXT NOT NULL DEFAULT 'requested',
    method TEXT,
    details TEXT,
    note TEXT,
    requested_at TEXT NOT NULL,
    processed_at TEXT,
    processed_by INTEGER
);

CREATE TABLE live_classes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    lesson_id INTEGER,
    instructor_id INTEGER NOT NULL,
    title TEXT NOT NULL,
    description TEXT,
    starts_at TEXT NOT NULL,
    duration_minutes INTEGER NOT NULL DEFAULT 60,
    zoom_meeting_id TEXT,
    zoom_join_url TEXT,
    zoom_start_url TEXT,
    status TEXT NOT NULL DEFAULT 'scheduled',
    created_at TEXT NOT NULL
);

CREATE TABLE live_attendance (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    live_class_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    joined_at TEXT NOT NULL,
    UNIQUE (live_class_id, user_id)
);

CREATE TABLE threads (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    course_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    lesson_id INTEGER,
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    pinned INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT
);

CREATE TABLE posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    thread_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    body TEXT NOT NULL,
    created_at TEXT NOT NULL
);

CREATE TABLE messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    sender_id INTEGER NOT NULL,
    recipient_id INTEGER NOT NULL,
    course_id INTEGER,
    subject TEXT,
    body TEXT NOT NULL,
    read_at TEXT,
    created_at TEXT NOT NULL
);

CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT NOT NULL,
    title TEXT NOT NULL,
    body TEXT,
    link TEXT,
    read_at TEXT,
    created_at TEXT NOT NULL
);

CREATE TABLE pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    content TEXT,
    seo_title TEXT,
    seo_description TEXT,
    status TEXT NOT NULL DEFAULT 'published',
    show_in_footer INTEGER NOT NULL DEFAULT 1,
    show_in_nav INTEGER NOT NULL DEFAULT 0,
    position INTEGER NOT NULL DEFAULT 0,
    updated_at TEXT
);

CREATE TABLE settings (
    key TEXT PRIMARY KEY,
    value TEXT
);

CREATE TABLE subscribers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT NOT NULL UNIQUE,
    name TEXT,
    status TEXT NOT NULL DEFAULT 'active',
    created_at TEXT NOT NULL
);

CREATE TABLE campaigns (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    subject TEXT NOT NULL,
    body TEXT NOT NULL,
    recipients_count INTEGER NOT NULL DEFAULT 0,
    sent_at TEXT,
    created_by INTEGER,
    created_at TEXT NOT NULL
);

CREATE TABLE media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    filename TEXT NOT NULL,
    path TEXT NOT NULL,
    mime TEXT,
    size_bytes INTEGER,
    disk TEXT NOT NULL DEFAULT 'local',
    created_at TEXT NOT NULL
);

CREATE TABLE backups (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT NOT NULL,
    size_bytes INTEGER,
    created_by INTEGER,
    created_at TEXT NOT NULL
);

CREATE TABLE audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    action TEXT NOT NULL,
    subject TEXT,
    meta TEXT,
    created_at TEXT NOT NULL
);

CREATE TABLE system_updates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    version TEXT NOT NULL,
    notes TEXT,
    applied_at TEXT NOT NULL,
    applied_by INTEGER
);

CREATE TABLE contact_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    subject TEXT,
    body TEXT NOT NULL,
    created_at TEXT NOT NULL,
    read_at TEXT
);

CREATE INDEX idx_courses_status ON courses (status);
CREATE INDEX idx_courses_category ON courses (category_id);
CREATE INDEX idx_lessons_course ON lessons (course_id);
CREATE INDEX idx_enroll_user ON enrollments (user_id);
CREATE INDEX idx_progress_user ON lesson_progress (user_id, last_watched_at);
CREATE INDEX idx_notifications_user ON notifications (user_id, read_at);
CREATE INDEX idx_messages_pair ON messages (recipient_id, sender_id);
CREATE INDEX idx_orders_user ON orders (user_id, status);
