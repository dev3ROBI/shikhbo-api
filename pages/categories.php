<?php
$mysqli = getDBConnection();

// ── Handle CRUD (unchanged) ──
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
            $stmt->execute() ? $success = "Category added." : $error = $stmt->error; $stmt->close();
        } elseif ($action === 'edit_category') {
            $catId=intval($_POST['category_id']); $name=sanitize($_POST['name']);
            $parentId=$_POST['parent_id'] ? intval($_POST['parent_id']) : null;
            $type=sanitize($_POST['category_type']??'academic'); $active=intval($_POST['is_active']??1);
            $level=$parentId ? ($mysqli->query("SELECT level FROM exam_categories WHERE id=$parentId")->fetch_assoc()['level']+1):1;
            $stmt=$mysqli->prepare("UPDATE exam_categories SET name=?,parent_id=?,level=?,category_type=?,is_active=? WHERE id=?");
            $stmt->bind_param('siisii', $name, $parentId, $level, $type, $active, $catId);
            $stmt->execute() ? $success="Category updated." : $error=$stmt->error; $stmt->close();
        } elseif ($action === 'delete_category') {
            $catId=intval($_POST['category_id']);
            $parent=$mysqli->query("SELECT parent_id FROM exam_categories WHERE id=$catId")->fetch_assoc();
            $np=$parent['parent_id']??null;
            if($np) $mysqli->query("UPDATE exam_categories SET parent_id=$np WHERE parent_id=$catId");
            else $mysqli->query("UPDATE exam_categories SET parent_id=NULL WHERE parent_id=$catId");
            $stmt=$mysqli->prepare("DELETE FROM exam_categories WHERE id=?"); $stmt->bind_param('i',$catId);
            $stmt->execute() ? $success="Category deleted." : $error=$stmt->error; $stmt->close();
        }
    }
}

$allCats=$mysqli->query("SELECT * FROM exam_categories ORDER BY parent_id,sort_order,id");
$catsById=[]; while($c=$allCats->fetch_assoc()){$catsById[$c['id']]=$c;}
function buildTree($cats,$p=null){$t=[];foreach($cats as $id=>$c){if($c['parent_id']==$p){$c['children']=buildTree($cats,$id);$t[]=$c;}}return $t;}
$tree=buildTree($catsById);

function renderRows($tree,$level=0){
    $h='';$tc=['academic'=>'blue','job'=>'green','general'=>'purple','other'=>'gray'];
    foreach($tree as $c){
        $ind=str_repeat('&nbsp;&nbsp;&nbsp;',min($level,3));
        $clr=$tc[$c['category_type']]??'gray';$ch=!empty($c['children']);
        $h.="<tr class='hover:bg-gray-50 border-b border-gray-100'><td class='px-3 py-2.5 text-sm'><span class='inline-flex items-center min-w-0'>{$ind}<i class='fa-solid fa-folder-tree text-{$clr}-400 mr-1.5 text-xs flex-shrink-0'></i><span class='font-medium text-gray-800 truncate'>".sanitizeOutput($c['name'])."</span>".($ch?"<span class='text-xs text-gray-400 ml-1'>(+".count($c['children']).")</span>":"")."</span></td>";
        $h.="<td class='px-3 py-2.5 text-xs text-gray-500 hidden sm:table-cell'>Lvl {$c['level']}</td>";
        $h.="<td class='px-3 py-2.5 hidden sm:table-cell'><span class='px-1.5 py-0.5 text-[10px] rounded-full bg-{$clr}-100 text-{$clr}-700'>{$c['category_type']}</span></td>";
        $h.="<td class='px-3 py-2.5 hidden sm:table-cell'>".($c['is_active']?"<span class='text-green-600 text-xs'>Active</span>":"<span class='text-red-500 text-xs'>Inactive</span>")."</td>";
        $h.="<td class='px-3 py-2.5 text-xs space-x-1.5'><button onclick='editCategory(".htmlspecialchars(json_encode($c),ENT_QUOTES,'UTF-8').")' class='text-shikhbo-primary hover:underline'><i class='fa-solid fa-pen-to-square'></i></button><button onclick='deleteCategory({$c['id']})' class='text-red-600 hover:underline'><i class='fa-solid fa-trash'></i></button></td></tr>";
        if($ch) $h.=renderRows($c['children'],$level+1);
    }
    return $h;
}
?>

