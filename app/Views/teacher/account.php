<?php
$page_title = "Настройки аккаунта";
$page_scripts = [];
$inline_scripts = '';

include __DIR__ . '/header.php';
?>

<style>
.account-section {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    margin-bottom: 2rem;
}

.account-section-header {
    background: var(--light-color);
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e9ecef;
    border-radius: var(--border-radius) var(--border-radius) 0 0;
}

.account-section-header h5 {
    margin: 0;
    color: var(--dark-color);
}

.account-section-body {
    padding: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-row {
    display: flex;
    gap: 1rem;
}

.form-row .form-group {
    flex: 1;
}

.password-strength {
    margin-top: 0.5rem;
}

.password-requirements {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: var(--border-radius);
    padding: 1rem;
    margin-top: 1rem;
}

.password-requirements h6 {
    margin-bottom: 0.5rem;
    color: var(--dark-color);
}

.password-requirements ul {
    margin: 0;
    padding-left: 1.5rem;
}

.password-requirements li {
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
    color: var(--secondary-color);
}

.password-requirements li.valid {
    color: var(--success-color);
}

.password-requirements li.invalid {
    color: var(--danger-color);
}

.profile-avatar {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    background: var(--primary-color);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    margin-bottom: 1rem;
}

.profile-info {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.profile-details h4 {
    margin: 0;
    color: var(--dark-color);
}

.profile-details .text-muted {
    margin: 0;
}

.danger-zone {
    border: 1px solid var(--danger-color);
    border-radius: var(--border-radius);
    background: #fff5f5;
}

.danger-zone .account-section-header {
    background: var(--danger-color);
    color: white;
    border-bottom-color: var(--danger-color);
}

@media (max-width: 767.98px) {
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .profile-info {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1><i class="fas fa-user-cog"></i> Настройки аккаунта</h1>
</div>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i>
        <?php echo htmlspecialchars($success_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo htmlspecialchars($error_message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Профиль пользователя -->
<div class="account-section">
    <div class="account-section-header">
        <h5><i class="fas fa-user"></i> Профиль пользователя</h5>
    </div>
    <div class="account-section-body">
        <div class="profile-info">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($teacher_info['first_name'], 0, 1) . substr($teacher_info['last_name'], 0, 1)); ?>
            </div>
            <div class="profile-details">
                <h4><?php echo htmlspecialchars($teacher_info['first_name'] . ' ' . $teacher_info['last_name']); ?></h4>
                <p class="text-muted">@<?php echo htmlspecialchars($teacher_info['username']); ?></p>
                <p class="text-muted">
                    <i class="fas fa-envelope"></i>
                    <?php echo htmlspecialchars($teacher_info['email']); ?>
                    <?php if ($teacher_info['email_verified']): ?>
                        <span class="badge bg-success ms-2">
                            <i class="fas fa-check"></i> Подтвержден
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning ms-2">
                            <i class="fas fa-clock"></i> Не подтвержден
                        </span>
                    <?php endif; ?>
                </p>
                <p class="text-muted">
                    <i class="fas fa-calendar"></i>
                    Зарегистрирован: <?php echo date('d.m.Y', strtotime($teacher_info['created_at'])); ?>
                </p>
            </div>
        </div>
        
        <form method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="action" value="update_profile">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name" class="form-label">Имя *</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" 
                           value="<?php echo htmlspecialchars($teacher_info['first_name']); ?>" required>
                    <div class="invalid-feedback">Пожалуйста, введите имя.</div>
                </div>
                
                <div class="form-group">
                    <label for="last_name" class="form-label">Фамилия *</label>
                    <input type="text" class="form-control" id="last_name" name="last_name" 
                           value="<?php echo htmlspecialchars($teacher_info['last_name']); ?>" required>
                    <div class="invalid-feedback">Пожалуйста, введите фамилию.</div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="username" class="form-label">Имя пользователя *</label>
                <input type="text" class="form-control" id="username" name="username" 
                       value="<?php echo htmlspecialchars($teacher_info['username']); ?>" required minlength="3">
                <div class="form-text">Минимум 3 символа. Только буквы, цифры, дефис и подчеркивание.</div>
                <div class="invalid-feedback">Имя пользователя должно содержать минимум 3 символа.</div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                Сохранить изменения
            </button>
        </form>
    </div>
</div>

<!-- Смена пароля -->
<div class="account-section">
    <div class="account-section-header">
        <h5><i class="fas fa-lock"></i> Смена пароля</h5>
    </div>
    <div class="account-section-body">
        <form method="POST" class="needs-validation" novalidate id="passwordForm">
            <input type="hidden" name="action" value="change_password">
            
            <div class="form-group">
                <label for="current_password" class="form-label">Текущий пароль *</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="invalid-feedback">Пожалуйста, введите текущий пароль.</div>
            </div>
            
            <div class="form-group">
                <label for="new_password" class="form-label">Новый пароль *</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-strength" id="passwordStrength"></div>
                <div class="invalid-feedback">Новый пароль должен содержать минимум 6 символов.</div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password" class="form-label">Подтверждение пароля *</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="invalid-feedback">Пароли не совпадают.</div>
            </div>
            
            <div class="password-requirements">
                <h6>Требования к паролю:</h6>
                <ul id="passwordRequirements">
                    <li id="req-length">Минимум 6 символов</li>
                    <li id="req-letter">Содержит буквы</li>
                    <li id="req-number">Содержит цифры</li>
                    <li id="req-match">Пароли совпадают</li>
                </ul>
            </div>
            
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-key"></i>
                Изменить пароль
            </button>
        </form>
    </div>
</div>

<!-- Статистика аккаунта -->
<div class="account-section">
    <div class="account-section-header">
        <h5><i class="fas fa-chart-line"></i> Статистика</h5>
    </div>
    <div class="account-section-body">
        <div class="row">
            <div class="col-md-3 col-6">
                <div class="text-center">
                    <div class="stats-number"><?php echo $total_students ?? 0; ?></div>
                    <div class="stats-label">Студентов</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-center">
                    <div class="stats-number"><?php echo $total_decks ?? 0; ?></div>
                    <div class="stats-label">Колод</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-center">
                    <div class="stats-number"><?php echo $total_tests ?? 0; ?></div>
                    <div class="stats-label">Тестов</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-center">
                    <div class="stats-number"><?php echo $total_words ?? 0; ?></div>
                    <div class="stats-label">Слов</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Переключение видимости пароля
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Проверка силы пароля
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const requirements = {
        length: password.length >= 6,
        letter: /[a-zA-Z]/.test(password),
        number: /[0-9]/.test(password)
    };
    
    // Обновляем визуальные индикаторы
    updateRequirement('req-length', requirements.length);
    updateRequirement('req-letter', requirements.letter);
    updateRequirement('req-number', requirements.number);
    
    // Показываем индикатор силы
    const strength = Object.values(requirements).filter(Boolean).length;
    const strengthBar = document.getElementById('passwordStrength');
    const strengthClasses = ['weak', 'fair', 'good', 'strong'];
    const strengthTexts = ['Слабый', 'Удовлетворительный', 'Хороший', 'Сильный'];
    
    strengthBar.className = 'password-strength ' + (strengthClasses[strength - 1] || '');
    strengthBar.innerHTML = strength > 0 ? 
        `<div class="password-strength-bar"><div class="password-strength-fill"></div></div>
         <div class="password-strength-text">${strengthTexts[strength - 1] || ''}</div>` : '';
});

// Проверка совпадения паролей
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    const match = newPassword === confirmPassword && confirmPassword.length > 0;
    
    updateRequirement('req-match', match);
    
    if (confirmPassword.length > 0) {
        if (match) {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        } else {
            this.classList.remove('is-valid');
            this.classList.add('is-invalid');
        }
    }
});

function updateRequirement(id, valid) {
    const element = document.getElementById(id);
    if (valid) {
        element.className = 'valid';
        element.innerHTML = '✓ ' + element.textContent.replace('✓ ', '').replace('✗ ', '');
    } else {
        element.className = 'invalid';
        element.innerHTML = '✗ ' + element.textContent.replace('✓ ', '').replace('✗ ', '');
    }
}

// Bootstrap form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php include __DIR__ . '/footer.php'; ?>
