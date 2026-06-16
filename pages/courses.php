<?php
$mysqli = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { $error = 'Security token validation failed.'; }
    else {
        $action = $_POST['action'] ?? '';
        if ($action === 'add_course') {
            $title = sanitize($_POST['title']);
            $desc = sanitize($_POST['description'] ?? '');
            $shortDesc = sanitize($_POST['short_description'] ?? '');
            $categoryId = $_POST['category_id'] ? intval($_POST['category_id']) : null;
            $parentCourseId = $_POST['parent_course_id'] ? intval($_POST['parent_course_id']) : null;
            $courseType = sanitize($_POST['course_type'] ?? 'skill');
            $difficulty = sanitize($_POST['difficulty'] ?? 'beginner');
            $price = floatval($_POST['price'] ?? 0);
            $isFree = intval($_POST['is_free'] ?? 1);
            $coverImage = sanitize($_POST['cover_image'] ?? '');
            $durationHours = intval($_POST['duration_hours'] ?? 0);
            $isActive = intval($_POST['is_active'] ?? 1);
            $isFeatured = intval($_POST['is_featured'] ?? 0);
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title)) . '-' . substr(uniqid(), -6);
            $stmt = $mysqli->prepare("INSERT INTO courses (title,slug,short_description,description,cover_image,price,is_free,category_id,parent_course_id,course_type,difficulty,duration_hours,is_active,is_featured) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssdsssssiii', $title, $slug, $shortDesc, $desc, $coverImage, $price, $isFree, $categoryId, $parentCourseId, $courseType, $difficulty, $durationHours, $isActive, $isFeatured);
            $stmt->execute() ? $success = "Course added successfully." : $error = $stmt->error; $stmt->close();
        } elseif ($action === 'edit_course') {
            $courseId = intval($_POST['course_id']);
            $title = sanitize($_POST['title']);
            $desc = sanitize($_POST['description'] ?? '');
            $shortDesc = sanitize($_POST['short_description'] ?? '');
            $categoryId = $_POST['category_id'] ? intval($_POST['category_id']) : null;
            $parentCourseId = $_POST['parent_course_id'] ? intval($_POST['parent_course_id']) : null;
            $courseType = sanitize($_POST['course_type'] ?? 'skill');
            $difficulty = sanitize($_POST['difficulty'] ?? 'beginner');
            $price = floatval($_POST['price'] ?? 0);
            $isFree = intval($_POST['is_free'] ?? 1);
            $coverImage = sanitize($_POST['cover_image'] ?? '');
            $durationHours = intval($_POST['duration_hours'] ?? 0);
            $isActive = intval($_POST['is_active'] ?? 1);
            $isFeatured = intval($_POST['is_featured'] ?? 0);
            $stmt = $mysqli->prepare("UPDATE courses SET title=?,short_description=?,description=?,cover_image=?,price=?,is_free=?,category_id=?,parent_course_id=?,course_type=?,difficulty=?,duration_hours=?,is_active=?,is_featured=? WHERE id=?");
            $stmt->bind_param('ssssdissssiiii', $title, $shortDesc, $desc, $coverImage, $price, $isFree, $categoryId, $parentCourseId, $courseType, $difficulty, $durationHours, $isActive, $isFeatured, $courseId);
            $stmt->execute() ? $success = "Course updated successfully." : $error = $stmt->error; $stmt->close();
        } elseif ($action === 'delete_course') {
            $courseId = intval($_POST['course_id']);
            $result = $mysqli->query("SELECT parent_course_id FROM courses WHERE id=$courseId");
            $parent = $result ? $result->fetch_assoc() : null;
            $np = $parent ? ($parent['parent_course_id'] ?? null) : null;
            if ($np) { $stmt = $mysqli->prepare("UPDATE courses SET parent_course_id=? WHERE parent_course_id=?"); $stmt->bind_param('ii', $np, $courseId); $stmt->execute(); $stmt->close(); }
            else { $stmt = $mysqli->prepare("UPDATE courses SET parent_course_id=NULL WHERE parent_course_id=?"); $stmt->bind_param('i', $courseId); $stmt->execute(); $stmt->close(); }
            $stmt = $mysqli->prepare("DELETE FROM courses WHERE id=?"); $stmt->bind_param('i', $courseId);
            $stmt->execute() ? $success = "Course deleted successfully." : $error = $stmt->error; $stmt->close();
        }
    }
}

