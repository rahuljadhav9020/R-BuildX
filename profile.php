<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Fetch user details
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "Error fetching user details";
    header('Location: home.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - R-BuildX</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" href="favicon.png" type="image/png">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="profile-info">
                    <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                    <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <button onclick="enableEdit()" class="edit-btn">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
            </div>

            <?php if(isset($_SESSION['profile_success'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['profile_success'];
                    unset($_SESSION['profile_success']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_SESSION['profile_error'])): ?>
                <div class="alert alert-error">
                    <?php 
                    echo $_SESSION['profile_error'];
                    unset($_SESSION['profile_error']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="profile-content">
                <form id="profileForm" method="POST" action="update_profile.php" class="profile-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" disabled required>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" disabled required>
                        </div>
                    </div>

                    <div class="form-row password-section" style="display: none;">
                        <div class="form-group">
                            <label><i class="fas fa-lock"></i> Current Password</label>
                            <input type="password" name="current_password" disabled placeholder="Enter current password" 
                                   oninput="toggleNewPasswordField(this.value)">
                            <small class="password-hint">Required to change password</small>
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-key"></i> New Password</label>
                            <input type="password" name="new_password" disabled placeholder="Enter new password" 
                                   class="new-password-input">
                            <small class="password-hint">Leave blank to keep current password</small>
                        </div>
                    </div>

                    <div class="form-actions" style="display: none;">
                        <button type="submit" class="save-btn">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <button type="button" onclick="cancelEdit()" class="cancel-btn">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    .profile-container {
        max-width: 1000px;
        margin: 40px auto;
        padding: 20px;
    }

    .profile-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        padding: 30px;
    }

    .profile-header {
        display: flex;
        align-items: center;
        margin-bottom: 40px;
        padding-bottom: 20px;
        border-bottom: 2px solid #f0f0f0;
    }

    .profile-avatar {
        font-size: 64px;
        color: #512da8;
        margin-right: 20px;
    }

    .profile-info {
        flex-grow: 1;
    }

    .profile-info h2 {
        margin: 0;
        color: #333;
        font-size: 24px;
    }

    .user-email {
        color: #666;
        margin: 5px 0 0 0;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        display: block;
        margin-bottom: 10px;
        color: #555;
        font-weight: 500;
        font-size: 14px;
    }

    .form-group label i {
        margin-right: 8px;
        color: #512da8;
    }

    .form-group input {
        width: 100%;
        padding: 12px;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 15px;
        transition: all 0.3s ease;
    }

    .form-group input:focus {
        border-color: #512da8;
        outline: none;
        box-shadow: 0 0 0 3px rgba(81, 45, 168, 0.1);
    }

    .form-group input:disabled {
        background: #f8f9fa;
        cursor: not-allowed;
        border-color: #e0e0e0;
    }

    .form-actions {
        margin-top: 30px;
        display: flex;
        gap: 15px;
        justify-content: flex-end;
    }

    .save-btn, .edit-btn {
        background: #512da8;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        transition: all 0.3s ease;
    }

    .cancel-btn {
        background: #dc3545;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        transition: all 0.3s ease;
    }

    .save-btn:hover, .edit-btn:hover {
        background: #673ab7;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(81, 45, 168, 0.2);
    }

    .cancel-btn:hover {
        background: #c82333;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);
    }

    .alert {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
        animation: fadeOut 5s forwards;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .password-section {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }

    .password-hint {
        display: block;
        color: #666;
        font-size: 12px;
        margin-top: 5px;
    }

    .form-group input[type="password"] {
        padding-right: 40px;
    }

    .password-toggle {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #512da8;
    }

    @keyframes fadeOut {
        0% { opacity: 1; }
        70% { opacity: 1; }
        100% { opacity: 0; display: none; }
    }
    </style>

    <script>
    function toggleNewPasswordField(currentPassword) {
        const newPasswordInput = document.querySelector('.new-password-input');
        if (currentPassword.length > 0) {
            newPasswordInput.disabled = false;
            newPasswordInput.required = true;
        } else {
            newPasswordInput.disabled = true;
            newPasswordInput.required = false;
            newPasswordInput.value = ''; // Clear new password when current password is empty
        }
    }

    function enableEdit() {
        const form = document.getElementById('profileForm');
        const inputs = form.getElementsByTagName('input');
        for(let input of inputs) {
            // Don't enable new password field yet
            if (input.name !== 'new_password') {
                input.disabled = false;
            }
        }
        
        document.querySelector('.password-section').style.display = 'grid';
        document.querySelector('.form-actions').style.display = 'flex';
        document.querySelector('.edit-btn').style.display = 'none';
    }

    function cancelEdit() {
        const form = document.getElementById('profileForm');
        const inputs = form.getElementsByTagName('input');
        for(let input of inputs) {
            input.disabled = true;
            input.value = input.defaultValue; // Reset to original value
        }
        
        document.querySelector('.password-section').style.display = 'none';
        document.querySelector('.form-actions').style.display = 'none';
        document.querySelector('.edit-btn').style.display = 'block';
        form.reset();
    }

    // Add this validation function
    function validatePhoneNumber(phone) {
        const phoneRegex = /^[789]\d{9}$/;
        return phoneRegex.test(phone);
    }

    // Update the form submission handler
    document.getElementById('profileForm').addEventListener('submit', function(e) {
        const newPassword = this.querySelector('[name="new_password"]').value;
        const currentPassword = this.querySelector('[name="current_password"]').value;
        const phone = this.querySelector('[name="phone"]').value;

        // Validate phone number
        if (!validatePhoneNumber(phone)) {
            e.preventDefault();
            alert('Mobile number must be 10 digits and start with 7, 8, or 9');
            return;
        }

        if (currentPassword && !newPassword) {
            e.preventDefault();
            alert('Please enter a new password or clear the current password field');
        } else if (newPassword && !currentPassword) {
            e.preventDefault();
            alert('Please enter your current password to change password');
        }
    });

    // Add this new function
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            setTimeout(() => {
                alert.remove();
            }, 5000);
        });
    });
    </script>

    <?php include 'footer.php'; ?>
</body>
</html> 