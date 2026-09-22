<?php

declare(strict_types=1);

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\CatalogController as AdminCatalogController;
use App\Http\Controllers\Admin\CommerceController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\SystemController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CronController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Instructor\CourseController as StudioCourseController;
use App\Http\Controllers\Instructor\CurriculumController;
use App\Http\Controllers\Instructor\LiveController as StudioLiveController;
use App\Http\Controllers\Instructor\QuizController as StudioQuizController;
use App\Http\Controllers\Instructor\StudioController;
use App\Http\Controllers\LearnController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\WebhookController;

/** @var App\Core\Router $router */

$router->get('/', [HomeController::class, 'index']);
$router->get('/courses', [CatalogController::class, 'index']);
$router->get('/courses/{slug}', [CatalogController::class, 'show']);
$router->get('/instructors/{id}', [CatalogController::class, 'instructor']);
$router->get('/live', [CatalogController::class, 'schedule']);
$router->get('/teach', [StudentController::class, 'teachForm']);
$router->get('/pages/{slug}', [PageController::class, 'show']);
$router->get('/contact', [PageController::class, 'contact']);
$router->post('/contact', [PageController::class, 'sendContact']);
$router->post('/newsletter', [PageController::class, 'subscribe']);
$router->get('/certificate/verify/{code}', [CertificateController::class, 'verify']);
$router->get('/certificates/{code}', [CertificateController::class, 'show']);
$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
$router->get('/robots.txt', [SeoController::class, 'robots']);
$router->get('/health', static function (): void {
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'app' => 'meridian', 'version' => config('version')], json_flags());
});

