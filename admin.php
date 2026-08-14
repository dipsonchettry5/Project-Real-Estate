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

// Get statistics
$propertyCount = $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Get properties for the table
$properties = $pdo->query("SELECT * FROM properties ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get users for the table
$users = $pdo->query("SELECT id, username FROM users")->fetchAll(PDO::FETCH_ASSOC);



// Get recent properties (last 5)
$recentProperties = $pdo->query("SELECT * FROM properties ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Handle delete operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_property'])) {
        $id = $_POST['property_id'];
        $pdo->prepare("DELETE FROM properties WHERE id = ?")->execute([$id]);
        header("Location: admin.php");
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
            header("Location: admin.php");
            exit();
        }
    }
}

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
    <title>Admin Dashboard - Real Estate Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            line-height: 1.6;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 30px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
        }

        .sidebar .logo {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 30px;
            padding: 0 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 20px;
        }

        .sidebar .logo i {
            color: #667eea;
            margin-right: 10px;
        }

        .sidebar .user-info {
            background: rgba(102, 126, 234, 0.1);
            padding: 20px;
            margin: 0 15px 30px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .sidebar .user-info p {
            font-size: 12px;
            color: #bbb;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .sidebar .user-info .username {
            font-size: 16px;
            font-weight: bold;
            color: white;
        }

        .sidebar nav a {
            display: block;
            color: #bbb;
            text-decoration: none;
            padding: 15px 30px;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }

        .sidebar nav a:hover,
        .sidebar nav a.active {
            background: rgba(102, 126, 234, 0.2);
            color: #667eea;
            border-left-color: #667eea;
            padding-left: 35px;
        }

        .sidebar nav a i {
            width: 20px;
            margin-right: 15px;
        }

        .sidebar .logout-btn {
            position: absolute;
            bottom: 30px;
            left: 30px;
            right: 30px;
            background: #e74c3c;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
            font-weight: bold;
        }

        .sidebar .logout-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
        }

        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            color: #2c3e50;
        }

        .header .admin-title {
            color: #667eea;
            font-size: 14px;
            font-weight: bold;
        }

        /* Dashboard Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-left: 5px solid #667eea;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        .stat-card.properties {
            border-left-color: #3498db;
        }

        .stat-card.users {
            border-left-color: #2ecc71;
        }

        .stat-card.revenue {
            border-left-color: #f39c12;
        }

        .stat-card .icon {
            font-size: 40px;
            margin-bottom: 15px;
            color: #667eea;
        }

        .stat-card.properties .icon {
            color: #3498db;
        }

        .stat-card.users .icon {
            color: #2ecc71;
        }

        .stat-card.revenue .icon {
            color: #f39c12;
        }

        .stat-card .label {
            font-size: 14px;
            color: #999;
            margin-bottom: 10px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
        }

        /* Tabs */
        .tab-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .tabs {
            display: flex;
            border-bottom: 2px solid #ecf0f1;
        }

        .tab {
            flex: 1;
            padding: 18px;
            text-align: center;
            background: white;
            border: none;
            cursor: pointer;
            font-size: 15px;
            font-weight: bold;
            color: #7f8c8d;
            transition: all 0.3s;
            position: relative;
        }

        .tab:hover {
            background: #f8f9fa;
            color: #667eea;
        }

        .tab.active {
            color: #667eea;
            background: #f8f9fa;
        }

        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 3px;
            background: #667eea;
        }

        .tab i {
            margin-right: 8px;
        }

        /* Tab Content */
        .tab-content {
            padding: 30px;
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table thead {
            background: #f8f9fa;
        }

        table th {
            padding: 15px;
            text-align: left;
            font-weight: bold;
            color: #2c3e50;
            border-bottom: 2px solid #ecf0f1;
        }

        table td {
            padding: 15px;
            border-bottom: 1px solid #ecf0f1;
        }

        table tbody tr:hover {
            background: #f8f9fa;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-edit {
            background: #3498db;
            color: white;
        }

        .btn-edit:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: #e74c3c;
            color: white;
        }

        .btn-delete:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .btn-view {
            background: #2ecc71;
            color: white;
        }

        .btn-view:hover {
            background: #27ae60;
            transform: translateY(-2px);
        }

        .btn-add {
            background: #667eea;
            color: white;
            padding: 12px 25px;
            margin-bottom: 20px;
        }

        .btn-add:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }

        /* Image thumbnail */
        .thumbnail {
            width: 50px;
            height: 50px;
            border-radius: 5px;
            object-fit: cover;
        }

        /* Price formatting */
        .price {
            color: #27ae60;
            font-weight: bold;
        }

        /* Badge */
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            background: #ecf0f1;
            color: #2c3e50;
        }

        .badge-type-Villa {
            background: #e8f4f8;
            color: #3498db;
        }

        .badge-type-Apartment {
            background: #f0e8f8;
            color: #8e44ad;
        }

        .badge-type-House {
            background: #e8f8e8;
            color: #27ae60;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }

        .modal-header {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-body p {
            margin: 15px 0;
            color: #666;
        }

        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .recent-activity {
            margin-top: 30px;
        }

        .recent-activity h3 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .activity-item .icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            color: white;
            font-size: 18px;
        }

        .activity-item .icon.property {
            background: #3498db;
        }

        .activity-item .icon.user {
            background: #2ecc71;
        }

        .activity-info {
            flex: 1;
        }

        .activity-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .activity-time {
            font-size: 12px;
            color: #999;
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .header {
                flex-direction: column;
                text-align: center;
            }

            .tabs {
                flex-wrap: wrap;
            }

            .tab {
                flex: 1;
                min-width: 120px;
            }

            table {
                font-size: 12px;
            }

            table th, table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-building"></i> Admin
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
                <a href="logout.php" style="margin-top: auto;">
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
                <div style="text-align: right;">
                 
                </div>
            </div>

            <!-- Dashboard Tab -->
            <div id="dashboard" class="tab-content active">
                <!-- Stats Grid -->
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
                                            <td class="price">$<?php echo number_format($prop['price'], 0); ?></td>
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
                                    <strong>$<?php echo number_format($propertyCount > 0 ? $totalValue / $propertyCount : 0, 0); ?></strong>
                                </li>
                                <li style="display: flex; justify-content: space-between;">
                                    <span style="color: #666;">Total Property Value:</span>
                                    <strong>$<?php echo number_format($totalValue ?? 0, 0); ?></strong>
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
        // Tab switching functionality
        function switchTab(e, tabName) {
            e.preventDefault();
            
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all nav links
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks.forEach(link => link.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked nav link
            event.target.closest('.nav-link').classList.add('active');
        }

       
    </script>
</body>
</html>
