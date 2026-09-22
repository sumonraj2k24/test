<div class="certificate">
    <div class="certificate-inner">
        <p class="kicker"><?= e($siteName) ?></p>
        <h1>Certificate</h1>
        <p>This confirms that</p>
        <div class="name"><?= e($certificate['student_name']) ?></div>
        <p>completed</p>
        <h2><?= e($certificate['course_title']) ?></h2>
        <p class="quiet">Taught by <?= e($certificate['instructor_name']) ?></p>
        <div class="cert-meta">
            <div>Completed<br><strong><?= e(pretty_date($certificate['completed_at'])) ?></strong></div>
            <div>Code<br><strong><?= e($certificate['certificate_code']) ?></strong></div>
            <div>Verify<br><strong><?= e(url('/certificate/verify/' . $certificate['certificate_code'])) ?></strong></div>
        </div>
    </div>
</div>
<p class="no-print" style="text-align:center"><button class="btn" onclick="print()">Print</button> <a href="/certificate/verify/<?= e($certificate['certificate_code']) ?>">Verification page</a></p>
