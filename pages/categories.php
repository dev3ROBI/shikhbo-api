<?php
$mysqli = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { $error = 'Security token validation failed.'; }
    else {
        $action = $_POST['action'] ?? '';
        if ($action === 'add_category') {
            $name = sanitize($_POST['name']);
            $parentId = $_POST['parent_id'] ? intval($_POST['parent_id']) : null;
            $type = sanitize($_POST['category_type'] ?? 'academic');
            $icon = $_POST['icon'] ?? '';
            if (empty($icon) && !empty($_POST['icon_custom'])) $icon = sanitize($_POST['icon_custom']);
            else $icon = sanitize($icon);
            $desc = sanitize($_POST['description'] ?? '');
            $level = 1;
            if ($parentId) { $r = $mysqli->query("SELECT level FROM exam_categories WHERE id=$parentId"); $p = $r ? $r->fetch_assoc() : null; if ($p) $level = $p['level'] + 1; }
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)) . '-' . substr(uniqid(), -4);
            $stmt = $mysqli->prepare("INSERT INTO exam_categories (name,slug,parent_id,level,category_type,icon,description) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssss', $name, $slug, $parentId, $level, $type, $icon, $desc);
            $stmt->execute() ? $success = "Category added successfully." : $error = $stmt->error; $stmt->close();
        } elseif ($action === 'edit_category') {
            $catId=intval($_POST['category_id']); $name=sanitize($_POST['name']);
            $parentId=$_POST['parent_id'] ? intval($_POST['parent_id']) : null;
            $type=sanitize($_POST['category_type']??'academic'); $active=intval($_POST['is_active']??1);
            $icon = $_POST['icon'] ?? '';
            if (empty($icon) && !empty($_POST['icon_custom'])) $icon = sanitize($_POST['icon_custom']);
            else $icon = sanitize($icon);
            $desc=sanitize($_POST['description']??'');
            $level=1;
            if ($parentId) { $r = $mysqli->query("SELECT level FROM exam_categories WHERE id=$parentId"); $p = $r ? $r->fetch_assoc() : null; if ($p) $level = $p['level'] + 1; }
            $stmt=$mysqli->prepare("UPDATE exam_categories SET name=?,parent_id=?,level=?,category_type=?,is_active=?,icon=?,description=? WHERE id=?");
            $stmt->bind_param('sssssssi', $name, $parentId, $level, $type, $active, $icon, $desc, $catId);
            $stmt->execute() ? $success="Category updated successfully." : $error=$stmt->error; $stmt->close();
        } elseif ($action === 'delete_category') {
            $catId=intval($_POST['category_id']);
            $r=$mysqli->query("SELECT parent_id FROM exam_categories WHERE id=$catId");
            $parent=$r?$r->fetch_assoc():null;
            $np=$parent?($parent['parent_id']??null):null;
            if ($np) { $s=$mysqli->prepare("UPDATE exam_categories SET parent_id=? WHERE parent_id=?"); $s->bind_param('ii',$np,$catId); $s->execute(); $s->close(); }
            else { $s=$mysqli->prepare("UPDATE exam_categories SET parent_id=NULL WHERE parent_id=?"); $s->bind_param('i',$catId); $s->execute(); $s->close(); }
            $stmt=$mysqli->prepare("DELETE FROM exam_categories WHERE id=?"); $stmt->bind_param('i',$catId);
            $stmt->execute() ? $success="Category deleted successfully." : $error=$stmt->error; $stmt->close();
        }
    }
}

$allCats=$mysqli->query("SELECT * FROM exam_categories ORDER BY parent_id,sort_order,id");
$catsById=[]; while($c=$allCats->fetch_assoc()){$catsById[$c['id']]=$c;}
function buildTree($cats,$p=null){$t=[];foreach($cats as $id=>$c){if($c['parent_id']==$p){$c['children']=buildTree($cats,$id);$t[]=$c;}}return $t;}
$tree=buildTree($catsById);

// Build breadcrumb paths for parent select
function buildCatBreadcrumb($id, $cats, $depth = 0) {
    if ($depth > 20 || !isset($cats[$id])) return '';
    $p = $cats[$id]['parent_id'];
    $n = $cats[$id]['name'];
    return ($p && isset($cats[$p])) ? buildCatBreadcrumb($p, $cats, $depth + 1) . ' → ' . $n : $n;
}
$parentCatOptions = '';
foreach ($catsById as $id => $c) {
    $path = buildCatBreadcrumb($id, $catsById);
    $parentCatOptions .= "<option value='{$id}'>" . sanitizeOutput($path) . "</option>";
}

