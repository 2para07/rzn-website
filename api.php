<?php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Helper function to log activity
function logActivity($pdo, $userId, $action, $details = null) {
    $stmt = $pdo->prepare("INSERT INTO activity_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $details, $_SERVER['REMOTE_ADDR']]);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin or leader
function isAdmin() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'leader');
}

// Check if user is leader (RZN.J3em)
function isLeader() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'leader';
}

switch($action) {
    
    // REGISTER NEW MEMBER
    case 'register':
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }
        
        // Add RZN. prefix if not present
        if (strpos($username, 'RZN.') !== 0) {
            $username = 'RZN.' . $username;
        }
        
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            exit;
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user with pending status
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$username, $email, $hashedPassword]);
        
        echo json_encode(['success' => true, 'message' => 'Registration successful! Please wait for admin approval.']);
        break;
    
    // LOGIN
    case 'login':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Username and password are required']);
            exit;
        }
        
        // Add RZN. prefix if not present
        if (strpos($username, 'RZN.') !== 0) {
            $username = 'RZN.' . $username;
        }
        
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
            exit;
        }
        
        if ($user['role'] === 'pending') {
            echo json_encode(['success' => false, 'message' => 'Your account is pending approval by administrators']);
            exit;
        }
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        logActivity($pdo, $user['id'], 'login', 'User logged in');
        
        echo json_encode([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'avatar' => $user['avatar'],
                'facebook_url' => $user['facebook_url'],
                'youtube_url' => $user['youtube_url'],
                'tiktok_url' => $user['tiktok_url']
            ]
        ]);
        break;
    
    // LOGOUT
    case 'logout':
        if (isLoggedIn()) {
            logActivity($pdo, $_SESSION['user_id'], 'logout', 'User logged out');
        }
        session_destroy();
        echo json_encode(['success' => true]);
        break;
    
    // GET CURRENT USER
    case 'getCurrentUser':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT id, username, role, avatar, facebook_url, youtube_url, tiktok_url FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        echo json_encode(['success' => true, 'user' => $user]);
        break;
    
    // UPDATE PROFILE
    case 'updateProfile':
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Not logged in']);
            exit;
        }
        
        $avatar = $_POST['avatar'] ?? null;
        $facebook = $_POST['facebook_url'] ?? null;
        $youtube = $_POST['youtube_url'] ?? null;
        $tiktok = $_POST['tiktok_url'] ?? null;
        
        $stmt = $pdo->prepare("UPDATE users SET avatar = ?, facebook_url = ?, youtube_url = ?, tiktok_url = ? WHERE id = ?");
        $stmt->execute([$avatar, $facebook, $youtube, $tiktok, $_SESSION['user_id']]);
        
        logActivity($pdo, $_SESSION['user_id'], 'update_profile', 'Profile updated');
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        break;
    
    // GET ALL APPROVED MEMBERS
    case 'getMembers':
        $stmt = $pdo->prepare("SELECT id, username, avatar, facebook_url, youtube_url, tiktok_url, role FROM users WHERE role IN ('leader', 'admin', 'member') ORDER BY 
            CASE role 
                WHEN 'leader' THEN 1 
                WHEN 'admin' THEN 2 
                WHEN 'member' THEN 3 
            END, username ASC");
        $stmt->execute();
        $members = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'members' => $members]);
        break;
    
    // GET LEADERS (for homepage)
    case 'getLeaders':
        $stmt = $pdo->prepare("SELECT username, avatar, facebook_url, youtube_url, tiktok_url, role FROM users WHERE role IN ('leader', 'admin') ORDER BY 
            CASE role 
                WHEN 'leader' THEN 1 
                WHEN 'admin' THEN 2 
            END, id ASC");
        $stmt->execute();
        $leaders = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'leaders' => $leaders]);
        break;
    
    // ADMIN: GET PENDING MEMBERS
    case 'getPendingMembers':
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT id, username, email, created_at FROM users WHERE role = 'pending' ORDER BY created_at DESC");
        $stmt->execute();
        $pending = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'pending' => $pending]);
        break;
    
    // ADMIN: APPROVE MEMBER
    case 'approveMember':
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            exit;
        }
        
        $memberId = intval($_POST['member_id'] ?? 0);
        
        $stmt = $pdo->prepare("UPDATE users SET role = 'member', approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $memberId]);
        
        logActivity($pdo, $_SESSION['user_id'], 'approve_member', "Approved member ID: $memberId");
        
        echo json_encode(['success' => true, 'message' => 'Member approved successfully']);
        break;
    
    // ADMIN: DECLINE MEMBER
    case 'declineMember':
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            exit;
        }
        
        $memberId = intval($_POST['member_id'] ?? 0);
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'pending'");
        $stmt->execute([$memberId]);
        
        logActivity($pdo, $_SESSION['user_id'], 'decline_member', "Declined member ID: $memberId");
        
        echo json_encode(['success' => true, 'message' => 'Member declined successfully']);
        break;
    
    // ADMIN/LEADER: GET ALL MEMBERS (for admin panel)
    case 'getAllMembers':
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            exit;
        }
        
        // Get current user role
        $currentRole = $_SESSION['role'];
        
        // If leader, show ALL members including admins
        // If admin, show only members (not other admins or leader)
        if ($currentRole === 'leader') {
            $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY 
                CASE role 
                    WHEN 'leader' THEN 1 
                    WHEN 'admin' THEN 2 
                    WHEN 'member' THEN 3 
                    WHEN 'pending' THEN 4 
                END, created_at DESC");
        } else {
            $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users WHERE role IN ('member', 'pending') ORDER BY created_at DESC");
        }
        
        $allMembers = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'members' => $allMembers, 'currentRole' => $currentRole]);
        break;
    
    // LEADER ONLY: DELETE MEMBER (including admins)
    case 'deleteMember':
        if (!isLeader()) {
            echo json_encode(['success' => false, 'message' => 'Leader access required']);
            exit;
        }
        
        $memberId = intval($_POST['member_id'] ?? 0);
        
        // Prevent leader from deleting themselves
        if ($memberId == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
            exit;
        }
        
        // Get username before deleting
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$memberId]);
        $user = $stmt->fetch();
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$memberId]);
        
        logActivity($pdo, $_SESSION['user_id'], 'delete_member', "Deleted member: " . $user['username']);
        
        echo json_encode(['success' => true, 'message' => 'Member deleted successfully']);
        break;
    
    // ADMIN: DELETE MEMBER (only non-admin members)
    case 'deleteMemberAdmin':
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Admin access required']);
            exit;
        }
        
        // Admins can only delete regular members, not other admins or leader
        if (isLeader()) {
            // Leader can delete anyone except themselves
            $memberId = intval($_POST['member_id'] ?? 0);
            
            if ($memberId == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
                exit;
            }
        } else {
            // Regular admin can only delete members
            $memberId = intval($_POST['member_id'] ?? 0);
            
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$memberId]);
            $user = $stmt->fetch();
            
            if ($user['role'] === 'admin' || $user['role'] === 'leader') {
                echo json_encode(['success' => false, 'message' => 'You cannot delete admin or leader accounts']);
                exit;
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$memberId]);
        
        logActivity($pdo, $_SESSION['user_id'], 'delete_member', "Deleted member ID: $memberId");
        
        echo json_encode(['success' => true, 'message' => 'Member deleted successfully']);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>
