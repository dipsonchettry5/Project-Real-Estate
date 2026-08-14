<?php
session_start();
require "config.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$userEmail = $_SESSION["username"] ?? "";
$isAdmin = ($_SESSION["role"] ?? "") === "admin";

// Handle inquiry deletion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_inquiry"])) {
    $inquiryId = intval($_POST["inquiry_id"]);
    
    // Check permission to delete (must be recipient owner, sender, or admin)
    if ($isAdmin) {
        $stmt = $pdo->prepare("DELETE FROM inquiries WHERE id = ?");
        $stmt->execute([$inquiryId]);
    } else {
        $stmt = $pdo->prepare("
            DELETE i FROM inquiries i
            LEFT JOIN properties p ON i.property_id = p.id
            WHERE i.id = ? AND (p.user_id = ? OR i.user_id = ? OR i.email = ?)
        ");
        $stmt->execute([$inquiryId, $userId, $userId, $userEmail]);
    }
    
    header("Location: inquiries.php");
    exit;
}

// Fetch Received Inquiries (inquiries sent by buyers for properties owned by this user)
if ($isAdmin) {
    $receivedSql = "
        SELECT i.*, p.title AS property_title, p.image AS property_image, p.price AS property_price, p.location AS property_location
        FROM inquiries i
        JOIN properties p ON i.property_id = p.id
        ORDER BY i.created_at DESC";
    $stmtReceived = $pdo->query($receivedSql);
    $receivedInquiries = $stmtReceived->fetchAll(PDO::FETCH_ASSOC);
} else {
    $receivedSql = "
        SELECT i.*, p.title AS property_title, p.image AS property_image, p.price AS property_price, p.location AS property_location
        FROM inquiries i
        JOIN properties p ON i.property_id = p.id
        WHERE p.user_id = ?
        ORDER BY i.created_at DESC";
    $stmtReceived = $pdo->prepare($receivedSql);
    $stmtReceived->execute([$userId]);
    $receivedInquiries = $stmtReceived->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch Sent Inquiries (inquiries submitted by this user)
$sentSql = "
    SELECT i.*, p.title AS property_title, p.image AS property_image, p.price AS property_price, p.location AS property_location
    FROM inquiries i
    JOIN properties p ON i.property_id = p.id
    WHERE i.user_id = ? OR (i.email = ? AND i.email != '')
    ORDER BY i.created_at DESC";
$stmtSent = $pdo->prepare($sentSql);
$stmtSent->execute([$userId, $userEmail]);
$sentInquiries = $stmtSent->fetchAll(PDO::FETCH_ASSOC);

$activeTab = $_GET['tab'] ?? 'received';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Inquiries - Sapanko Ghar</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .page-title-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #cbd5e1;
        }

        .tab-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
        }

        .tab-btn {
            padding: 12px 24px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0b192c;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.25s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .tab-btn:hover {
            background: #f1f5f9;
            border-color: #1e3a8a;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #0b192c 0%, #1e3a8a 100%);
            color: #ffffff;
            border-color: #0b192c;
            box-shadow: 0 4px 12px rgba(11, 25, 44, 0.2);
        }

        .inquiries-grid {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .inquiry-card {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 24px;
            display: grid;
            grid-template-columns: 140px 1fr auto;
            gap: 20px;
            align-items: start;
            box-shadow: 0 4px 15px rgba(11, 25, 44, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .inquiry-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(11, 25, 44, 0.1);
        }

        .inquiry-img {
            width: 140px;
            height: 110px;
            object-fit: cover;
            border-radius: 8px;
        }

        .inquiry-details h3 {
            font-size: 18px;
            color: #0b192c;
            margin-bottom: 8px;
        }

        .inquiry-details h3 a {
            color: #0b192c;
            text-decoration: none;
        }

        .inquiry-details h3 a:hover {
            color: #1e3a8a;
            text-decoration: underline;
        }

        .meta-info {
            font-size: 14px;
            color: #475569;
            margin-bottom: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .meta-info span {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .inquiry-message {
            background: #f8fafc;
            padding: 14px 18px;
            border-radius: 8px;
            border-left: 4px solid #1e3a8a;
            color: #1e293b;
            font-size: 14px;
            line-height: 1.6;
        }

        .inquiry-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 15px;
        }

        .inquiry-date {
            font-size: 13px;
            color: #64748b;
            white-space: nowrap;
        }

        .delete-inquiry-btn {
            background: #fee2e2;
            color: #dc2626;
            border: 1px solid #fca5a5;
            padding: 8px 14px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .delete-inquiry-btn:hover {
            background: #dc2626;
            color: #ffffff;
        }

        .empty-box {
            text-align: center;
            padding: 60px 20px;
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin: 20px 0;
        }

        .empty-box-icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: #cbd5e1;
        }

        .empty-box h3 {
            font-size: 20px;
            color: #0b192c;
            margin-bottom: 8px;
        }

        .empty-box p {
            color: #64748b;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .inquiry-card {
                grid-template-columns: 1fr;
            }
            .inquiry-img {
                width: 100%;
                height: 180px;
            }
            .inquiry-actions {
                align-items: flex-start;
                flex-direction: row;
                justify-content: space-between;
                width: 100%;
            }
        }
    </style>
</head>
<body>

<header class="header">
    <div class="container header-content">
        <div class="header-left">
            <a href="index.php" class="secondary-btn">← Browse Properties</a>
        </div>

        <h1 class="header-title">Property Inquiries</h1>

        <div class="header-right">
            <a href="favorites.php" class="secondary-btn" style="margin-right: 8px;">❤️ Favorites</a>
            <a href="add.php" class="primary-btn">Add Property</a>
        </div>
    </div>
</header>

<main class="container">
    <div class="page-title-section">
        <h2>📬 Manage Inquiries</h2>
    </div>

    <!-- Navigation Tabs -->
    <div class="tab-buttons">
        <a href="inquiries.php?tab=received" class="tab-btn <?= $activeTab === 'received' ? 'active' : '' ?>">
            📥 Received Inquiries (<?= count($receivedInquiries) ?>)
        </a>
        <a href="inquiries.php?tab=sent" class="tab-btn <?= $activeTab === 'sent' ? 'active' : '' ?>">
            📤 Sent Inquiries (<?= count($sentInquiries) ?>)
        </a>
    </div>

    <!-- Received Inquiries Section -->
    <?php if ($activeTab === 'received'): ?>
        <?php if (empty($receivedInquiries)): ?>
            <div class="empty-box">
                <div class="empty-box-icon">📬</div>
                <h3>No received inquiries yet</h3>
                <p>When potential buyers submit inquiries for your property listings, they will appear here.</p>
                <a href="add.php" class="primary-btn">List a Property</a>
            </div>
        <?php else: ?>
            <div class="inquiries-grid">
                <?php foreach ($receivedInquiries as $inquiry): 
                    $pTitle    = htmlspecialchars($inquiry['property_title']);
                    $pImg      = htmlspecialchars($inquiry['property_image']);
                    $pPrice    = number_format($inquiry['property_price']);
                    $pLocation = htmlspecialchars($inquiry['property_location']);
                    $senderName  = htmlspecialchars($inquiry['name']);
                    $senderEmail = htmlspecialchars($inquiry['email']);
                    $senderPhone = htmlspecialchars($inquiry['phone']);
                    $msg       = nl2br(htmlspecialchars($inquiry['message']));
                    $date      = date("M j, Y - g:i A", strtotime($inquiry['created_at']));
                    $propId    = intval($inquiry['property_id']);
                    $inqId     = intval($inquiry['id']);
                ?>
                    <div class="inquiry-card">
                        <img src="uploads/<?= $pImg ?>" alt="<?= $pTitle ?>" class="inquiry-img">
                        
                        <div class="inquiry-details">
                            <h3><a href="details.php?id=<?= $propId ?>"><?= $pTitle ?></a> (Rs <?= $pPrice ?>)</h3>
                            
                            <div class="meta-info">
                                <span>👤 <strong>From:</strong> <?= $senderName ?></span>
                                <span>✉️ <a href="mailto:<?= $senderEmail ?>"><?= $senderEmail ?></a></span>
                                <?php if ($senderPhone): ?>
                                    <span>📞 <a href="tel:<?= $senderPhone ?>"><?= $senderPhone ?></a></span>
                                <?php endif; ?>
                            </div>

                            <div class="inquiry-message">
                                <strong>Message:</strong><br>
                                <?= $msg ?>
                            </div>
                        </div>

                        <div class="inquiry-actions">
                            <span class="inquiry-date">📅 <?= $date ?></span>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this inquiry?');">
                                <input type="hidden" name="inquiry_id" value="<?= $inqId ?>">
                                <button type="submit" name="delete_inquiry" class="delete-inquiry-btn">Delete Inquiry</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <!-- Sent Inquiries Section -->
    <?php else: ?>
        <?php if (empty($sentInquiries)): ?>
            <div class="empty-box">
                <div class="empty-box-icon">📤</div>
                <h3>No sent inquiries yet</h3>
                <p>When you submit an inquiry on a property page, you can track it here.</p>
                <a href="index.php" class="primary-btn">Browse Properties</a>
            </div>
        <?php else: ?>
            <div class="inquiries-grid">
                <?php foreach ($sentInquiries as $inquiry): 
                    $pTitle    = htmlspecialchars($inquiry['property_title']);
                    $pImg      = htmlspecialchars($inquiry['property_image']);
                    $pPrice    = number_format($inquiry['property_price']);
                    $pLocation = htmlspecialchars($inquiry['property_location']);
                    $msg       = nl2br(htmlspecialchars($inquiry['message']));
                    $date      = date("M j, Y - g:i A", strtotime($inquiry['created_at']));
                    $propId    = intval($inquiry['property_id']);
                    $inqId     = intval($inquiry['id']);
                ?>
                    <div class="inquiry-card">
                        <img src="uploads/<?= $pImg ?>" alt="<?= $pTitle ?>" class="inquiry-img">
                        
                        <div class="inquiry-details">
                            <h3><a href="details.php?id=<?= $propId ?>"><?= $pTitle ?></a></h3>
                            
                            <div class="meta-info">
                                <span>📍 <?= $pLocation ?></span>
                                <span>💰 Rs <?= $pPrice ?></span>
                            </div>

                            <div class="inquiry-message">
                                <strong>Your Message:</strong><br>
                                <?= $msg ?>
                            </div>
                        </div>

                        <div class="inquiry-actions">
                            <span class="inquiry-date">📅 <?= $date ?></span>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this inquiry log?');">
                                <input type="hidden" name="inquiry_id" value="<?= $inqId ?>">
                                <button type="submit" name="delete_inquiry" class="delete-inquiry-btn">Remove Log</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>

</body>
</html>
