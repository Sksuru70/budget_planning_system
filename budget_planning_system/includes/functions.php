<?php
// includes/functions.php

if (session_status() === PHP_SESSION_NONE) session_start();

// ── Auth helpers ──────────────────────────────────────────
function isLoggedIn(): bool   { return isset($_SESSION['user_id']); }
function isAdmin(): bool       { return ($_SESSION['role'] ?? '') === 'admin'; }
function requireLogin(): void  { if (!isLoggedIn()) { header('Location: /budget_planning_system/index.php'); exit; } }
function requireAdmin(): void  { requireLogin(); if (!isAdmin()) { header('Location: /budget_planning_system/dashboard.php'); exit; } }

function uid(): int { return (int)($_SESSION['user_id'] ?? 0); }

// ── Currency ──────────────────────────────────────────────
function fmt(float $n): string { return '₹ ' . number_format($n, 2); }

// ── Flash messages ────────────────────────────────────────
function setFlash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function getFlash(): ?array {
    if (!isset($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}

// ── Generate alerts for budget overruns ──────────────────
function checkAndGenerateAlerts(PDO $pdo, int $userId): void {
    $month = date('Y-m');
    $sql = "
        SELECT bc.category_id, bc.category_name, bc.budget_limit,
               COALESCE(SUM(t.amount),0) AS spent
        FROM budget_categories bc
        LEFT JOIN transactions t
               ON t.category_id = bc.category_id
              AND t.type = 'expense'
              AND DATE_FORMAT(t.transaction_date,'%Y-%m') = ?
        WHERE bc.user_id = ? AND bc.status = 'active'
        GROUP BY bc.category_id";
    $rows = $pdo->prepare($sql);
    $rows->execute([$month, $userId]);
    foreach ($rows->fetchAll() as $r) {
        $pct = $r['budget_limit'] > 0 ? ($r['spent'] / $r['budget_limit']) * 100 : 0;
        $type = null;
        if ($pct >= 100) $type = 'exceeded';
        elseif ($pct >= 80) $type = 'warning';
        if (!$type) continue;
        // avoid duplicate alerts in same month
        $chk = $pdo->prepare("SELECT alert_id FROM alerts WHERE user_id=? AND category_id=? AND alert_type=? AND DATE_FORMAT(created_at,'%Y-%m')=?");
        $chk->execute([$userId, $r['category_id'], $type, $month]);
        if ($chk->fetch()) continue;
        $msg = $type === 'exceeded'
            ? "You have exceeded your budget for {$r['category_name']}! Spent ".fmt($r['spent'])." of ".fmt($r['budget_limit'])."."
            : "Warning: You have used ".round($pct)."% of your {$r['category_name']} budget (".fmt($r['spent'])." of ".fmt($r['budget_limit']).").";
        $ins = $pdo->prepare("INSERT INTO alerts (user_id,category_id,alert_type,message) VALUES (?,?,?,?)");
        $ins->execute([$userId, $r['category_id'], $type, $msg]);
    }
}
