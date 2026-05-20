<?php
// includes/reset_session_component.php
// Reusable Reset Sessions button + modal + JavaScript.
// Use in any admin page:
//
// require_once __DIR__ . '/../includes/reset_session_component.php';
//
// In button area:
// renderResetSessionButton('Reset Sessions', 'btn-reset');
//
// Before </body>:
// renderResetSessionModal($conn, '../controllers/session/reset_sessions.php');

if (!function_exists('resetSessionComponentTableExists')) {
    function resetSessionComponentTableExists(mysqli $conn, string $tableName): bool
    {
        $stmt = $conn->prepare("
            SELECT COUNT(*)
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
        ");

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $tableName);
        $stmt->execute();

        $count = 0;
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        return (int)$count > 0;
    }
}

if (!function_exists('renderResetSessionButton')) {
    function renderResetSessionButton(string $label = 'Reset Sessions', string $className = 'btn-reset-sessions'): void
    {
        ?>
        <button type="button" class="<?= htmlspecialchars($className) ?>" onclick="openReusableResetSessions()">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:-2px;margin-right:4px;">
            <polyline points="1 4 1 10 7 10"></polyline>
            <path d="M3.51 15a9 9 0 1 0 .49-3.63"></path>
          </svg>
          <?= htmlspecialchars($label) ?>
        </button>
        <?php
    }
}

