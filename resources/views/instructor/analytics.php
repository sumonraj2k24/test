<div class="page-head"><div><p class="kicker">Studio</p><h1>Analytics</h1></div></div>
<div class="stats">
    <div class="stat"><b><?= money((int) $stats['revenue']) ?></b><span>Earnings, paid orders</span></div>
    <div class="stat"><b><?= (int) $stats['quiz_pass'] ?>%</b><span>Quiz pass rate</span></div>
    <div class="stat"><b><?= (int) $stats['completion'] ?>%</b><span>Completion</span></div>
    <div class="stat"><b><?= (int) $stats['students'] ?></b><span>Students</span></div>
</div>
<h2>Earnings, six months</h2>
<?php $max = max(1, ...array_column($stats['series'], 'value')); ?>
<div class="bars"><?php foreach ($stats['series'] as $bar): ?><div><i style="height:<?= (int) round($bar['value'] / $max * 100) ?>%"></i><span><?= e($bar['label']) ?></span></div><?php endforeach; ?></div>
<div class="table-wrap"><table>
    <thead><tr><th>Course</th><th>Students</th><th>Completed</th><th>Rating</th><th>Earnings</th></tr></thead>
    <tbody>
    <?php foreach ($stats['by_course'] as $row): ?>
        <tr><td><?= e($row['title']) ?></td><td><?= (int) $row['students_count'] ?></td><td><?= (int) $row['completed'] ?></td><td><?= e(number_format((float) $row['rating_avg'], 1)) ?></td><td><?= money((int) $row['revenue']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
</table></div>
