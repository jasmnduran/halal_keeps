<?php
require_once __DIR__ . '/../../config/app.php';
requireLogin();
requireRole([ROLE_ADMIN]);

$page_title = 'Authorization (RBAC)';
$page_heading = 'Role-Based Access Control';
$is_dashboard = true;
$breadcrumbs = [['label' => 'Admin Dashboard', 'url' => BASE_URL . 'dashboard/admin/'], ['label' => 'Authorization']];

// Get all roles
$roles_result = $conn->query("SELECT * FROM roles ORDER BY id ASC");
$roles = $roles_result ? $roles_result->fetch_all(MYSQLI_ASSOC) : [];

// Define static permissions matrix for display purposes
// In a full implementation, these could be stored in a permissions and role_permissions table
$permissions = [
    'User Management' => [
        'approve_roles' => ['description' => 'Approve/Reject user role applications', 'allowed' => [ROLE_ADMIN, ROLE_PRESIDENT]],
        'view_users' => ['description' => 'View list of all users', 'allowed' => [ROLE_ADMIN, ROLE_PRESIDENT]],
    ],
    'Applications' => [
        'create_loi' => ['description' => 'Create Letter of Intent', 'allowed' => [ROLE_BUSINESS_OWNER]],
        'verify_loi' => ['description' => 'Verify Letter of Intent', 'allowed' => [ROLE_EVALUATOR]],
        'submit_application' => ['description' => 'Submit Halal Application', 'allowed' => [ROLE_BUSINESS_OWNER]],
        'verify_application' => ['description' => 'Verify Halal Application', 'allowed' => [ROLE_EVALUATOR]],
    ],
    'Inspections & Audits' => [
        'schedule_inspection' => ['description' => 'Create Inspection Schedules', 'allowed' => [ROLE_EVALUATOR]],
        'submit_audit_findings' => ['description' => 'Submit Technical/Shariah Findings', 'allowed' => [ROLE_AUDITOR_TECHNICAL, ROLE_AUDITOR_SHARIAH]],
        'view_audit_findings' => ['description' => 'View Audit Findings', 'allowed' => [ROLE_BUSINESS_OWNER, ROLE_EVALUATOR, ROLE_IMPARTIAL_COMMITTEE]],
    ],
    'Laboratory' => [
        'receive_samples' => ['description' => 'Receive testing samples', 'allowed' => [ROLE_RECEIVING_OFFICER]],
        'submit_lab_results' => ['description' => 'Submit Laboratory Results', 'allowed' => [ROLE_LABORATORY_ANALYST]],
    ],
    'Decisions & Certificates' => [
        'impartial_review' => ['description' => 'Submit Impartial Committee Review', 'allowed' => [ROLE_IMPARTIAL_COMMITTEE]],
        'final_decision' => ['description' => 'Make Final Decision', 'allowed' => [ROLE_DECISION_COMMITTEE]],
        'award_certificate' => ['description' => 'Award Halal Certificate', 'allowed' => [ROLE_PRESIDENT]],
    ],
    'System Configuration' => [
        'manage_security' => ['description' => 'Manage DLP and Security Settings', 'allowed' => [ROLE_ADMIN]],
        'view_logs' => ['description' => 'View System Logs', 'allowed' => [ROLE_ADMIN]],
    ]
];

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-key" style="color: var(--primary-600); margin-right: 8px;"></i> API & Permission Checks</h3>
    </div>
    <div class="card-body">
        <p style="color: var(--neutral-600); margin-bottom: 24px;">The system implements strict Role-Based Access Control (RBAC). The matrix below outlines which roles are authorized to access specific functional endpoints and modules.</p>

        <?php foreach ($permissions as $category => $perms): ?>
            <h4 style="margin-top: 24px; margin-bottom: 12px; font-size: 1.05rem; font-weight: 700; color: var(--neutral-800); border-bottom: 2px solid var(--neutral-200); padding-bottom: 8px;"><?= htmlspecialchars($category) ?></h4>
            <div class="table-responsive">
                <table class="table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th style="width: 25%;">Permission</th>
                            <th style="width: 30%;">Description</th>
                            <th>Authorized Roles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($perms as $perm_key => $perm_data): ?>
                        <tr>
                            <td style="font-family: monospace; color: var(--primary-700); font-weight: 600;"><?= htmlspecialchars($perm_key) ?></td>
                            <td style="color: var(--neutral-700);"><?= htmlspecialchars($perm_data['description']) ?></td>
                            <td>
                                <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                                    <?php 
                                        foreach ($roles as $role) {
                                            if (in_array($role['id'], $perm_data['allowed'])) {
                                                echo '<span class="badge badge-success"><i class="fas fa-check" style="margin-right:4px;"></i>' . htmlspecialchars($role['role_name']) . '</span>';
                                            }
                                        }
                                    ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
