<?php
// Suppliers Management - Manage supplier information
require_once __DIR__ . '/includes/db.php';
requireAuth(['admin', 'manager', 'staff']);

$action = $_GET['action'] ?? 'list';
$supplier_id = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name']);
        $contact_person = trim($_POST['contact_person']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $state = trim($_POST['state']);
        $zip_code = trim($_POST['zip_code']);
        $country = trim($_POST['country']);
        $website = trim($_POST['website']);
        $notes = trim($_POST['notes']);

        // Validate required fields
        if (empty($name)) {
            $error = "Supplier name is required.";
        } else {
            $pdo = getPdo();
            if (!$pdo) {
                $error = "Database connection failed.";
            } else {
                try {
                    if ($action === 'add') {
                        $stmt = $pdo->prepare("
                            INSERT INTO inventory_suppliers (name, contact_person, email, phone, address, city, state, zip_code, country, website, notes, created_by)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$name, $contact_person, $email, $phone, $address, $city, $state, $zip_code, $country, $website, $notes, $_SESSION['user_id']]);

                        $success = "Supplier added successfully.";
                        header("Location: suppliers.php?action=list");
                        exit;
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE inventory_suppliers
                            SET name = ?, contact_person = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, zip_code = ?, country = ?, website = ?, notes = ?, updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([$name, $contact_person, $email, $phone, $address, $city, $state, $zip_code, $country, $website, $notes, $supplier_id]);

                        $success = "Supplier updated successfully.";
                    }
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $error = "A supplier with this email already exists.";
                    } else {
                        $error = "Error saving supplier: " . $e->getMessage();
                    }
                }
            }
        }
    } elseif ($action === 'delete' && $supplier_id) {
        $pdo = getPdo();
        if (!$pdo) {
            $error = "Database connection failed.";
        } else {
            try {
                // Check if supplier has items
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_items WHERE supplier_id = ?");
                $stmt->execute([$supplier_id]);
                $has_items = $stmt->fetchColumn();

                if ($has_items > 0) {
                    // Soft delete by marking as inactive
                    $stmt = $pdo->prepare("UPDATE inventory_suppliers SET is_active = 0 WHERE id = ?");
                    $stmt->execute([$supplier_id]);
                    $success = "Supplier has been deactivated (preserved due to existing items).";
                } else {
                    // Hard delete
                    $stmt = $pdo->prepare("DELETE FROM inventory_suppliers WHERE id = ?");
                    $stmt->execute([$supplier_id]);
                    $success = "Supplier has been permanently deleted.";
                }

                header("Location: suppliers.php?action=list");
                exit;
            } catch (PDOException $e) {
                $error = "Error deleting supplier: " . $e->getMessage();
            }
        }
    }
}

// Get supplier data for editing
$supplier = null;
$pdo = getPdo();
if (($action === 'edit' || $action === 'view') && $supplier_id) {
    if ($pdo) {
        $stmt = $pdo->prepare("SELECT * FROM inventory_suppliers WHERE id = ?");
        $stmt->execute([$supplier_id]);
        $supplier = $stmt->fetch();
    }

    if (!$supplier) {
        $error = "Supplier not found.";
        $action = 'list';
    }
}
?>
<!doctype html>
<html lang="en" class="">
<head>
    <script>
      (function() {
        const theme = localStorage.getItem('theme') || 'light';
        document.documentElement.classList.toggle('dark', theme === 'dark');
      })();
    </script>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Suppliers Management - Core 1 Hotel Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="./public/css/tokens.css" />
    <meta http-equiv="X-Content-Type-Options" content="nosniff" />
    <meta http-equiv="X-Frame-Options" content="DENY" />
    <meta http-equiv="X-XSS-Protection" content="1; mode=block" />