$courses = $mysqli->query("SELECT c.*, ec.name AS category_name FROM courses c LEFT JOIN exam_categories ec ON c.category_id = ec.id ORDER BY c.id DESC");

$totalCourses = 0; $freeCourses = 0; $paidCourses = 0; $featuredCount = 0;
$r = $mysqli->query("SELECT COUNT(*) AS c FROM courses"); if ($r) $totalCourses = $r->fetch_assoc()['c'];
$r = $mysqli->query("SELECT COUNT(*) AS c FROM courses WHERE is_free=1"); if ($r) $freeCourses = $r->fetch_assoc()['c'];
$r = $mysqli->query("SELECT COUNT(*) AS c FROM courses WHERE is_free=0"); if ($r) $paidCourses = $r->fetch_assoc()['c'];
$r = $mysqli->query("SELECT COUNT(*) AS c FROM courses WHERE is_featured=1"); if ($r) $featuredCount = $r->fetch_assoc()['c'];

$allCoursesList = $mysqli->query("SELECT id, title, parent_course_id FROM courses ORDER BY title");
if (!$allCoursesList) { $allCoursesList = []; }

// Build category breadcrumb paths
$allCatsData = $mysqli->query("SELECT id, name, parent_id FROM exam_categories ORDER BY parent_id, id");
$catsById = [];
if ($allCatsData) { while ($c = $allCatsData->fetch_assoc()) { $catsById[$c['id']] = $c; } }
function buildCatPath($id, $cats, $depth = 0) {
    if ($depth > 20 || !isset($cats[$id])) return '';
    $p = $cats[$id]['parent_id'];
    $n = $cats[$id]['name'];
    return ($p && isset($cats[$p])) ? buildCatPath($p, $cats, $depth + 1) . ' → ' . $n : $n;
}
$catPathOptions = '';
$lastLevel = 0;
if ($allCatsData) {
    $allCatsData->data_seek(0);
    while ($c = $allCatsData->fetch_assoc()) {
        $path = buildCatPath($c['id'], $catsById);
        $catPathOptions .= "<option value='{$c['id']}'>" . sanitizeOutput($path) . "</option>";
    }
}

// Build course breadcrumb paths for parent course select
$allCoursesArr = [];
if ($allCoursesList) {
    $allCoursesList->data_seek(0);
    while ($pc = $allCoursesList->fetch_assoc()) { $allCoursesArr[$pc['id']] = $pc; }
}
function buildCoursePath($id, $courses, $depth = 0) {
    if ($depth > 20 || !isset($courses[$id])) return '';
    $p = $courses[$id]['parent_course_id'];
    $n = $courses[$id]['title'];
    return ($p && isset($courses[$p])) ? buildCoursePath($p, $courses, $depth + 1) . ' → ' . $n : $n;
}
$coursePathOptions = '';
foreach ($allCoursesArr as $id => $c) {
    $path = buildCoursePath($id, $allCoursesArr);
    $coursePathOptions .= "<option value='{$id}'>" . sanitizeOutput($path) . "</option>";
}
?>