if (!function_exists('renderResetSessionModal')) {
    function renderResetSessionModal(mysqli $conn, string $controllerUrl = '../controllers/session/reset_sessions.php'): void
    {
        static $rendered = false;

        if ($rendered) {
            return;
        }

        $rendered = true;
        $sessionResetLogs = [];

        if (resetSessionComponentTableExists($conn, 'session_reset_logs')) {
            $resetResult = $conn->query("
                SELECT reset_title, total_students, total_credits_before, total_credits_after, reset_by, created_at
                FROM session_reset_logs
                ORDER BY created_at DESC
                LIMIT 5
            ");

            if ($resetResult) {
                $sessionResetLogs = $resetResult->fetch_all(MYSQLI_ASSOC);
            }
        }
        ?>

        <style>
          .reusable-reset-modal {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0, 0, 0, 0.45);
            align-items: center;
            justify-content: center;
          }

          .reusable-reset-box {
            width: 100%;
            max-width: 560px;
            margin: 1rem;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.22);
            font-family: 'Poppins', sans-serif;
            overflow: hidden;
          }

          .reusable-reset-header {
            background: #d97706;
            color: #ffffff;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
          }

          .reusable-reset-header span {
            font-size: 14px;
            font-weight: 700;
          }

          .reusable-reset-close {
            background: transparent;
            border: none;
            color: #ffffff;
            font-size: 20px;
            cursor: pointer;
            line-height: 1;
          }

          .reusable-reset-body {
            padding: 20px 24px;
          }

          .reusable-reset-warning {
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 12px;
            line-height: 1.6;
            margin-bottom: 14px;
          }

          .reusable-reset-label {
            font-size: 12px;
            font-weight: 700;
            color: #374151;
            display: block;
            margin-bottom: 6px;
          }

          .reusable-reset-input {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 13px;
            font-size: 13px;
            font-family: 'Poppins', sans-serif;
            outline: none;
            color: #111827;
          }

          .reusable-reset-input:focus {
            border-color: #d97706;
            box-shadow: 0 0 0 3px rgba(217, 119, 6, 0.12);
          }

          .reusable-reset-error {
            display: none;
            margin-top: 7px;
            font-size: 12px;
            color: #b91c1c;
          }

          .reusable-reset-log-title {
            font-size: 12px;
            font-weight: 800;
            color: #374151;
            margin-bottom: 6px;
          }

          .reusable-reset-log-list {
            max-height: 170px;
            overflow-y: auto;
            display: grid;
            gap: 8px;
            margin-top: 10px;
          }

          .reusable-reset-log-item {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 12px;
            color: #374151;
          }

          .reusable-reset-log-name {
            font-weight: 800;
            color: #111827;
            margin-bottom: 2px;
          }

          .reusable-reset-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
          }

          .reusable-reset-cancel {
            padding: 9px 20px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            font-size: 13px;
            font-weight: 500;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            color: #374151;
          }

          .reusable-reset-confirm {
            padding: 9px 20px;
            background: #d97706;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
          }

          .reusable-reset-confirm:disabled {
            opacity: 0.65;
            cursor: not-allowed;
          }

          .reusable-reset-toast {
            display: none;
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 10000;
            color: #ffffff;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.18);
          }
        </style>

        <div id="reusableResetSessionModal" class="reusable-reset-modal">
          <div class="reusable-reset-box">
            <div class="reusable-reset-header">
              <span>🔄 Reset Student Sessions</span>
              <button type="button" class="reusable-reset-close" onclick="closeReusableResetSessions()">✕</button>
            </div>

            <div class="reusable-reset-body">
              <div class="reusable-reset-warning">
                <strong>Important:</strong> This will reset all students' available session credits back to
                <strong>30</strong>. It will not delete students, reservations, current sit-ins, or sit-in history.
                The reset title will be saved in the reset logs.
              </div>

              <div>
                <label class="reusable-reset-label">Reset Title</label>
                <input
                  type="text"
                  id="reusableResetSessionTitle"
                  class="reusable-reset-input"
                  placeholder="Example: Final Term Session Reset">
                <div id="reusableResetSessionError" class="reusable-reset-error"></div>
              </div>

              <?php if (!empty($sessionResetLogs)): ?>
                <div style="margin-top:12px;">
                  <div class="reusable-reset-log-title">Recent Reset Logs</div>

                  <div class="reusable-reset-log-list">
                    <?php foreach ($sessionResetLogs as $resetLog): ?>
                      <div class="reusable-reset-log-item">
                        <div class="reusable-reset-log-name"><?= htmlspecialchars($resetLog['reset_title']) ?></div>
                        <div>
                          Students: <?= (int)$resetLog['total_students'] ?> ·
                          Before: <?= (int)$resetLog['total_credits_before'] ?> ·
                          After: <?= (int)$resetLog['total_credits_after'] ?>
                        </div>
                        <div style="color:#6b7280;margin-top:2px;">
                          By <?= htmlspecialchars($resetLog['reset_by'] ?: 'Administrator') ?>
                          · <?= date('M d, Y h:i A', strtotime($resetLog['created_at'])) ?>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>

              <div class="reusable-reset-actions">
                <button type="button" class="reusable-reset-cancel" onclick="closeReusableResetSessions()">
                  Cancel
                </button>

                <button type="button" id="reusableResetConfirmBtn" class="reusable-reset-confirm" onclick="confirmReusableResetSessions()">
                  Yes, Reset Sessions
                </button>
              </div>
            </div>
          </div>
        </div>

        <div id="reusableResetToast" class="reusable-reset-toast"></div>

        <script>
          const reusableResetControllerUrl = <?= json_encode($controllerUrl) ?>;

          function openReusableResetSessions() {
            const modal = document.getElementById('reusableResetSessionModal');
            const input = document.getElementById('reusableResetSessionTitle');
            const error = document.getElementById('reusableResetSessionError');

            if (input && !input.value.trim()) {
              const now = new Date();
              input.value = 'Session Reset - ' + now.toLocaleDateString('en-PH', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
              });
            }

            if (error) {
              error.textContent = '';
              error.style.display = 'none';
            }

            if (modal) {
              modal.style.display = 'flex';
            }
          }

          function closeReusableResetSessions() {
            const modal = document.getElementById('reusableResetSessionModal');
            const btn = document.getElementById('reusableResetConfirmBtn');

            if (modal) {
              modal.style.display = 'none';
            }

            if (btn) {
              btn.disabled = false;
              btn.textContent = 'Yes, Reset Sessions';
            }
          }

          function showReusableResetToast(message, color) {
            const toast = document.getElementById('reusableResetToast');

            if (!toast) {
              appAlert(message, 'Reset Sessions', color === '#059669' ? 'success' : 'danger');
              return;
            }

            toast.textContent = message;
            toast.style.background = color;
            toast.style.display = 'block';

            setTimeout(() => {
              toast.style.display = 'none';
            }, 3000);
          }

          function confirmReusableResetSessions() {
            const input = document.getElementById('reusableResetSessionTitle');
            const error = document.getElementById('reusableResetSessionError');
            const btn = document.getElementById('reusableResetConfirmBtn');
            const title = input ? input.value.trim() : '';

            if (!title) {
              if (error) {
                error.textContent = 'Please enter a reset title.';
                error.style.display = 'block';
              }
              return;
            }

            if (btn) {
              btn.disabled = true;
              btn.textContent = 'Resetting...';
            }

            const formData = new FormData();
            formData.append('reset_title', title);

            fetch(reusableResetControllerUrl, {
              method: 'POST',
              body: formData
            })
              .then(res => res.json())
              .then(data => {
                closeReusableResetSessions();

                if (data.success) {
                  showReusableResetToast(data.message || 'Student sessions reset successfully.', '#059669');
                  setTimeout(() => location.reload(), 1200);
                } else {
                  showReusableResetToast(data.message || 'Failed to reset sessions.', '#dc2626');
                }
              })
              .catch(() => {
                closeReusableResetSessions();
                showReusableResetToast('Something went wrong while resetting sessions.', '#dc2626');
              });
          }

          const reusableResetModal = document.getElementById('reusableResetSessionModal');

          if (reusableResetModal) {
            reusableResetModal.addEventListener('click', function (event) {
              if (event.target === this) {
                closeReusableResetSessions();
              }
            });
          }
        </script>
        <?php
    }
}
