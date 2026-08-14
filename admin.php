<?php
session_start();
require 'config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: index.php");
    exit();
}

// Handle action operations (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_property'])) {
        $id = $_POST['property_id'];
        $pdo->prepare("DELETE FROM properties WHERE id = ?")->execute([$id]);
        header("Location: admin.php#properties");
        exit();
    }
    if (isset($_POST['approve_property'])) {
        $id = $_POST['property_id'];
        $pdo->prepare("UPDATE properties SET status = 'approved', rejection_reason = NULL WHERE id = ?")->execute([$id]);
        header("Location: admin.php#properties");
        exit();
    }
    if (isset($_POST['reject_property'])) {
        $id = $_POST['property_id'];
        $reason = trim($_POST['rejection_reason'] ?? '');
        $pdo->prepare("UPDATE properties SET status = 'rejected', rejection_reason = ? WHERE id = ?")
            ->execute([$reason !== '' ? $reason : null, $id]);
        header("Location: admin.php#properties");
        exit();
    }
    if (isset($_POST['delete_user'])) {
        $id = $_POST['user_id'];
        if ($id != 1) { // Don't allow deleting admin account
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
            header("Location: admin.php#users");
            exit();
        }
    }
}

// Get statistics & data
$propertyCount = $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalValue = $pdo->query("SELECT SUM(price) FROM properties")->fetchColumn() ?: 0;
$properties = $pdo->query("SELECT * FROM properties ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
$users = $pdo->query("SELECT id, username FROM users")->fetchAll(PDO::FETCH_ASSOC);
$recentProperties = $pdo->query("SELECT * FROM properties ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Get type distribution
$typeDistribution = $pdo->query("
    SELECT type, COUNT(*) as count 
    FROM properties 
    GROUP BY type
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sapanko Ghar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-building"></i> Sapanko Ghar Admin
            </div>
            
            <div class="user-info">
                <p>Logged in as</p>
                <div class="username"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
            </div>

            <nav>
                <a href="#dashboard" class="nav-link active" onclick="switchTab(event, 'dashboard')">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="#properties" class="nav-link" onclick="switchTab(event, 'properties')">
                    <i class="fas fa-key"></i> Properties
                </a>
                <a href="#users" class="nav-link" onclick="switchTab(event, 'users')">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="#analytics" class="nav-link" onclick="switchTab(event, 'analytics')">
                    <i class="fas fa-chart-bar"></i> Analytics
                </a>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>

            <form action="logout.php" method="POST" style="position: absolute; bottom: 0; left: 0; right: 0;">
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="header">
                <div>
                    <h1>Admin Dashboard</h1>
                    <p class="admin-title">Welcome back, Administrator</p>
                </div>
            </div>

            <!-- Dashboard Tab -->
            <div id="dashboard" class="tab-content active">
                <div class="stats-grid">
                    <div class="stat-card properties">
                        <div class="icon"><i class="fas fa-home"></i></div>
                        <div class="label">Total Properties</div>
                        <div class="value"><?php echo $propertyCount; ?></div>
                    </div>
                    <div class="stat-card users">
                        <div class="icon"><i class="fas fa-users"></i></div>
                        <div class="label">Total Users</div>
                        <div class="value"><?php echo $userCount; ?></div>
                    </div>
                </div>

                <!-- Recent Properties -->
                <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 30px;">
                    <h2 style="margin-bottom: 20px; color: #2c3e50;">Recent Properties</h2>
                    <?php if (!empty($recentProperties)): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                            <?php foreach ($recentProperties as $prop): ?>
                                <div style="border: 1px solid #ecf0f1; border-radius: 8px; overflow: hidden; transition: all 0.3s;">
                                    <?php if ($prop['image']): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($prop['image']); ?>" style="width: 100%; height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 200px; background: #ecf0f1; display: flex; align-items: center; justify-content: center; color: #999;">
                                            <i class="fas fa-image" style="font-size: 40px;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div style="padding: 15px;">
                                        <h3 style="color: #2c3e50; margin-bottom: 10px;"><?php echo htmlspecialchars($prop['title']); ?></h3>
                                        <p style="color: #7f8c8d; font-size: 14px; margin-bottom: 8px;">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($prop['location']); ?>
                                        </p>
                                        <p style="color: #27ae60; font-weight: bold; margin-bottom: 8px;">
                                            $<?php echo number_format($prop['price'], 0); ?>
                                        </p>
                                        <span class="badge" style="background: #e8f4f8; color: #3498db;">
                                            <?php echo htmlspecialchars($prop['type']); ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>No properties found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Properties Tab -->
            <div id="properties" class="tab-content">
                <div class="tab-container">
                    <div style="padding: 30px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2 style="color: #2c3e50;">All Properties</h2>
                            <a href="add.php" class="btn btn-add"><i class="fas fa-plus"></i> Add Property</a>
                        </div>

                        <?php if (!empty($properties)): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Title</th>
                                        <th>Location</th>
                                        <th>Type</th>
                                        <th>Land Area</th>
                                        <th>Price</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($properties as $prop): ?>
                                        <tr>
                                            <td>#<?php echo $prop['id']; ?></td>
                                            <td>
                                                <?php if ($prop['image']): ?>
                                                    <img src="uploads/<?php echo htmlspecialchars($prop['image']); ?>" alt="Property" class="thumbnail">
                                                <?php else: ?>
                                                    <div style="width: 50px; height: 50px; background: #ecf0f1; border-radius: 5px; display: flex; align-items: center; justify-content: center; color: #999;">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($prop['title']); ?></td>
                                            <td><?php echo htmlspecialchars($prop['location']); ?></td>
                                            <td><span class="badge badge-type-<?php echo htmlspecialchars($prop['type']); ?>"><?php echo htmlspecialchars($prop['type']); ?></span></td>
                                            <td><?php echo !empty($prop['land_area']) ? htmlspecialchars($prop['land_area']) : '-'; ?></td>
                                            <td class="price">Rs <?php echo number_format($prop['price'], 0); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($prop['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($prop['status'] !== 'approved'): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="property_id" value="<?php echo $prop['id']; ?>">
                                                            <button type="submit" name="approve_property" class="btn btn-view">Approve</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <?php if ($prop['status'] !== 'rejected'): ?>
                                                        <form method="POST" style="display: inline;" onsubmit="const r = prompt('Reason for rejecting this listing (optional):'); if (r === null) return false; this.rejection_reason.value = r;">
                                                            <input type="hidden" name="property_id" value="<?php echo $prop['id']; ?>">
                                                            <input type="hidden" name="rejection_reason" value="">
                                                            <button type="submit" name="reject_property" class="btn btn-delete">Reject</button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <a href="edit.php?id=<?php echo $prop['id']; ?>" class="btn btn-edit">Edit</a>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this property?');">
                                                        <input type="hidden" name="property_id" value="<?php echo $prop['id']; ?>">
                                                        <button type="submit" name="delete_property" class="btn btn-delete">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No properties found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Users Tab -->
            <div id="users" class="tab-content">
                <div class="tab-container">
                    <div style="padding: 30px;">
                        <h2 style="color: #2c3e50; margin-bottom: 20px;">User Management</h2>
                        
                        <?php if (!empty($users)): ?>
                            <table>
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Username</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>#<?php echo $user['id']; ?></td>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td>
                                                <span class="badge" style="background: <?php echo $user['id'] == 1 ? '#f0e8f8; color: #8e44ad;' : '#e8f8e8; color: #27ae60;'; ?>">
                                                    <?php echo $user['id'] == 1 ? 'Administrator' : 'User'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge" style="background: #e8f8e8; color: #27ae60;">Active</span>
                                            </td>
                                            <td>
                                                <div class="action-buttons">
                                                    <?php if ($user['id'] != 1): ?>
                                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <button type="submit" name="delete_user" class="btn btn-delete">Delete</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span style="color: #999; font-size: 13px;">Admin Account</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <p>No users found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Analytics Tab -->
            <div id="analytics" class="tab-content">
                <div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    <h2 style="color: #2c3e50; margin-bottom: 20px;">Analytics & Reports</h2>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                        <!-- Property Type Distribution -->
                        <div style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
                            <h3 style="color: #2c3e50; margin-bottom: 15px;">Properties by Type</h3>
                            <?php if (!empty($typeDistribution)): ?>
                                <ul style="list-style: none;">
                                    <?php foreach ($typeDistribution as $type): ?>
                                        <li style="margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between;">
                                            <span style="color: #666;"><?php echo htmlspecialchars($type['type']); ?></span>
                                            <span style="background: #667eea; color: white; padding: 5px 12px; border-radius: 20px; font-weight: bold;"><?php echo $type['count']; ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p style="color: #999;">No data available</p>
                            <?php endif; ?>
                        </div>

                        <!-- Quick Stats -->
                        <div style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
                            <h3 style="color: #2c3e50; margin-bottom: 15px;">Quick Statistics</h3>
                            <ul style="list-style: none;">
                                <li style="margin-bottom: 12px; display: flex; justify-content: space-between;">
                                    <span style="color: #666;">Total Properties:</span>
                                    <strong><?php echo $propertyCount; ?></strong>
                                </li>
                                <li style="margin-bottom: 12px; display: flex; justify-content: space-between;">
                                    <span style="color: #666;">Total Users:</span>
                                    <strong><?php echo $userCount; ?></strong>
                                </li>
                                <li style="margin-bottom: 12px; display: flex; justify-content: space-between;">
                                    <span style="color: #666;">Average Property Price:</span>
                                    <strong>Rs <?php echo number_format($propertyCount > 0 ? $totalValue / $propertyCount : 0, 0); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between;">
                                    <span style="color: #666;">Total Property Value:</span>
                                    <strong>Rs <?php echo number_format($totalValue, 0); ?></strong>
                                </li>
                            </ul>
                        </div>

                        <!-- System Status -->
                        <div style="padding: 20px; background: #f8f9fa; border-radius: 8px;">
                            <h3 style="color: #2c3e50; margin-bottom: 15px;">System Status</h3>
                            <ul style="list-style: none;">
                                <li style="margin-bottom: 12px;">
                                    <span style="color: #666;">Database:</span>
                                    <span style="background: #2ecc71; color: white; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">Connected</span>
                                </li>
                                <li style="margin-bottom: 12px;">
                                    <span style="color: #666;">Server:</span>
                                    <span style="background: #2ecc71; color: white; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: bold;">Online</span>
                                </li>
                                <li style="margin-bottom: 12px;">
                                    <span style="color: #666;">PHP Version:</span>
                                    <strong><?php echo phpversion(); ?></strong>
                                </li>
                                <li>
                                    <span style="color: #666;">Server Time:</span>
                                    <strong><?php echo date('Y-m-d H:i:s'); ?></strong>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(e, tabName) {
            e.preventDefault();
            
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => link.classList.remove('active'));
            
            document.getElementById(tabName).classList.add('active');
            e.currentTarget.closest('.nav-link').classList.add('active');
        }

        // Auto-switch tab if hash present in URL (e.g. #properties)
        window.addEventListener('DOMContentLoaded', () => {
            const hash = window.location.hash.substring(1);
            if (hash && document.getElementById(hash)) {
                const link = document.querySelector(`.nav-link[href="#${hash}"]`);
                if (link) link.click();
            }
        });
    </script>
</body>
</html>