<div class="page-content">
    <?php if(isset($error)):?>
    <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl flex items-center gap-3 alert-auto-dismiss">
        <i class="fa-solid fa-circle-exclamation text-red-500"></i><span class="text-red-700 dark:text-red-300"><?php echo sanitizeOutput($error);?></span>
    </div>
    <?php endif;?>
    <?php if(isset($success)):?>
    <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl flex items-center gap-3 alert-auto-dismiss">
        <i class="fa-solid fa-circle-check text-green-500"></i><span class="text-green-700 dark:text-green-300"><?php echo sanitizeOutput($success);?></span>
    </div>
    <?php endif;?>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-gray-100">Courses</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1"><?php echo $totalCourses;?> courses</p>
        </div>
        <button onclick="openAddModal()" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2 shadow-lg shadow-indigo-200 dark:shadow-indigo-900/30">
            <i class="fa-solid fa-plus"></i>Add Course
        </button>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                    <i class="fa-solid fa-book text-blue-600 dark:text-blue-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?php echo $totalCourses;?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <i class="fa-solid fa-circle-check text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?php echo $freeCourses;?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Free</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                    <i class="fa-solid fa-coins text-amber-600 dark:text-amber-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?php echo $paidCourses;?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Paid</p>
                </div>
            </div>
        </div>
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <i class="fa-solid fa-star text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800 dark:text-gray-100"><?php echo $featuredCount;?></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Featured</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="overflow-x-auto table-wrapper">
            <table class="w-full">
                <thead class="table-header">
                    <tr>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cover</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Title</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Category</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Price</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Difficulty</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Status</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Featured</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                    <?php while($c = $courses->fetch_assoc()):
                        $diffColors = ['beginner'=>'green','intermediate'=>'amber','advanced'=>'red'];
                        $diffColor = $diffColors[$c['difficulty']]??'gray';
                    ?>
                    <tr class="table-row">
                        <td class="px-4 py-3.5">
                            <?php if($c['cover_image']):?>
                            <img src="<?php echo sanitizeOutput($c['cover_image']);?>" alt="" class="h-10 w-10 object-cover rounded">
                            <?php else:?>
                            <div class="h-10 w-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <i class="fa-solid fa-image text-gray-400 dark:text-gray-500"></i>
                            </div>
                            <?php endif;?>
                        </td>
                        <td class="px-4 py-3.5">
                            <span class="font-medium text-gray-800 dark:text-gray-100"><?php echo sanitizeOutput($c['title']);?></span>
                        </td>
                        <td class="px-4 py-3.5 hide-mobile">
                            <span class="badge bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300"><?php echo sanitizeOutput($c['category_name'] ?? 'Uncategorized');?></span>
                        </td>
                        <td class="px-4 py-3.5 hide-mobile">
                            <?php if($c['is_free']):?>
                            <span class="badge bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Free</span>
                            <?php else:?>
                            <span class="font-medium text-gray-800 dark:text-gray-100">৳<?php echo number_format($c['price'],2);?></span>
                            <?php endif;?>
                        </td>
                        <td class="px-4 py-3.5 hide-mobile">
                            <span class="badge bg-<?php echo $diffColor;?>-100 text-<?php echo $diffColor;?>-700 dark:bg-<?php echo $diffColor;?>-900/30 dark:text-<?php echo $diffColor;?>-400"><?php echo ucfirst($c['difficulty']);?></span>
                        </td>
                        <td class="px-4 py-3.5 hide-mobile">
                            <?php echo $c['is_active'] ? "<span class='badge bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'><i class='fa-solid fa-check mr-1'></i>Active</span>" : "<span class='badge bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'><i class='fa-solid fa-xmark mr-1'></i>Inactive</span>";?>
                        </td>
                        <td class="px-4 py-3.5 hide-mobile">
                            <?php echo $c['is_featured'] ? "<span class='badge bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'><i class='fa-solid fa-star mr-1'></i>Featured</span>" : "<span class='badge bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'>No</span>";?>
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-1">
                                <button onclick='editCourse(<?php echo htmlspecialchars(json_encode($c),ENT_QUOTES,'UTF-8');?>)' class='p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors' title='Edit'><i class='fa-solid fa-pen-to-square'></i></button>
                                <button onclick='deleteCourse(<?php echo $c['id'];?>)' class='p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors' title='Delete'><i class='fa-solid fa-trash'></i></button>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile;?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="courseModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 modal-backdrop" onclick="closeModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="modal-content w-full max-w-2xl pointer-events-auto">
            <div class="modal-header flex items-center justify-between sticky top-0 z-10">
                <h3 class="text-lg font-semibold" id="courseModalTitle">Add Course</h3>
                <button onclick="closeModal()" class="p-2 rounded-lg transition-all">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form method="POST" class="modal-body-scroll space-y-4" id="courseForm">
                <?php echo getCSRFTokenField();?>
                <input type="hidden" name="action" id="courseAction" value="add_course">
                <input type="hidden" name="course_id" id="courseId">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Title</label>
                        <input type="text" name="title" id="courseTitle" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                        <textarea name="description" id="courseDesc" rows="3" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Short Description</label>
                        <input type="text" name="short_description" id="courseShortDesc" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Category</label>
                        <select name="category_id" id="courseCategory" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="">No Category</option>
                            <?php echo $catPathOptions; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Parent Course</label>
                        <select name="parent_course_id" id="courseParent" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="">None (Root Course)</option>
                            <?php echo $coursePathOptions; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Course Type</label>
                        <select name="course_type" id="courseType" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="academic">Academic</option>
                            <option value="skill">Skill</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Difficulty</label>
                        <select name="difficulty" id="courseDifficulty" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Price</label>
                        <input type="number" name="price" id="coursePrice" step="0.01" min="0" value="0" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Is Free</label>
                        <select name="is_free" id="courseIsFree" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Cover Image</label>
                        <div class="flex items-center gap-4">
                            <div id="imagePreviewContainer" class="w-16 h-16 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 flex items-center justify-center overflow-hidden bg-gray-50 dark:bg-gray-900/50 flex-shrink-0">
                                <img id="courseImagePreview" src="" alt="" class="w-full h-full object-cover hidden">
                                <i id="imagePlaceholderIcon" class="fa-solid fa-cloud-arrow-up text-gray-300"></i>
                            </div>
                            <div class="flex-1 space-y-2">
                                <input type="file" id="courseImageFile" accept="image/*" class="hidden" onchange="uploadToImgBB(this)">
                                <button type="button" onclick="document.getElementById('courseImageFile').click()" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-xs font-bold hover:bg-gray-200 dark:hover:bg-gray-600 transition-all flex items-center gap-2">
                                    <i class="fa-solid fa-image"></i>
                                    <span id="uploadBtnText">Select Image</span>
                                </button>
                                <input type="hidden" name="cover_image" id="courseCoverImage">
                                <p id="uploadStatus" class="text-[10px] font-bold text-gray-400 uppercase hidden"></p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Duration (Hours)</label>
                        <input type="number" name="duration_hours" id="courseDurationHours" min="0" value="0" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Is Active</label>
                        <select name="is_active" id="courseIsActive" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Is Featured</label>
                        <select name="is_featured" id="courseIsFeatured" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="deleteCourseForm" method="POST" class="hidden"><?php echo getCSRFTokenField();?><input type="hidden" name="action" value="delete_course"><input type="hidden" name="course_id" id="deleteCourseId"></form>