$iconOptions = [
    ['value' => 'fa-graduation-cap', 'label' => 'Graduation (Academic)', 'icon' => 'graduation-cap'],
    ['value' => 'fa-briefcase', 'label' => 'Briefcase (Job)', 'icon' => 'briefcase'],
    ['value' => 'fa-book', 'label' => 'Book (General)', 'icon' => 'book'],
    ['value' => 'fa-flask', 'label' => 'Flask (Science)', 'icon' => 'flask'],
    ['value' => 'fa-calculator', 'label' => 'Calculator (Math)', 'icon' => 'calculator'],
    ['value' => 'fa-language', 'label' => 'Language', 'icon' => 'language'],
    ['value' => 'fa-laptop-code', 'label' => 'Laptop (Computer)', 'icon' => 'laptop-code'],
    ['value' => 'fa-pencil-alt', 'label' => 'Pencil (Writing)', 'icon' => 'pencil-alt'],
];

function renderRows($tree,$level=0){
    $h='';$tc=['academic'=>'blue','job'=>'emerald','general'=>'purple','other'=>'gray'];
    foreach($tree as $c){
        $ind=str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;',min($level,3));
        $clr=$tc[$c['category_type']]??'gray';$ch=!empty($c['children']);
        $iconHtml = $c['icon'] ? "<i class='fa-solid {$c['icon']} text-{$clr}-600 dark:text-{$clr}-400 text-sm'></i>" : "<i class='fa-solid fa-folder text-{$clr}-600 dark:text-{$clr}-400 text-sm'></i>";
        $h.="<tr class='table-row'>
            <td class='px-4 py-3.5'>
                <span class='inline-flex items-center gap-2'>
                    {$ind}<div class='w-8 h-8 rounded-lg bg-{$clr}-100 dark:bg-{$clr}-900/30 flex items-center justify-center flex-shrink-0'>
                        {$iconHtml}
                    </div>
                    <span class='font-medium text-gray-800 dark:text-gray-100'>".sanitizeOutput($c['name'])."</span>
                    ".($ch?"<span class='text-xs text-gray-400 dark:text-gray-500'>(+".count($c['children']).")</span>":"")."
                </span>
            </td>
            <td class='px-4 py-3.5 hide-mobile'><span class='badge bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'>Lvl {$c['level']}</span></td>
            <td class='px-4 py-3.5 hide-mobile'><span class='badge bg-{$clr}-100 text-{$clr}-700 dark:bg-{$clr}-900/30 dark:text-{$clr}-400'>{$c['category_type']}</span></td>
            <td class='px-4 py-3.5 hide-mobile'>".($c['is_active']?"<span class='badge bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'><i class='fa-solid fa-check mr-1'></i>Active</span>":"<span class='badge bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'><i class='fa-solid fa-xmark mr-1'></i>Inactive</span>")."</td>
            <td class='px-4 py-3.5'>
                <div class='flex items-center gap-1'>
                    <button onclick='editCategory(".htmlspecialchars(json_encode($c),ENT_QUOTES,'UTF-8').")' class='p-2 text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg transition-colors' title='Edit'><i class='fa-solid fa-pen-to-square'></i></button>
                    <button onclick='deleteCategory({$c['id']})' class='p-2 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors' title='Delete'><i class='fa-solid fa-trash'></i></button>
                </div>
            </td>
        </tr>";
        if($ch) $h.=renderRows($c['children'],$level+1);
    }
    return $h;
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
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 dark:text-gray-100">Categories</h1>
            <p class="text-gray-500 dark:text-gray-400 mt-1"><?php echo count($catsById);?> categories</p>
        </div>
        <button onclick="openAddModal()" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors flex items-center gap-2 shadow-lg shadow-indigo-200 dark:shadow-indigo-900/30">
            <i class="fa-solid fa-plus"></i>Add Category
        </button>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md overflow-hidden border border-gray-100 dark:border-gray-700">
        <div class="overflow-x-auto table-wrapper">
            <table class="w-full">
                <thead class="table-header">
                    <tr>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Level</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Type</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider hide-mobile">Status</th>
                        <th class="px-4 py-3.5 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-700">
                    <?php echo renderRows($tree);?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="categoryModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 modal-backdrop" onclick="closeModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none">
        <div class="modal-content w-full max-w-md pointer-events-auto">
            <div class="modal-header flex items-center justify-between sticky top-0 z-10">
                <h3 class="text-lg font-semibold" id="catModalTitle">Add Category</h3>
                <button onclick="closeModal()" class="p-2 rounded-lg transition-all">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form method="POST" class="modal-body-scroll space-y-4" id="catForm">
                <?php echo getCSRFTokenField();?>
                <input type="hidden" name="action" id="catAction" value="add_category">
                <input type="hidden" name="category_id" id="catId">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Name</label>
                    <input type="text" name="name" id="catName" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Parent</label>
                    <select name="parent_id" id="catParent" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">Root Category</option>
                        <?php echo $parentCatOptions; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Type</label>
                    <select name="category_type" id="catType" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="academic">Academic</option>
                        <option value="job">Job</option>
                        <option value="general">General</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Icon</label>
                    <div class="grid grid-cols-4 gap-2" id="iconPicker">
                        <?php foreach($iconOptions as $opt):
                            $selectedIcon = $opt['value'];
                        ?>
                        <label class="icon-option flex flex-col items-center p-2 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 has-[:checked]:ring-2 has-[:checked]:ring-indigo-500 has-[:checked]:border-indigo-500">
                            <input type="radio" name="icon" value="<?php echo $selectedIcon; ?>" class="hidden icon-radio">
                            <i class="fa-solid <?php echo $selectedIcon; ?> text-xl text-gray-600 dark:text-gray-300"></i>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" name="icon_custom" id="catIconCustom" placeholder="Or type custom FontAwesome class (e.g. fa-heart)" class="w-full px-4 py-2.5 mt-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                    <textarea name="description" id="catDesc" rows="2" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none input-enhanced" placeholder="Brief description of this category"></textarea>
                </div>
                <div id="catActiveGroup">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Active</label>
                    <select name="is_active" id="catActive" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()" class="btn-cancel">Cancel</button>
                    <button type="submit" class="btn-save">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="deleteCatForm" method="POST" class="hidden"><?php echo getCSRFTokenField();?><input type="hidden" name="action" value="delete_category"><input type="hidden" name="category_id" id="deleteCatId"></form>

<style>
.icon-option:has(input[type="radio"]:checked) { border-color: #6366f1; background-color: #eef2ff; }
.dark .icon-option:has(input[type="radio"]:checked) { background-color: #1e1b4b; }
</style>

<script>
function openAddModal() {
    document.getElementById('catModalTitle').textContent = 'Add Category';
    document.getElementById('catAction').value = 'add_category';
    document.getElementById('catId').value = '';
    document.getElementById('catName').value = '';
    document.getElementById('catParent').value = '';
    document.getElementById('catType').value = 'academic';
    document.getElementById('catActive').value = '1';
    document.getElementById('catActiveGroup').style.display = 'none';
    document.querySelectorAll('.icon-radio').forEach(r => r.checked = false);
    document.getElementById('catIconCustom').value = '';
    document.getElementById('catDesc').value = '';
    document.getElementById('categoryModal').classList.remove('hidden');
}
function editCategory(cat) {
    document.getElementById('catModalTitle').textContent = 'Edit Category';
    document.getElementById('catAction').value = 'edit_category';
    document.getElementById('catId').value = cat.id;
    document.getElementById('catName').value = cat.name;
    document.getElementById('catParent').value = cat.parent_id || '';
    document.getElementById('catType').value = cat.category_type;
    document.getElementById('catActive').value = cat.is_active;
    document.getElementById('catActiveGroup').style.display = 'block';
    if (cat.icon) {
        let matched = false;
        document.querySelectorAll('.icon-radio').forEach(r => { if (r.value === cat.icon) { r.checked = true; matched = true; } });
        document.getElementById('catIconCustom').value = matched ? '' : cat.icon;
    } else {
        document.querySelectorAll('.icon-radio').forEach(r => r.checked = false);
        document.getElementById('catIconCustom').value = '';
    }
    document.getElementById('catDesc').value = cat.description || '';
    document.getElementById('categoryModal').classList.remove('hidden');
}
function closeModal() { document.getElementById('categoryModal').classList.add('hidden'); }
function deleteCategory(id) {
    confirmAction('Delete category? Children will be moved up.', () => {
        document.getElementById('deleteCatId').value = id;
        document.getElementById('deleteCatForm').submit();
    });
}
</script>
