<div class="page-head"><div><p class="kicker">Platform</p><h1>Analytics</h1></div></div>
<div class="stats">
    <div class="stat"><b><?= (int) $stats['users'] ?></b><span>Users</span></div>
    <div class="stat"><b><?= (int) $stats['courses'] ?></b><span>Published courses</span></div>
    <div class="stat"><b><?= money((int) $stats['month_revenue']) ?></b><span>This month</span></div>
    <div class="stat"><b><?= money((int) $stats['fees']) ?></b><span>Platform fees</span></div>
</div>
<h2>Revenue</h2>
<?php $max = max(1, ...array_column($stats['series'], 'value')); ?>
<div class="bars"><?php foreach ($stats['series'] as $bar): ?><div><i style="height:<?= (int) round($bar['value'] / $max * 100) ?>%"></i><span><?= e($bar['label']) ?></span></div><?php endforeach; ?></div>
<h2>Gateways</h2>
<ul><?php foreach ($stats['gateways'] as $row): ?><li><?= e($row['gateway']) ?> · <?= (int) $row['n'] ?> · <?= money((int) $row['total']) ?></li><?php endforeach; ?></ul>
<h2>Top courses</h2>
<div class="table-wrap"><table>
    <thead><tr><th>Course</th><th>Students</th><th>Rating</th><th>Revenue</th></tr></thead>
    <tbody><?php foreach ($stats['top_courses'] as $row): ?><tr><td><a href="/courses/<?= e($row['slug']) ?>"><?= e($row['title']) ?></a></td><td><?= (int) $row['students_count'] ?></td><td><?= e(number_format((float) $row['rating_avg'], 1)) ?></td><td><?= money((int) $row['revenue']) ?></td></tr><?php endforeach; ?></tbody>
</table></div>
