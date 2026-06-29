<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

// Проверка CSRF для POST
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function Generator($size){
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for($i=0;$i<$size;$i++){
        $randomString .= $characters[random_int(0, $charactersLength - 1)];
    }
    return $randomString;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!empty($_GET['save'])) {
        print('Спасибо, результаты сохранены.');
        print('Ваш логин: ' . htmlspecialchars($_SESSION['uLogin'] ?? '', ENT_QUOTES, 'UTF-8'));
        print('Ваш пароль: ' . htmlspecialchars($_SESSION['uPass'] ?? '', ENT_QUOTES, 'UTF-8'));
        setcookie('errors_array', '', time() - 3600, '/');
    }
    include __DIR__ . '/form.php';
    exit();
}

// Проверка CSRF
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    die('Ошибка безопасности. Попробуйте снова.');
}

$errors_array = [];

if (empty($_POST['fio'])) {
    $errors_array['fio'] = "Заполните имя.";
} elseif (strlen($_POST['fio']) > 150) {
    $errors_array['fio'] = "Слишком много символов в поле ФИО.";
} elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s-]+$/u', $_POST['fio'])) {
    $errors_array['fio'] = "ФИО должно содержать только буквы, пробелы и дефисы";
}

if (empty($_POST['phone'])) {
    $errors_array['phone'] = "Заполните номер телефона.";
} elseif (!preg_match('/^[\+\(\)\d\s-]+$/', $_POST['phone'])) {
    $errors_array['phone'] = "Номер телефона может содержать цифры, +, пробелы, скобки и дефисы.";
}

if (empty($_POST['email'])) {
    $errors_array['email'] = "Заполните почту.";
} elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
    $errors_array['email'] = "Введите корректный email адрес.";
}

if (empty($_POST['brithDate'])) {
    $errors_array['brithDate'] = "Заполните Дату рождения.";
}

if (empty($_POST['gender'])) {
    $errors_array['gender'] = "Выберите пол.";
}

if (empty($_POST['lang_id'])) {
    $errors_array['lang_id'] = "Выберите язык программирования.";
}

if (empty($_POST['contract'])) {
    $errors_array['contract'] = "Необходимо согласие с условиями.";
}

if (!empty($errors_array)) {
    setcookie('saved_fio', $_POST['fio'], 0, '/');
    setcookie('saved_phone', $_POST['phone'], 0, '/');
    setcookie('saved_email', $_POST['email'], 0, '/');
    setcookie('saved_brithDate', $_POST['brithDate'], 0, '/');
    setcookie('saved_gender', $_POST['gender'], 0, '/');
    setcookie('saved_bio', $_POST['bio'], 0, '/');
    
    if (!empty($_POST['lang_id'])) {
        setcookie('saved_lang_id', json_encode($_POST['lang_id']), 0, '/');
    } else {
        setcookie('saved_lang_id', '', time() - 3600, '/');
    }
    
    setcookie('errors_array', json_encode($errors_array), 0, '/');
    header('Location: ./');
    exit();
}

$one_year = time() + 31536000;
setcookie('saved_fio', $_POST['fio'], $one_year, '/');
setcookie('saved_phone', $_POST['phone'], $one_year, '/');
setcookie('saved_email', $_POST['email'], $one_year, '/');
setcookie('saved_brithDate', $_POST['brithDate'], $one_year, '/');
setcookie('saved_gender', $_POST['gender'], $one_year, '/');
setcookie('saved_bio', $_POST['bio'], $one_year, '/');

if (!empty($_POST['lang_id'])) {
    setcookie('saved_lang_id', json_encode($_POST['lang_id']), $one_year, '/');
}

setcookie('errors_array', '', time() - 3600, '/');

$userLogin = Generator(7);
$userPass = Generator(8);
$_SESSION['uLogin'] = $userLogin;
$_SESSION['uPass'] = $userPass;

$userPassHashed = password_hash($userPass, PASSWORD_DEFAULT);

$user = 'u82467';
$pass = '5630801';

try {
    $db = new PDO('mysql:host=localhost;dbname=u82467', $user, $pass,
        [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $stmt = $db->prepare("INSERT INTO users (fio, phone, email, brithDate, gender, bio, contract, userLogin, userPass) 
                          VALUES (:fio, :phone, :email, :brithDate, :gender, :bio, :contract, :userLogin, :userPass)");
    $stmt->execute([
        ':fio' => $_POST['fio'],
        ':phone' => $_POST['phone'],
        ':email' => $_POST['email'],
        ':brithDate' => $_POST['brithDate'],
        ':gender' => $_POST['gender'],  
        ':bio' => $_POST['bio'],
        ':contract' => isset($_POST['contract']) ? 1 : 0,
        ':userLogin' => $userLogin,
        ':userPass' => $userPassHashed
    ]);
    
    $user_id = $db->lastInsertId();
    
    $stmt = $db->prepare("INSERT INTO user_languages (user_id, lang_id) VALUES (:user_id, :lang_id)");
    foreach ($_POST['lang_id'] as $lang_id) {
        $stmt->execute([
            ':user_id' => $user_id,
            ':lang_id' => $lang_id
        ]);
    }
} catch(PDOException $e) {
    // Скрываем детали ошибки
    error_log($e->getMessage());
    print('Произошла ошибка при регистрации. Пожалуйста, попробуйте позже.');
    exit();
}

header('Location: ?save=1');
exit();
?>