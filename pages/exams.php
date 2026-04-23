<?php
$mysqli = getDBConnection();
$exams = $mysqli->query("SELECT id, title, subject, exam_date, duration_minutes, total_marks, status FROM exams ORDER BY exam_date DESC");
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Exams Management</h1>
        <p class="text-gray-500 mt-1">Manage all online examinations</p>
    </div>
    <button class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm hover:bg-indigo-700">
        <i class="fa-solid fa-plus mr-2"></i>Create Exam
    </button>
</div>

<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Duration</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Marks</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if ($exams && $exams->num_rows > 0): ?>
                <?php while ($exam = $exams->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800"><?php echo sanitizeOutput($exam['title']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo sanitizeOutput($exam['subject']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo date('d M, Y', strtotime($exam['exam_date'])); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo $exam['duration_minutes']; ?> min</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo $exam['total_marks']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                <?php echo $exam['status'] === 'active' ? 'text-green-800 bg-green-100' : 
                                    ($exam['status'] === 'completed' ? 'text-red-800 bg-red-100' : 'text-yellow-800 bg-yellow-100'); ?>">
                                <?php echo ucfirst($exam['status']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                            <button class="text-shikhbo-primary hover:underline">Edit</button>
                            <button class="text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                        <i class="fa-solid fa-file-circle-exclamation text-3xl mb-2 block"></i>
                        No exams created yet.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>