<?php
$mysqli = getDBConnection();
$results = $mysqli->query("SELECT r.id, u.name as student_name, e.title as exam_title, r.score, r.total_marks, r.percentage, r.status, r.completed_at 
                           FROM exam_results r 
                           JOIN users u ON r.user_id = u.id 
                           JOIN exams e ON r.exam_id = e.id 
                           ORDER BY r.completed_at DESC LIMIT 50");
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Exam Results</h1>
    <p class="text-gray-500 mt-1">View student performance and scores</p>
</div>

<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Exam</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Score</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Percentage</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if ($results && $results->num_rows > 0): ?>
                <?php while ($r = $results->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800"><?php echo sanitizeOutput($r['student_name']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo sanitizeOutput($r['exam_title']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo $r['score']; ?>/<?php echo $r['total_marks']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-shikhbo-primary h-2 rounded-full" style="width: <?php echo $r['percentage']; ?>%"></div>
                                </div>
                                <span class="text-sm font-medium"><?php echo $r['percentage']; ?>%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                <?php echo $r['status'] === 'passed' ? 'text-green-800 bg-green-100' : 'text-red-800 bg-red-100'; ?>">
                                <?php echo ucfirst($r['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d M, Y', strtotime($r['completed_at'])); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                        <i class="fa-solid fa-chart-simple text-3xl mb-2 block"></i>
                        No results available yet.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>