<script>
function uploadToImgBB(input) {
    const file = input.files[0];
    if (!file) return;

    const status = document.getElementById('uploadStatus');
    const btnText = document.getElementById('uploadBtnText');
    const preview = document.getElementById('courseImagePreview');
    const icon = document.getElementById('imagePlaceholderIcon');
    const hiddenInput = document.getElementById('courseCoverImage');

    status.textContent = 'Uploading...';
    status.classList.remove('hidden', 'text-red-500', 'text-emerald-500');
    status.classList.add('text-gray-400');
    btnText.textContent = 'Please wait...';
    input.disabled = true;

    const formData = new FormData();
    formData.append('image', file);

    fetch('https://api.imgbb.com/1/upload?key=9afda477a902e97f3638eb6375188f81', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const url = data.data.url;
            hiddenInput.value = url;
            preview.src = url;
            preview.classList.remove('hidden');
            icon.classList.add('hidden');
            status.textContent = 'Upload Successful';
            status.classList.replace('text-gray-400', 'text-emerald-500');
            btnText.textContent = 'Change Image';
            showToast('Image uploaded successfully', 'success');
        } else {
            throw new Error('Upload failed');
        }
    })
    .catch(err => {
        status.textContent = 'Upload Failed';
        status.classList.replace('text-gray-400', 'text-red-500');
        btnText.textContent = 'Try Again';
        showToast('Image upload failed', 'error');
    })
    .finally(() => {
        input.disabled = false;
        input.value = '';
    });
}

