<?php
/**
 * PIN Authentication System (Database Version)
 * Place this file in: includes/pin-auth.php
 */

/**
 * Verify PIN against database and return role
 */
function verifyPIN($pin) {
    global $pdo;
    
    $pin = trim($pin);
    
    try {
        // Get all PINs from database
        $stmt = $pdo->query("SELECT role, pin FROM system_pins");
        $pins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($pins as $pinData) {
            if (password_verify($pin, $pinData['pin'])) {
                return $pinData['role'];
            }
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("PIN verification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if PIN is authenticated
 */
function isPINAuthenticated() {
    return isset($_SESSION['pin_authenticated']) && 
           isset($_SESSION['pin_role']) &&
           $_SESSION['pin_authenticated'] === true;
}

/**
 * Get authenticated PIN role
 */
function getPINRole() {
    return $_SESSION['pin_role'] ?? null;
}

/**
 * Set PIN authentication
 */
function setPINAuthentication($role) {
    $_SESSION['pin_authenticated'] = true;
    $_SESSION['pin_role'] = $role;
    $_SESSION['pin_auth_time'] = time();
}

/**
 * Clear PIN authentication
 */
function clearPINAuthentication() {
    unset($_SESSION['pin_authenticated']);
    unset($_SESSION['pin_role']);
    unset($_SESSION['pin_auth_time']);
}

/**
 * Check if PIN session is expired (30 minutes)
 */
function isPINSessionExpired() {
    if (!isset($_SESSION['pin_auth_time'])) {
        return true;
    }
    
    return (time() - $_SESSION['pin_auth_time']) > 60; // 1 minutes = 60 seconds
}

/**
 * Validate user role matches PIN role
 */
function validateUserRoleWithPIN($userRole) {
    $pinRole = getPINRole();
    
    if (!$pinRole) {
        return false;
    }
    
    return strtolower($userRole) === strtolower($pinRole);
}

/**
 * Update PIN for a role (Admin only)
 */
function updateRolePIN($role, $newPin, $userId, $changeReason = null) {
    global $pdo;
    
    try {
        // Get old PIN hash for history
        $stmt = $pdo->prepare("SELECT pin FROM system_pins WHERE role = ?");
        $stmt->execute([$role]);
        $oldPinHash = $stmt->fetchColumn();
        
        // Hash new PIN
        $newPinHash = password_hash($newPin, PASSWORD_DEFAULT);
        
        // Update PIN
        $stmt = $pdo->prepare("UPDATE system_pins SET pin = ?, updated_at = NOW() WHERE role = ?");
        $stmt->execute([$newPinHash, $role]);
        
        // Log to history
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt = $pdo->prepare("
            INSERT INTO pin_change_history (role, old_pin_hash, new_pin_hash, changed_by, change_reason, ip_address) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$role, $oldPinHash, $newPinHash, $userId, $changeReason, $ipAddress]);
        
        return ['success' => true, 'message' => 'PIN updated successfully'];
    } catch (PDOException $e) {
        error_log("PIN update error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Get PIN for a role (returns hashed PIN - for display only, not verification)
 */
function getRolePIN($role) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT pin, updated_at FROM system_pins WHERE role = ?");
        $stmt->execute([$role]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get PIN error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get PIN change history (Admin only)
 */
function getPINChangeHistory($limit = 10, $offset = 0) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT h.*, u.username, u.first_name, u.last_name 
            FROM pin_change_history h
            LEFT JOIN users u ON h.changed_by = u.id
            ORDER BY h.changed_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Get PIN history error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get PIN change history count (Admin only)
 */
function getPINChangeHistoryCount() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM pin_change_history");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
    } catch (PDOException $e) {
        error_log("Get PIN history count error: " . $e->getMessage());
        return 0;
    }
}