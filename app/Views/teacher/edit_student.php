<?php
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Получаем данные ученика
$student_info = $user->getStudentInfo($student_id, $teacher_id);
if (!$student_info) {
    header("Location: /teacher/students");
    exit();
}

// Обработка формы обновления
if ($_POST && isset($_POST['update_student'])) {
    $new_username = trim($_POST['username']);
    $new_password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $new_first_name = trim($_POST['first_name']);
    $new_last_name = trim($_POST['last_name']);
    
    // Валидация
    if (empty($new_username) || empty($new_first_name) || empty($new_last_name)) {
        $error = translate('edit_student_all_fields_required');
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $error = translate('edit_student_password_min_length');
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = translate('edit_student_passwords_not_match');
    } else {
        // Обновляем данные
        $password_to_update = !empty($new_password) ? $new_password : null;
        
        if ($user->updateStudent($student_id, $teacher_id, $new_username, $password_to_update, $new_first_name, $new_last_name)) {
            $success = translate('edit_student_data_updated');
            // Обновляем локальные данные для отображения
            $student_info['username'] = $new_username;
            $student_info['first_name'] = $new_first_name;
            $student_info['last_name'] = $new_last_name;
        } else {
            $error = translate('edit_student_update_error');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo translate('edit_student_title'); ?> - QuizCard</title>
    <link rel="stylesheet" href="/public/css/app.css">
    <link rel="icon" type="image/x-icon" href="/public/favicon/favicon.ico">
    <style>
        .edit-form-container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .form-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .form-header p {
            opacity: 0.9;
            font-size: 1rem;
        }

        .form-content {
            padding: 2rem;
        }

        .student-info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid #007bff;
        }

        .student-info-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
        }

        .student-info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }

        .student-info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 500;
            color: #666;
        }

        .info-value {
            color: #333;
        }

        .form-grid {
            display: grid;
            gap: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .form-group input {
            padding: 0.8rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .password-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }

        .password-note {
            color: #856404;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .required {
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .edit-form-container {
                margin: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/header.php'; ?>

    <div class="container">
        <div class="edit-form-container">
            <div class="form-header">
                <h1 data-translate-key="edit_student_title"><?php echo translate('edit_student_title'); ?></h1>
                <p data-translate-key="edit_student_subtitle"><?php echo translate('edit_student_subtitle'); ?></p>
            </div>

            <div class="form-content">
                <div class="student-info-card">
                    <div class="student-info-title" data-translate-key="current_student_info">
                        <?php echo translate('current_student_info'); ?>
                    </div>
                    <div class="student-info-item">
                        <span class="info-label" data-translate-key="student_id"><?php echo translate('student_id'); ?>:</span>
                        <span class="info-value">#<?php echo $student_info['id']; ?></span>
                    </div>
                    <div class="student-info-item">
                        <span class="info-label" data-translate-key="registration_date"><?php echo translate('registration_date'); ?>:</span>
                        <span class="info-value"><?php echo date('d.m.Y', strtotime($student_info['created_at'])); ?></span>
                    </div>
                    <div class="student-info-item">
                        <span class="info-label" data-translate-key="last_login"><?php echo translate('last_login'); ?>:</span>
                        <span class="info-value">
                            <?php echo $student_info['last_login'] ? date('d.m.Y H:i', strtotime($student_info['last_login'])) : translate('never'); ?>
                        </span>
                    </div>
                </div>

                <?php if (isset($error) && !empty($error)): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($success) && !empty($success)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="form-grid">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">
                                <span data-translate-key="first_name"><?php echo translate('first_name'); ?></span> 
                                <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="first_name" 
                                   name="first_name" 
                                   value="<?php echo htmlspecialchars($student_info['first_name']); ?>" 
                                   required>
                        </div>

                        <div class="form-group">
                            <label for="last_name">
                                <span data-translate-key="last_name"><?php echo translate('last_name'); ?></span> 
                                <span class="required">*</span>
                            </label>
                            <input type="text" 
                                   id="last_name" 
                                   name="last_name" 
                                   value="<?php echo htmlspecialchars($student_info['last_name']); ?>" 
                                   required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="username">
                            <span data-translate-key="username"><?php echo translate('username'); ?></span> 
                            <span class="required">*</span>
                        </label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               value="<?php echo htmlspecialchars($student_info['username']); ?>" 
                               required>
                    </div>

                    <div class="password-section">
                        <div class="password-note" data-translate-key="password_change_note">
                            <?php echo translate('password_change_note'); ?>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password" data-translate-key="new_password">
                                    <?php echo translate('new_password'); ?>
                                </label>
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       placeholder="<?php echo translate('password_placeholder'); ?>">
                            </div>

                            <div class="form-group">
                                <label for="confirm_password" data-translate-key="confirm_password">
                                    <?php echo translate('confirm_password'); ?>
                                </label>
                                <input type="password" 
                                       id="confirm_password" 
                                       name="confirm_password" 
                                       placeholder="<?php echo translate('confirm_password_placeholder'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <a href="/teacher/students" class="btn btn-secondary" data-translate-key="cancel">
                            <?php echo translate('cancel'); ?>
                        </a>
                        <button type="submit" name="update_student" class="btn btn-primary" data-translate-key="save_changes">
                            <?php echo translate('save_changes'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/footer.php'; ?>
    <script src="/public/js/security.js"></script>
</body>
</html>