function openAddModal() {
    document.getElementById('courseModalTitle').textContent = 'Add Course';
    document.getElementById('courseAction').value = 'add_course';
    document.getElementById('courseId').value = '';
    document.getElementById('courseTitle').value = '';
    document.getElementById('courseDesc').value = '';
    document.getElementById('courseShortDesc').value = '';
    document.getElementById('courseCategory').value = '';
    document.getElementById('courseParent').value = '';
    document.getElementById('courseType').value = 'skill';
    document.getElementById('courseDifficulty').value = 'beginner';
    document.getElementById('coursePrice').value = '0';
    document.getElementById('courseIsFree').value = '1';
    document.getElementById('courseCoverImage').value = '';
    document.getElementById('courseDurationHours').value = '0';
    document.getElementById('courseIsActive').value = '1';
    document.getElementById('courseIsFeatured').value = '0';
    
    // Reset Preview
    document.getElementById('courseImagePreview').src = '';
    document.getElementById('courseImagePreview').classList.add('hidden');
    document.getElementById('imagePlaceholderIcon').classList.remove('hidden');
    document.getElementById('uploadStatus').classList.add('hidden');
    document.getElementById('uploadBtnText').textContent = 'Select Image';
    
    document.getElementById('courseModal').classList.remove('hidden');
}
function editCourse(course) {
    document.getElementById('courseModalTitle').textContent = 'Edit Course';
    document.getElementById('courseAction').value = 'edit_course';
    document.getElementById('courseId').value = course.id;
    document.getElementById('courseTitle').value = course.title;
    document.getElementById('courseDesc').value = course.description || '';
    document.getElementById('courseShortDesc').value = course.short_description || '';
    document.getElementById('courseCategory').value = course.category_id || '';
    document.getElementById('courseParent').value = course.parent_course_id || '';
    document.getElementById('courseType').value = course.course_type;
    document.getElementById('courseDifficulty').value = course.difficulty;
    document.getElementById('coursePrice').value = course.price;
    document.getElementById('courseIsFree').value = course.is_free;
    document.getElementById('courseCoverImage').value = course.cover_image || '';
    document.getElementById('courseDurationHours').value = course.duration_hours;
    document.getElementById('courseIsActive').value = course.is_active;
    document.getElementById('courseIsFeatured').value = course.is_featured;

    // Handle Preview
    const preview = document.getElementById('courseImagePreview');
    const icon = document.getElementById('imagePlaceholderIcon');
    if (course.cover_image) {
        preview.src = course.cover_image;
        preview.classList.remove('hidden');
        icon.classList.add('hidden');
        document.getElementById('uploadBtnText').textContent = 'Change Image';
    } else {
        preview.src = '';
        preview.classList.add('hidden');
        icon.classList.remove('hidden');
        document.getElementById('uploadBtnText').textContent = 'Select Image';
    }
    document.getElementById('uploadStatus').classList.add('hidden');

    document.getElementById('courseModal').classList.remove('hidden');
}
function closeModal() { document.getElementById('courseModal').classList.add('hidden'); }
function deleteCourse(id) {
    confirmAction('Delete this course? Children will be re-assigned to the parent course.', () => {
        document.getElementById('deleteCourseId').value = id;
        document.getElementById('deleteCourseForm').submit();
    });
}
</script>
