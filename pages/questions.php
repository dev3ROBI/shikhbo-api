<?php
$mysqli = getDBConnection();
$questions = $mysqli->query("SELECT q.id, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d, q.correct_answer, s.name as subject 
                             FROM questions q LEFT JOIN subjects s ON q.subject_id = s.id 
                             ORDER BY q.id DESC LIMIT 50");
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Question Bank</h1>
        <p class="text-gray-500 mt-1">Manage exam questions and answers</p>
    </div>
    <button class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm hover:bg-indigo-700">
        <i class="fa-solid fa-plus mr-2"></i>Add Question
    </button>
</div>

<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Question</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Correct</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if ($questions && $questions->num_rows > 0): ?>
                <?php while ($q = $questions->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#<?php echo $q['id']; ?></td>
                        <td class="px-6 py-4 text-sm text-gray-800 max-w-xs truncate"><?php echo sanitizeOutput($q['question_text']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo sanitizeOutput($q['subject'] ?? 'General'); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600"><?php echo sanitizeOutput($q['correct_answer']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                            <button class="text-shikhbo-primary hover:underline">Edit</button>
                            <button class="text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                        <i class="fa-solid fa-database text-3xl mb-2 block"></i>
                        No questions in bank yet.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>