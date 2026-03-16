<?php
require_once 'config/config.php';

if (!isLoggedIn()) {
    redirect('/login.php');
}

$user = getCurrentUser();
$pdo = connectDB();

$title = '认证';
include 'includes/header.php';

// Handle certification submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_certification'])) {
    $certType = trim($_POST['cert_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $contactInfo = trim($_POST['contact_info'] ?? '');

    if (empty($certType) || empty($description)) {
        $error = '认证类型和描述不能为空';
    } else {
        // For now, just mark as verified. In real implementation, this would require admin review
        $stmt = $pdo->prepare('UPDATE users SET is_verified = 1 WHERE id = ?');
        $stmt->execute([$user['id']]);
        $message = '认证申请已提交，等待审核';
    }
}
?>

<div class="row">
    <div class="col-md-12">
        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($user['is_verified']): ?>
            <div class="card">
                <div class="card-header">认证状态</div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle"></i> 已认证</h5>
                        <p>您的账号已通过实名认证，可以正常使用所有功能。</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="card-header">实名认证</div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> 认证说明</h5>
                        <p>为了保障平台安全和用户权益，<?php echo $user['role'] === 'mediator' ? '媒人' : '商家'; ?>需要进行实名认证。</p>
                        <ul>
                            <li>提交身份证明和相关资质证书</li>
                            <li>认证通过后将获得认证标识</li>
                            <li>认证用户享有更多平台功能和信任</li>
                        </ul>
                    </div>

                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">认证类型</label>
                            <select name="cert_type" class="form-control" required>
                                <option value="">请选择认证类型</option>
                                <?php if ($user['role'] === 'mediator'): ?>
                                    <option value="personal">个人身份认证</option>
                                    <option value="professional">从业资格认证</option>
                                    <option value="company">机构认证</option>
                                <?php else: ?>
                                    <option value="business">营业执照认证</option>
                                    <option value="personal">法人身份认证</option>
                                    <option value="license">相关资质认证</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">认证描述</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="请详细描述您的认证信息，包括资质证书、从业经验等..." required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">联系方式</label>
                            <input type="text" name="contact_info" class="form-control" placeholder="请提供联系电话或邮箱" value="<?php echo htmlspecialchars($user['mobile'] ?? $user['email'] ?? ''); ?>">
                        </div>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="agree_terms" required>
                                <label class="form-check-label" for="agree_terms">
                                    我同意平台认证条款和隐私政策
                                </label>
                            </div>
                        </div>

                        <button type="submit" name="submit_certification" class="btn btn-primary">提交认证</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
            <div class="card mt-4">
                <div class="card-header">认证审核 (管理员)</div>
                <div class="card-body">
                    <h6>待审核认证申请</h6>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>用户</th>
                                    <th>角色</th>
                                    <th>申请时间</th>
                                    <th>状态</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query("SELECT id, nickname, role, created_at, is_verified FROM users WHERE is_verified = 0 AND is_deleted = 0 AND role IN ('mediator', 'merchant') ORDER BY created_at DESC LIMIT 20");
                                while ($pendingUser = $stmt->fetch()):
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($pendingUser['nickname']); ?></td>
                                        <td><?php echo $pendingUser['role']; ?></td>
                                        <td><?php echo $pendingUser['created_at']; ?></td>
                                        <td><span class="badge bg-warning">待审核</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-success" onclick="verifyUser(<?php echo $pendingUser['id']; ?>)">通过</button>
                                            <button class="btn btn-sm btn-danger" onclick="rejectUser(<?php echo $pendingUser['id']; ?>)">拒绝</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <script>
            function verifyUser(userId) {
                if (confirm('确定通过这个用户的认证申请？')) {
                    fetch('/api/verify_user.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ user_id: userId, action: 'verify' })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('操作失败');
                        }
                    });
                }
            }

            function rejectUser(userId) {
                if (confirm('确定拒绝这个用户的认证申请？')) {
                    fetch('/api/verify_user.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ user_id: userId, action: 'reject' })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('操作失败');
                        }
                    });
                }
            }
            </script>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>