$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->get('/register', [AuthController::class, 'showRegister'], ['guest']);
$router->post('/register', [AuthController::class, 'register'], ['guest']);
$router->get('/auth/google', [AuthController::class, 'googleRedirect']);
$router->get('/auth/google/callback', [AuthController::class, 'googleCallback']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth']);

$router->get('/cart', [CartController::class, 'index']);
$router->post('/cart', [CartController::class, 'add']);
$router->post('/cart/remove', [CartController::class, 'remove']);

$router->post('/webhooks/stripe', [WebhookController::class, 'stripe']);
$router->post('/webhooks/paypal', [WebhookController::class, 'paypal']);
$router->post('/webhooks/mollie', [WebhookController::class, 'mollie']);
$router->post('/webhooks/paystack', [WebhookController::class, 'paystack']);
$router->get('/cron/run', [CronController::class, 'run']);

$router->group(['auth'], function ($router): void {
    $router->get('/checkout', [CheckoutController::class, 'show']);
    $router->post('/checkout', [CheckoutController::class, 'start']);
    $router->get('/checkout/pay/{number}', [CheckoutController::class, 'gateway']);
    $router->post('/checkout/pay/{number}', [CheckoutController::class, 'confirm']);
    $router->get('/checkout/success/{number}', [CheckoutController::class, 'success']);
    $router->get('/checkout/return/{gateway}', [CheckoutController::class, 'returning']);

    $router->get('/learn', [StudentController::class, 'learning']);
    $router->get('/learn/history', [StudentController::class, 'history']);
    $router->get('/learn/{slug}', [LearnController::class, 'course']);
    $router->get('/learn/{slug}/lesson/{id}', [LearnController::class, 'lesson']);
    $router->post('/learn/{slug}/lesson/{id}/complete', [LearnController::class, 'complete']);
    $router->post('/learn/{slug}/lesson/{id}/note', [LearnController::class, 'note']);
    $router->get('/learn/{slug}/quiz/{lesson}', [LearnController::class, 'quiz']);
    $router->post('/learn/{slug}/quiz/{lesson}', [LearnController::class, 'submitQuiz']);
    $router->get('/learn/{slug}/assignment/{lesson}', [LearnController::class, 'assignment']);
    $router->post('/learn/{slug}/assignment/{lesson}', [LearnController::class, 'submitAssignment']);
    $router->post('/courses/{slug}/enroll', [StudentController::class, 'enrollFree']);
    $router->post('/courses/{slug}/reviews', [CatalogController::class, 'review']);

    $router->get('/live/room/{id}', [StudentController::class, 'room']);
    $router->post('/live/room/{id}/attend', [StudentController::class, 'attend']);
    $router->get('/wishlist', [StudentController::class, 'wishlist']);
    $router->post('/wishlist', [StudentController::class, 'toggleWishlist']);
    $router->get('/orders', [StudentController::class, 'orders']);
    $router->get('/orders/{id}', [StudentController::class, 'order']);
    $router->get('/certificates', [StudentController::class, 'certificates']);
    $router->post('/teach', [StudentController::class, 'teachSubmit']);

    $router->get('/courses/{slug}/discussions', [ForumController::class, 'index']);
    $router->post('/courses/{slug}/discussions', [ForumController::class, 'store']);
    $router->get('/courses/{slug}/discussions/{id}', [ForumController::class, 'show']);
    $router->post('/discussions/{id}/reply', [ForumController::class, 'reply']);
    $router->post('/discussions/{id}/pin', [ForumController::class, 'pin']);

    $router->get('/messages', [MessageController::class, 'index']);
    $router->get('/messages/with/{id}', [MessageController::class, 'show']);
    $router->post('/messages', [MessageController::class, 'store']);
    $router->get('/notifications', [AccountController::class, 'notifications']);
    $router->post('/notifications/read', [AccountController::class, 'read']);
    $router->get('/account', [AccountController::class, 'edit']);
    $router->post('/account', [AccountController::class, 'update']);
});

$router->group(['can:studio.access'], function ($router): void {
    $router->get('/studio', [StudioController::class, 'index']);
    $router->get('/studio/analytics', [StudioController::class, 'analytics']);
    $router->get('/studio/students', [StudioController::class, 'students']);
    $router->get('/studio/earnings', [StudioController::class, 'earnings'], ['can:earnings.view']);
    $router->post('/studio/earnings/payout', [StudioController::class, 'requestPayout'], ['can:earnings.view']);
    $router->get('/studio/courses', [StudioCourseController::class, 'index']);
    $router->get('/studio/courses/create', [StudioCourseController::class, 'create'], ['can:courses.create']);
    $router->post('/studio/courses', [StudioCourseController::class, 'store'], ['can:courses.create']);
    $router->get('/studio/courses/{id}/edit', [StudioCourseController::class, 'edit']);
    $router->post('/studio/courses/{id}', [StudioCourseController::class, 'update']);
    $router->post('/studio/courses/{id}/submit', [StudioCourseController::class, 'submit']);
    $router->post('/studio/courses/{id}/collaborators', [StudioCourseController::class, 'collaborator']);
    $router->get('/studio/courses/{id}/curriculum', [CurriculumController::class, 'edit']);
    $router->post('/studio/courses/{id}/sections', [CurriculumController::class, 'addSection']);
    $router->post('/studio/sections/{id}', [CurriculumController::class, 'updateSection']);
    $router->post('/studio/sections/{id}/delete', [CurriculumController::class, 'deleteSection']);
    $router->post('/studio/sections/{id}/lessons', [CurriculumController::class, 'addLesson']);
    $router->post('/studio/lessons/{id}', [CurriculumController::class, 'updateLesson']);
    $router->post('/studio/lessons/{id}/delete', [CurriculumController::class, 'deleteLesson']);
    $router->post('/studio/lessons/{id}/move', [CurriculumController::class, 'move']);
    $router->get('/studio/quizzes', [StudioQuizController::class, 'results']);
    $router->get('/studio/quizzes/{id}', [StudioQuizController::class, 'edit']);
    $router->post('/studio/quizzes/{id}', [StudioQuizController::class, 'update']);
    $router->post('/studio/quizzes/{id}/questions', [StudioQuizController::class, 'addQuestion']);
    $router->post('/studio/questions/{id}/delete', [StudioQuizController::class, 'deleteQuestion']);
    $router->get('/studio/assignments/{id}', [StudioQuizController::class, 'gradeList']);
    $router->post('/studio/submissions/{id}/grade', [StudioQuizController::class, 'grade']);
    $router->get('/studio/live', [StudioLiveController::class, 'index'], ['can:live.manage']);
    $router->post('/studio/live', [StudioLiveController::class, 'store'], ['can:live.manage']);
    $router->post('/studio/live/{id}/cancel', [StudioLiveController::class, 'delete'], ['can:live.manage']);
});

$router->group(['can:admin.access'], function ($router): void {
    $router->get('/admin', [AdminDashboardController::class, 'index']);
    $router->get('/admin/analytics', [AdminDashboardController::class, 'analytics'], ['can:analytics.view']);
    $router->get('/admin/users', [AdminUserController::class, 'index'], ['can:users.manage']);
    $router->post('/admin/users/{id}', [AdminUserController::class, 'update'], ['can:users.manage']);
    $router->get('/admin/applications', [AdminUserController::class, 'applications'], ['can:applications.review']);
    $router->post('/admin/applications/{id}', [AdminUserController::class, 'reviewApplication'], ['can:applications.review']);
    $router->get('/admin/courses', [AdminCatalogController::class, 'courses'], ['can:courses.review']);
    $router->post('/admin/courses/{id}/moderate', [AdminCatalogController::class, 'moderate'], ['can:courses.review']);
    $router->get('/admin/categories', [AdminCatalogController::class, 'categories'], ['can:categories.manage']);
    $router->post('/admin/categories', [AdminCatalogController::class, 'saveCategory'], ['can:categories.manage']);
    $router->post('/admin/categories/{id}/delete', [AdminCatalogController::class, 'deleteCategory'], ['can:categories.manage']);
    $router->get('/admin/orders', [CommerceController::class, 'orders'], ['can:orders.manage']);
    $router->get('/admin/orders/export', [CommerceController::class, 'export'], ['can:orders.manage']);
    $router->post('/admin/orders/{id}/refund', [CommerceController::class, 'refund'], ['can:orders.manage']);
    $router->get('/admin/payouts', [CommerceController::class, 'payouts'], ['can:payouts.manage']);
    $router->post('/admin/payouts/{id}', [CommerceController::class, 'updatePayout'], ['can:payouts.manage']);
    $router->get('/admin/pages', [ContentController::class, 'pages'], ['can:content.manage']);
    $router->get('/admin/pages/new', [ContentController::class, 'editPage'], ['can:content.manage']);
    $router->get('/admin/pages/{id}', [ContentController::class, 'editPage'], ['can:content.manage']);
    $router->post('/admin/pages', [ContentController::class, 'savePage'], ['can:content.manage']);
    $router->get('/admin/appearance', [ContentController::class, 'appearance'], ['can:content.manage']);
    $router->post('/admin/appearance', [ContentController::class, 'saveAppearance'], ['can:content.manage']);
    $router->get('/admin/newsletter', [ContentController::class, 'newsletter'], ['can:newsletter.send']);
    $router->post('/admin/newsletter', [ContentController::class, 'sendNewsletter'], ['can:newsletter.send']);
    $router->get('/admin/media', [ContentController::class, 'media'], ['can:media.manage']);
    $router->post('/admin/media', [ContentController::class, 'upload'], ['can:media.manage']);
    $router->post('/admin/media/{id}/delete', [ContentController::class, 'deleteMedia'], ['can:media.manage']);
    $router->get('/admin/inbox', [ContentController::class, 'inbox']);
    $router->post('/admin/inbox/read', [ContentController::class, 'inbox']);
    $router->get('/admin/settings', [SystemController::class, 'settings'], ['can:settings.manage']);
    $router->post('/admin/settings', [SystemController::class, 'saveSettings'], ['can:settings.manage']);
    $router->post('/admin/settings/test-mail', [SystemController::class, 'testMail'], ['can:settings.manage']);
    $router->get('/admin/permissions', [SystemController::class, 'permissions'], ['can:roles.manage']);
    $router->post('/admin/permissions', [SystemController::class, 'savePermissions'], ['can:roles.manage']);
    $router->post('/admin/roles', [SystemController::class, 'createRole'], ['can:roles.manage']);
    $router->get('/admin/backups', [SystemController::class, 'backups'], ['can:backups.run']);
    $router->post('/admin/backups', [SystemController::class, 'createBackup'], ['can:backups.run']);
    $router->get('/admin/backups/{id}/download', [SystemController::class, 'downloadBackup'], ['can:backups.run']);
    $router->get('/admin/updates', [SystemController::class, 'updates'], ['can:updates.apply']);
    $router->post('/admin/updates/apply', [SystemController::class, 'applyUpdate'], ['can:updates.apply']);
});
