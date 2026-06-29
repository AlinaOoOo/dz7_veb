<?php
// Генерация CSRF токена
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

if (empty($_SESSION['uLogin'])) {
    header('Location: autorization.php');
    exit();
}

$db = new PDO('mysql:host=localhost;dbname=u82467', 'u82467', '5630801');
$stmt = $db->prepare("SELECT role FROM users WHERE userLogin = ?");
$stmt->execute([$_SESSION['uLogin']]);
$row = $stmt->fetch();

$isAdmin = ($row && $row['role'] == 'admin');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование профиля</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-container">
    <h1>Редактирование профиля</h1>
    
    <p style="text-align: center; color: #666; font-size: 14px; margin-bottom: 25px;">
        Оставьте поле пустым, если не хотите его менять
    </p>
    
    <?php if (isset($_GET['save']) && $_GET['save'] == 1): ?>
        <div class="success">Данные успешно обновлены!</div>
    <?php endif; ?>
    
    <form method="POST" action="redakt.php">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        
        <div class="field-group">
            <label>ФИО</label>
            <input type="text" name="fio" placeholder="Новое ФИО">
        </div>
        
        <div class="field-group">
            <label>Телефон</label>
            <input type="tel" name="phone" placeholder="+7 (999) 123-45-67">
        </div>
        
        <div class="field-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="new@email.com">
        </div>
        
        <div class="field-group">
            <label>Дата рождения</label>
            <input type="date" name="brithDate">
        </div>
        
        <div class="field-group">
            <label>Пол</label>
            <div class="radio-group">
                <label><input type="radio" name="gender" value="male"> Мужской</label>
                <label><input type="radio" name="gender" value="female"> Женский</label>
            </div>
        </div>
        
        <div class="field-group">
            <label>Языки программирования</label>
            <select name="lang_id[]" multiple size="6">
                <?php
                $langs = [
                    1 => 'Pascal', 2 => 'C', 3 => 'C++', 4 => 'JavaScript',
                    5 => 'PHP', 6 => 'Python', 7 => 'Java', 8 => 'Haskel',
                    9 => 'Clojure', 10 => 'Prolog', 11 => 'Scala', 12 => 'Go'
                ];
                foreach ($langs as $id => $name):
                ?>
                    <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                <?php endforeach; ?>
            </select>
            <small>Зажмите Ctrl (Cmd) для выбора нескольких языков</small>
        </div>
        
        <div class="field-group">
            <label>Биография</label>
            <textarea name="bio" placeholder="Расскажите о себе..."></textarea>
        </div>
        
        <button type="submit">Сохранить изменения</button>
    </form>
    
    <hr>
    
    <div class="links">
        <a href="index.php">На главную</a>
    </div>
    
    <?php if ($isAdmin): ?>
        <div style="text-align: center; margin-top: 20px;">
            <a href="admin_edit_form.php" style="text-align: center; margin-top: 20px;">
                Админ-панель
            </a>
        </div>
    <?php endif; ?>
    
</div>

</body>
</html>