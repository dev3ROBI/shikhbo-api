<?php
$mysqli = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) { $error = 'Security token validation failed.'; }
    else {
        $action = $_POST['action'] ?? '';
        if ($action === 'add_category') {
            $name = sanitize($_POST['name']); $parentId = $_POST['parent_id'] ? intval($_POST['parent_id']) : null;
            $type = sanitize($_POST['category_type'] ?? 'academic');
            $level = $parentId ? ($mysqli->query("SELECT level FROM exam_categories WHERE id=$parentId")->fetch_assoc()['level'] + 1) : 1;
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name)) . '-' . substr(uniqid(), -4);
            $stmt = $mysqli->prepare("INSERT INTO exam_categories (name,slug,parent_id,level,category_type) VALUES (?,?,?,?,?)");
            $stmt->bind_param('ssiis', $name, $slug, $parentId, $level, $type);
            $stmt->execute() ? $success = "Category added successfully." : $error = $stmt->error; $stmt->close();
        } elseif ($action === 'edit_category') {
            $catId=intval($_POST['category_id']); $name=sanitize($_POST['name']);
            $parentId=$_POST['parent_id'] ? intval($_POST['parent_id']) : null;
            $type=sanitize($_POST['category_type']??'academic'); $active=intval($_POST['is_active']??1);
            $level=$parentId ? ($mysqli->query("SELECT level FROM exam_categories WHERE id=$parentId")->fetch_assoc()['level']+1):1;
            $stmt=$mysqli->prepare("UPDATE exam_categories SET name=?,parent_id=?,level=?,category_type=?,is_active=? WHERE id=?");
            $stmt->bind_param('siisii', $name, $parentId, $level, $type, $active, $catId);
            $stmt->execute() ? $success="Category updated successfully." : $error=$stmt->error; $stmt->close();
        } elseif ($action === 'delete_category') {
            $catId=intval($_POST['category_id']);
            $parent=$mysqli->query("SELECT parent_id FROM exam_categories WHERE id=$catId")->fetch_assoc();
            $np=$parent['parent_id']??null;
            if($np) $mysqli->query("UPDATE exam_categories SET parent_id=$np WHERE parent_id=$catId");
            else $mysqli->query("UPDATE exam_categories SET parent_id=NULL WHERE parent_id=$catId");
            $stmt=$mysqli->prepare("DELETE FROM exam_categories WHERE id=?"); $stmt->bind_param('i',$catId);
            $stmt->execute() ? $success="Category deleted successfully." : $error=$stmt->error; $stmt->close();
        }
    }
}

$allCats=$mysqli->query("SELECT * FROM exam_categories ORDER BY parent_id,sort_order,id");
$catsById=[]; while($c=$allCats->fetch_assoc()){$catsById[$c['id']]=$c;}
function buildTree($cats,$p=null){$t=[];foreach($cats as $id=>$c){if($c['parent_id']==$p){$c['children']=buildTree($cats,$id);$t[]=$c;}}return $t;}
$tree=buildTree($catsById);

function renderRows($tree,$level=0){
    $h='';$tc=['academic'=>'blue','job'=>'emerald','general'=>'purple','other'=>'gray'];
    foreach($tree as $c){
        $ind=str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;',min($level,3));
        $clr=$tc[$c['category_type']]??'gray';$ch=!empty($c['children']);
        $h.="<tr class='table-row'>
            <td class='px-4 py-3.5'>
                <span class='inline-flex items-center gap-2'>
                    {$ind}<div class='w-8 h-8 rounded-lg bg-{$clr}-100 dark:bg-{$clr}-900/30 flex items-center justify-center flex-shrink-0'>
                        <i class='fa-solid fa-folder text-{$clr}-600 dark:text-{$clr}-400 text-sm'></i>
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
        <div class="overflow-x-auto">
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
        <div class="modal-content bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-md pointer-events-auto">
            <div class="flex items-center justify-between p-6 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100" id="catModalTitle">Add Category</h3>
                <button onclick="closeModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form method="POST" class="p-6 space-y-4" id="catForm">
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
                        <?php $parents=$mysqli->query("SELECT id,name,level FROM exam_categories ORDER BY level,id");
                        while($p=$parents->fetch_assoc()):?>
                        <option value="<?php echo $p['id'];?>"><?php echo str_repeat('— ',max(0,$p['level']-1)).sanitizeOutput($p['name']);?></option>
                        <?php endwhile;?>
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
                <div id="catActiveGroup">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Active</label>
                    <select name="is_active" id="catActive" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="closeModal()" class="px-5 py-2.5 border border-gray-300 dark:border-gray-600 rounded-xl text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition-colors">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<form id="deleteCatForm" method="POST" class="hidden"><?php echo getCSRFTokenField();?><input type="hidden" name="action" value="delete_category"><input type="hidden" name="category_id" id="deleteCatId"></form>

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