</head>
<body class="min-h-screen bg-background">
    <?php require_once __DIR__ . '/includes/db.php'; ?>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <main class="container mx-auto px-4 py-6 space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold">Suppliers Management</h1>
                <p class="text-muted-foreground"><?php echo date('l, F j, Y'); ?></p>
            </div>
            <div class="flex gap-2">
                <?php if ($action === 'list'): ?>
                    <a href="inventory.php" class="inline-flex items-center gap-2 px-4 py-2 bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/90">
                        <i data-lucide="arrow-left"></i>
                        Back to Dashboard
                    </a>
                    <button onclick="showAddModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">
                        <i data-lucide="plus"></i>
                        Add Supplier
                    </button>
                <?php else: ?>
                    <a href="suppliers.php" class="inline-flex items-center gap-2 px-4 py-2 bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/90">
                        <i data-lucide="arrow-left"></i>
                        Back to List
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-md">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-md">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <!-- Suppliers List -->
            <div class="bg-card rounded-lg border">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-semibold">All Suppliers</h2>
                </div>
                <div class="p-6">
                    <div class="mb-4">
                        <input type="text" id="search" placeholder="Search suppliers..." class="w-full px-3 py-2 border rounded-md">
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left p-3">Supplier</th>
                                    <th class="text-left p-3">Contact</th>
                                    <th class="text-left p-3">Email</th>
                                    <th class="text-left p-3">Phone</th>
                                    <th class="text-left p-3">Location</th>
                                    <th class="text-center p-3">Items</th>
                                    <th class="text-center p-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="suppliers-table">
                                <?php
                                $pdo = getPdo();
                                $suppliers_list = [];
                                if ($pdo) {
                                    $suppliers_list = $pdo->query("
                                        SELECT s.*,
                                               COUNT(i.id) as item_count,
                                               GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ', ') as categories
                                        FROM inventory_suppliers s
                                        LEFT JOIN inventory_items i ON s.id = i.supplier_id AND i.is_active = 1
                                        LEFT JOIN inventory_categories c ON i.category_id = c.id
                                        WHERE s.is_active = 1
                                        GROUP BY s.id
                                        ORDER BY s.name
                                    ")->fetchAll();
                                }

                                foreach ($suppliers_list as $supplier):
                                ?>
                                    <tr class="border-b hover:bg-muted/50">
                                        <td class="p-3">
                                            <div>
                                                <div class="font-medium"><?php echo htmlspecialchars($supplier['name']); ?></div>
                                                <?php if ($supplier['website']): ?>
                                                    <div class="text-sm text-muted-foreground">
                                                        <a href="<?php echo htmlspecialchars($supplier['website']); ?>" target="_blank" class="text-primary hover:underline">
                                                            <?php echo htmlspecialchars($supplier['website']); ?>
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="p-3">
                                            <?php if ($supplier['contact_person']): ?>
                                                <div class="font-medium"><?php echo htmlspecialchars($supplier['contact_person']); ?></div>
                                            <?php else: ?>
                                                <span class="text-muted-foreground">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3">
                                            <?php if ($supplier['email']): ?>
                                                <a href="mailto:<?php echo htmlspecialchars($supplier['email']); ?>" class="text-primary hover:underline">
                                                    <?php echo htmlspecialchars($supplier['email']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted-foreground">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3">
                                            <?php if ($supplier['phone']): ?>
                                                <a href="tel:<?php echo htmlspecialchars($supplier['phone']); ?>" class="text-primary hover:underline">
                                                    <?php echo htmlspecialchars($supplier['phone']); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted-foreground">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3">
                                            <?php if ($supplier['city'] && $supplier['state']): ?>
                                                <div><?php echo htmlspecialchars($supplier['city']); ?>, <?php echo htmlspecialchars($supplier['state']); ?></div>
                                                <?php if ($supplier['country']): ?>
                                                    <div class="text-sm text-muted-foreground"><?php echo htmlspecialchars($supplier['country']); ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted-foreground">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="px-2 py-1 bg-secondary text-secondary-foreground rounded text-sm">
                                                <?php echo $supplier['item_count']; ?> items
                                            </span>
                                        </td>
                                        <td class="p-3 text-center">
                                            <div class="flex justify-center gap-2">
                                                <button onclick="editSupplier(<?php echo $supplier['id']; ?>)" class="px-2 py-1 text-blue-600 hover:bg-blue-50 rounded">
                                                    <i data-lucide="edit"></i>
                                                </button>
                                                <button onclick="deleteSupplier(<?php echo $supplier['id']; ?>)" class="px-2 py-1 text-red-600 hover:bg-red-50 rounded">
                                                    <i data-lucide="trash-2"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Add Supplier Modal -->
            <div id="add-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-card p-6 rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="add">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-bold">Add New Supplier</h2>
                            <button type="button" onclick="hideAddModal()" class="text-muted-foreground hover:text-foreground">
                                <i data-lucide="x" class="h-6 w-6"></i>
                            </button>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Supplier Name *</label>
                            <input type="text" name="name" required class="w-full px-3 py-2 border rounded-md">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Contact Person</label>
                                <input type="text" name="contact_person" class="w-full px-3 py-2 border rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Email</label>
                                <input type="email" name="email" class="w-full px-3 py-2 border rounded-md">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Phone</label>
                            <input type="tel" name="phone" class="w-full px-3 py-2 border rounded-md">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Address</label>
                            <textarea name="address" rows="3" class="w-full px-3 py-2 border rounded-md"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">City</label>
                                <input type="text" name="city" class="w-full px-3 py-2 border rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">State/Province</label>
                                <input type="text" name="state" class="w-full px-3 py-2 border rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">ZIP/Postal Code</label>
                                <input type="text" name="zip_code" class="w-full px-3 py-2 border rounded-md">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Country</label>
                            <input type="text" name="country" class="w-full px-3 py-2 border rounded-md">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Website</label>
                            <input type="url" name="website" placeholder="https://" class="w-full px-3 py-2 border rounded-md">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Notes</label>
                            <textarea name="notes" rows="3" class="w-full px-3 py-2 border rounded-md"></textarea>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" onclick="hideAddModal()" class="px-4 py-2 border rounded-md hover:bg-muted">Cancel</button>
                            <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">Add Supplier</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($action === 'edit' && $supplier): ?>
            <!-- Edit Supplier Form -->
            <div class="bg-card rounded-lg border">
                <div class="p-6 border-b">
                    <h2 class="text-xl font-bold">Edit Supplier: <?php echo htmlspecialchars($supplier['name']); ?></h2>
                </div>
                <div class="p-6">
                    <form method="POST">
                        <input type="hidden" name="action" value="edit">

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Supplier Name *</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($supplier['name']); ?>" required class="w-full px-3 py-2 border rounded-md">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Contact Person</label>
                                <input type="text" name="contact_person" value="<?php echo htmlspecialchars($supplier['contact_person']); ?>" class="w-full px-3 py-2 border rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Email</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($supplier['email']); ?>" class="w-full px-3 py-2 border rounded-md">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Phone</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($supplier['phone']); ?>" class="w-full px-3 py-2 border rounded-md">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Address</label>
                            <textarea name="address" rows="3" class="w-full px-3 py-2 border rounded-md"><?php echo htmlspecialchars($supplier['address']); ?></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">City</label>
                                <input type="text" name="city" value="<?php echo htmlspecialchars($supplier['city']); ?>" class="w-full px-3 py-2 border rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">State/Province</label>
                                <input type="text" name="state" value="<?php echo htmlspecialchars($supplier['state']); ?>" class="w-full px-3 py-2 border rounded-md">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">ZIP/Postal Code</label>
                                <input type="text" name="zip_code" value="<?php echo htmlspecialchars($supplier['zip_code']); ?>" class="w-full px-3 py-2 border rounded-md">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Country</label>
                            <input type="text" name="country" value="<?php echo htmlspecialchars($supplier['country']); ?>" class="w-full px-3 py-2 border rounded-md">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Website</label>
                            <input type="url" name="website" value="<?php echo htmlspecialchars($supplier['website']); ?>" placeholder="https://" class="w-full px-3 py-2 border rounded-md">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Notes</label>
                            <textarea name="notes" rows="3" class="w-full px-3 py-2 border rounded-md"><?php echo htmlspecialchars($supplier['notes']); ?></textarea>
                        </div>

                        <div class="flex justify-end gap-2">
                            <a href="suppliers.php" class="px-4 py-2 border rounded-md hover:bg-muted">Cancel</a>
                            <button type="submit" class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90">Update Supplier</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Supplier Items Section -->
            <div class="bg-card rounded-lg border">
                <div class="p-6 border-b">
                    <h2 class="text-lg font-semibold">Items from <?php echo htmlspecialchars($supplier['name']); ?></h2>
                </div>
                <div class="p-6">
                    <?php
                    $pdo = getPdo();
                    $supplier_items = [];
                    if ($pdo && $supplier_id) {
                        $stmt = $pdo->prepare("
                            SELECT i.name, i.sku, i.unit_cost, s.current_stock, c.name as category_name
                            FROM inventory_items i
                            JOIN inventory_stock s ON i.id = s.item_id
                            JOIN inventory_categories c ON i.category_id = c.id
                            WHERE i.supplier_id = ? AND i.is_active = 1
                            ORDER BY i.name
                        ");
                        $stmt->execute([$supplier_id]);
                        $supplier_items = $stmt->fetchAll();
                    }
                    ?>

                    <?php if (empty($supplier_items)): ?>
                        <p class="text-muted-foreground text-center py-4">No items found for this supplier</p>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="border-b">
                                        <th class="text-left p-3">Item</th>
                                        <th class="text-left p-3">SKU</th>
                                        <th class="text-left p-3">Category</th>
                                        <th class="text-right p-3">Stock</th>
                                        <th class="text-right p-3">Unit Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($supplier_items as $item): ?>
                                        <tr class="border-b hover:bg-muted/50">
                                            <td class="p-3">
                                                <div class="font-medium"><?php echo htmlspecialchars($item['name']); ?></div>
                                            </td>
                                            <td class="p-3">
                                                <span class="font-mono text-sm"><?php echo htmlspecialchars($item['sku']); ?></span>
                                            </td>
                                            <td class="p-3">
                                                <span class="px-2 py-1 bg-secondary text-secondary-foreground rounded text-sm"><?php echo htmlspecialchars($item['category_name']); ?></span>
                                            </td>
                                            <td class="p-3 text-right">
                                                <?php echo number_format($item['current_stock']); ?>
                                            </td>
                                            <td class="p-3 text-right">
                                                $<?php echo number_format($item['unit_cost'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        window.lucide && window.lucide.createIcons();

        function showAddModal() {
            document.getElementById('add-modal').classList.remove('hidden');
        }

        function hideAddModal() {
            document.getElementById('add-modal').classList.add('hidden');
        }

        function editSupplier(id) {
            window.location.href = `suppliers.php?action=edit&id=${id}`;
        }

        function deleteSupplier(id) {
            if (confirm('Are you sure you want to delete this supplier?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${id}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Search functionality
        document.getElementById('search')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#suppliers-table tr');

            rows.forEach(row => {
                if (row.cells && row.cells[0]) {
                    const text = row.cells[0].textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                }
            });
        });
    </script>
    
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
      window.lucide && window.lucide.createIcons();
    </script>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