<?php if(isset($error)):?>
<div
    class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm"><?php echo sanitizeOutput($error);?></div><?php endif;?>
<?php if(isset($success)):?>
<div
    class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm"><?php echo sanitizeOutput($success);?></div><?php endif;?>

<div
    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">Exam Categories</h1>
        <p class="text-gray-500 text-sm mt-1"><?php echo count($catsById);?>
            categories</p>
    </div>
    <button
        onclick="openAddModal()"
        class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm hover:bg-indigo-700 transition-colors flex-shrink-0">
        <i class="fa-solid fa-plus mr-1.5"></i>Add Category</button>
</div>

<div
    class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                    <th
                        class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">Level</th>
                    <th
                        class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">Type</th>
                    <th
                        class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase hidden sm:table-cell">Status</th>
                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100"><?php echo renderRows($tree);?></tbody>
        </table>
    </div>
</div>

<!-- Modal (unchanged) -->
<div
    id="categoryModal"
    class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeModal()"></div>
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold" id="catModalTitle">Add Category</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-xmark text-xl"></i>
            </button>
        </div>
        <form method="POST" class="space-y-4" id="catForm">
            <?php echo getCSRFTokenField();?>
            <input type="hidden" name="action" id="catAction" value="add_category"><input type="hidden" name="category_id" id="catId">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Name</label><input
                    type="text"
                    name="name"
                    id="catName"
                    required="required"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-shikhbo-primary outline-none"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Parent</label>
                <select
                    name="parent_id"
                    id="catParent"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Root Category</option><?php $parents=$mysqli->query("SELECT id,name,level FROM exam_categories ORDER BY level,id");while($p=$parents->fetch_assoc()):?>
                    <option value="<?php echo $p['id'];?>"><?php echo str_repeat('— ',max(0,$p['level']-1)).sanitizeOutput($p['name']);?></option><?php endwhile;?></select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select
                    name="category_type"
                    id="catType"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="academic">Academic</option>
                    <option value="job">Job</option>
                    <option value="general">General</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div id="catActiveGroup">
                <label class="block text-sm font-medium text-gray-700 mb-1">Active</label>
                <select
                    name="is_active"
                    id="catActive"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div class="flex justify-end space-x-3">
                <button
                    type="button"
                    onclick="closeModal()"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm">Cancel</button>
                <button
                    type="submit"
                    class="px-4 py-2 bg-shikhbo-primary text-white rounded-lg text-sm">Save</button>
            </div>
        </form>
    </div>
</div>
<form id="deleteCatForm" method="POST" style="display:none;"><?php echo getCSRFTokenField();?><input type="hidden" name="action" value="delete_category"><input type="hidden" name="category_id" id="deleteCatId"></form>

<script>
    function openAddModal() {
        document
            .getElementById('catModalTitle')
            .textContent = 'Add Category';
        document
            .getElementById('catAction')
            .value = 'add_category';
        document
            .getElementById('catId')
            .value = '';
        document
            .getElementById('catName')
            .value = '';
        document
            .getElementById('catParent')
            .value = '';
        document
            .getElementById('catType')
            .value = 'academic';
        document
            .getElementById('catActive')
            .value = '1';
        document
            .getElementById('catActiveGroup')
            .style
            .display = 'none';
        document
            .getElementById('categoryModal')
            .classList
            .remove('hidden');
    }
    function editCategory(cat) {
        document
            .getElementById('catModalTitle')
            .textContent = 'Edit Category';
        document
            .getElementById('catAction')
            .value = 'edit_category';
        document
            .getElementById('catId')
            .value = cat.id;
        document
            .getElementById('catName')
            .value = cat.name;
        document
            .getElementById('catParent')
            .value = cat.parent_id || '';
        document
            .getElementById('catType')
            .value = cat.category_type;
        document
            .getElementById('catActive')
            .value = cat.is_active;
        document
            .getElementById('catActiveGroup')
            .style
            .display = 'block';
        document
            .getElementById('categoryModal')
            .classList
            .remove('hidden');
    }
    function closeModal() {
        document
            .getElementById('categoryModal')
            .classList
            .add('hidden');
    }
    function deleteCategory(id) {
        if (confirm('Delete category? Children moved up.')) {
            document
                .getElementById('deleteCatId')
                .value = id;
            document
                .getElementById('deleteCatForm')
                .submit();
        }
    }
</script>