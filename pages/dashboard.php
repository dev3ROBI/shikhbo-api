<?php
/**
 * Dashboard Page - Dynamic Data from MySQL
 */
if (!defined('SECURE_ACCESS')) {
    $mysqli = getDBConnection();
} else {
    // When included from index.php
    $mysqli = getDBConnection();
}

// Fetch real stats from database
$totalStudents = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE role IS NULL OR role = '' OR role = 'user'")->fetch_assoc()['count'];
$totalAdmins = $mysqli->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'")->fetch_assoc()['count'];
$totalExams = $mysqli->query("SELECT COUNT(*) as count FROM exams")->fetch_assoc()['count'] ?? 0;
$totalQuestions = $mysqli->query("SELECT COUNT(*) as count FROM questions")->fetch_assoc()['count'] ?? 0;

// Recent students
$recentStudents = $mysqli->query(
    "SELECT id, name, email, status, created_at FROM users 
     WHERE role IS NULL OR role = '' OR role = 'user' 
     ORDER BY created_at DESC LIMIT 5"
);
?>
<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-800">Admin Dashboard</h1>
    <p class="text-gray-500 mt-1">Welcome back, <?php echo sanitizeOutput($_SESSION['admin_name']); ?>! Here's what's happening today.</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-md p-6 transition-all hover:shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Students</p>
                <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo number_format($totalStudents); ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fa-solid fa-users text-green-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            <a href="index.php?page=students" class="text-shikhbo-primary hover:underline">View all students →</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 transition-all hover:shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Exams</p>
                <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo number_format($totalExams); ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fa-solid fa-file-alt text-blue-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            <a href="index.php?page=exams" class="text-shikhbo-primary hover:underline">Manage exams →</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 transition-all hover:shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Questions</p>
                <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo number_format($totalQuestions); ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fa-solid fa-database text-purple-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            <a href="index.php?page=questions" class="text-shikhbo-primary hover:underline">View questions →</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 transition-all hover:shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-500">Total Admins</p>
                <p class="text-2xl font-bold text-gray-800 mt-1"><?php echo number_format($totalAdmins); ?></p>
            </div>
            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                <i class="fa-solid fa-user-gear text-yellow-600 text-xl"></i>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600">
            <a href="index.php?page=admins" class="text-shikhbo-primary hover:underline">Manage admins →</a>
        </div>
    </div>
</div>

<!-- Recent Students Table -->
<div class="bg-white rounded-xl shadow-md overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-gray-800">Recent Students</h3>
        <a href="index.php?page=students" class="text-sm text-shikhbo-primary hover:underline">View All</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($recentStudents && $recentStudents->num_rows > 0): ?>
                    <?php while ($student = $recentStudents->fetch_assoc()): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-800">
                                <?php echo sanitizeOutput($student['name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?php echo sanitizeOutput($student['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <?php echo date('d M, Y', strtotime($student['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    <?php echo $student['status'] === 'active' ? 'text-green-800 bg-green-100' : 
                                        ($student['status'] === 'suspended' ? 'text-red-800 bg-red-100' : 'text-yellow-800 bg-yellow-100'); ?>">
                                    <?php echo ucfirst($student['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                            <i class="fa-solid fa-users text-3xl mb-2 block"></i>
                            No students found. Students will appear here once they register.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>