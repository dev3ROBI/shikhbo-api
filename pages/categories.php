<?php
$mysqli = getDBConnection();

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Security token validation failed.';
    } elseif ($_POST['action'] === 'add_category') {
        $name = sanitize($_POST['name']);
        $parentId = $_POST['parent_id'] ? intval($_POST['parent_id']) : null;
        $level = $parentId ? ($mysqli->query("SELECT level FROM exam_categories WHERE id = $parentId")->fetch_assoc()['level'] + 1) : 1;
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        
        // Unique slug বানাবে
        $stmt = $mysqli->prepare("INSERT INTO exam_categories (name, slug, parent_id, level) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssii', $name, $slug, $parentId, $level);
        if ($stmt->execute()) {
            $success = "Category added successfully.";
        } else {
            $error = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// সব ক্যাটাগরি ফেচ
$categories = $mysqli->query("SELECT * FROM exam_categories ORDER BY parent_id, sort_order, id");
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Exam Categories</h1>
    <p class="text-gray-500 mt-1">Multi-level exam category management</p>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Category List (Left) -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-md p-6">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Level</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php while ($cat = $categories->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm">
                            <span style="padding-left: <?php echo ($cat['level'] - 1) * 20; ?>px">
                                <?php if ($cat['level'] > 1): ?>— <?php endif; ?>
                                <?php echo sanitizeOutput($cat['name']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">Level <?php echo $cat['level']; ?></td>
                        <td class="px-4 py-3 text-sm"><?php echo $cat['category_type']; ?></td>
                        <td class="px-4 py-3 text-sm space-x-2">
                            <button class="text-shikhbo-primary hover:underline">Edit</button>
                            <button class="text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Category Form (Right) -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Add New Category</h3>
        <form method="POST" class="space-y-4">
            <?php echo getCSRFTokenField(); ?>
            <input type="hidden" name="action" value="add_category">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Parent</label>
                <select name="parent_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Root Category</option>
                    <?php
                    $parents = $mysqli->query("SELECT id, name FROM exam_categories WHERE level <= 3 ORDER BY id");
                    while ($p = $parents->fetch_assoc()):
                    ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo $p['name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" class="w-full bg-shikhbo-primary text-white py-2 rounded-lg font-medium hover:bg-indigo-700">
                Save Category
            </button>
        </form>
    </div>
</div>