<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();
$title = '认证管理';
include 'includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>认证管理</h2>

        <?php if ($user['role'] === 'mediator'): ?>
            <div class="card mb-4">
                <div class="card-header">我的认证申请</div>
                <div class="card-body">
                    <?php
                    $pdo = connectDB();
                    $stmt = $pdo->prepare("SELECT a.*, u.nickname as merchant_name FROM associations a JOIN users u ON a.merchant_id = u.id WHERE a.mediator_id = ? ORDER BY a.created_at DESC");
                    $stmt->execute([$user['id']]);
                    $certifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($certifications)) {
                        echo '<p>暂无认证申请</p>';
                    } else {
                        foreach ($certifications as $cert) {
                            $status_text = $cert['status'] === 'pending' ? '待审核' : ($cert['status'] === 'approved' ? '已通过' : '已拒绝');
                            echo "<div class='mb-2'><strong>{$cert['merchant_name']}</strong> - {$status_text} - {$cert['created_at']}</div>";
                        }
                    }
                    ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">申请商家认证</div>
                <div class="card-body">
                    <form method="post" action="/apply_certification.php">
                        <div class="mb-3">
                            <label for="merchant_id" class="form-label">选择商家</label>
                            <select class="form-select" id="merchant_id" name="merchant_id" required>
                                <option value="">请选择商家</option>
                                <?php
                                $stmt = $pdo->query("SELECT id, nickname FROM users WHERE role = 'merchant' AND is_blacklisted = 0");
                                $merchants = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($merchants as $merchant) {
                                    echo "<option value='{$merchant['id']}'>{$merchant['nickname']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">申请认证</button>
                    </form>
                </div>
            </div>
        <?php elseif ($user['role'] === 'merchant'): ?>
            <div class="card mb-4">
                <div class="card-header">认证申请管理</div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->prepare("SELECT a.*, u.nickname as mediator_name FROM associations a JOIN users u ON a.mediator_id = u.id WHERE a.merchant_id = ? ORDER BY a.created_at DESC");
                    $stmt->execute([$user['id']]);
                    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($applications)) {
                        echo '<p>暂无认证申请</p>';
                    } else {
                        foreach ($applications as $app) {
                            echo "<div class='d-flex justify-content-between align-items-center mb-2'>";
                            echo "<span><strong>{$app['mediator_name']}</strong> - " . ($app['status'] === 'pending' ? '待审核' : ($app['status'] === 'approved' ? '已通过' : '已拒绝')) . "</span>";
                            if ($app['status'] === 'pending') {
                                echo "<div>";
                                echo "<a href='/certify.php?id={$app['id']}&action=approve' class='btn btn-success btn-sm'>通过</a> ";
                                echo "<a href='/certify.php?id={$app['id']}&action=reject' class='btn btn-danger btn-sm'>拒绝</a>";
                                echo "</div>";
                            }
                            echo "</div>";
                        }
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>