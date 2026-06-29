<?php
session_start();

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

if (!$row || $row['role'] != 'admin') {
    header('Location: redakt.php');
    exit();
}

$users = $db->query("SELECT id, userLogin, fio FROM users ORDER BY id")->fetchAll();

$selected_user = null;
$editLangs = [];

if (isset($_POST['select_user'])) {
    $user_id = (int)$_POST['user_id'];
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $selected_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($selected_user) {
        $stmt = $db->prepare("SELECT lang_id FROM user_languages WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $editLangs = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование пользователя</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-container">
    <h1>Редактирование пользователя</h1>
    
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        
        <div class="field-group">
            <label>Выберите пользователя</label>
            <select name="user_id" required>
                <option value="">-- Выберите --</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo htmlspecialchars($user['id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($user['id'] . ' - ' . $user['userLogin'] . ' - ' . $user['fio'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" name="select_user">Выбрать</button>
    </form>
    
    <?php if ($selected_user): ?>
        <hr>
        <h2>Редактирование: <?php echo htmlspecialchars($selected_user['userLogin'], ENT_QUOTES, 'UTF-8'); ?></h2>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="error">Ошибка: <?php echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="admin_edit_back.php">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($selected_user['id'], ENT_QUOTES, 'UTF-8'); ?>">
            
            <div class="field-group">
                <label>Логин</label>
                <input type="text" name="userLogin" value="<?php echo htmlspecialchars($selected_user['userLogin'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            
            <div class="field-group">
                <label>Новый пароль (оставьте пустым, если не менять)</label>
                <input type="password" name="userPass">
            </div>
            
            <div class="field-group">
                <label>ФИО</label>
                <input type="text" name="fio" value="<?php echo htmlspecialchars($selected_user['fio'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            
            <div class="field-group">
                <label>Телефон</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($selected_user['phone'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            
            <div class="field-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($selected_user['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            
            <div class="field-group">
                <label>Дата рождения</label>
                <input type="date" name="brithDate" value="<?php echo htmlspecialchars($selected_user['brithDate'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>
            
            <div class="field-group">
                <label>Пол</label>
                <div class="radio-group">
                    <label><input type="radio" name="gender" value="male" <?php echo ($selected_user['gender'] == 'male') ? 'checked' : ''; ?>> Мужской</label>
                    <label><input type="radio" name="gender" value="female" <?php echo ($selected_user['gender'] == 'female') ? 'checked' : ''; ?>> Женский</label>
                </div>
            </div>
            
            <div class="field-group">
                <label>Роль</label>
                <div class="radio-group">
                    <label><input type="radio" name="role" value="user" <?php echo ($selected_user['role'] == 'user') ? 'checked' : ''; ?>> Пользователь</label>
                    <label><input type="radio" name="role" value="admin" <?php echo ($selected_user['role'] == 'admin') ? 'checked' : ''; ?>> Администратор</label>
                </div>
            </div>
            
            <div class="field-group">
                <label>Языки программирования</label>
                <select name="lang_id[]" multiple>
                    <?php
                    $langs = [
                        1 => 'Pascal', 2 => 'C', 3 => 'C++', 4 => 'JavaScript',
                        5 => 'PHP', 6 => 'Python', 7 => 'Java', 8 => 'Haskel',
                        9 => 'Clojure', 10 => 'Prolog', 11 => 'Scala', 12 => 'Go'
                    ];
                    foreach ($langs as $id => $name):
                        $selected = in_array($id, $editLangs) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $id; ?>" <?php echo $selected; ?>><?php echo $name; ?></option>
                    <?php endforeach; ?>
                </select>
                <small>Зажмите Ctrl (Cmd) для выбора нескольких языков</small>
            </div>
            
            <div class="field-group">
                <label>Биография</label>
                <textarea name="bio"><?php echo htmlspecialchars($selected_user['bio'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
            </div>
            
            <div class="field-group">
                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                    <input type="checkbox" name="contract" value="1" <?php echo ($selected_user['contract'] == 1) ? 'checked' : ''; ?>>
                    <span>Согласен с условиями</span>
                </label>
            </div>
            
            <button type="submit">Сохранить изменения</button>
        </form>
    <?php endif; ?>
    
    <div class="links">
        <a href="redakt.php">Назад в профиль</a>
    </div>
</div>

</body>
</html>