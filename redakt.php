<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

// Проверка CSRF
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (empty($_SESSION['uLogin'])) {
        header('Location: ./autorization.php');
        exit();
    }
    
    if (!empty($_GET['save'])) {
        print('Спасибо, результаты сохранены.');
    }
    
    include __DIR__ . '/redaktForm.php';
    exit();
}

// Проверка CSRF
if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
    die('Ошибка безопасности. Попробуйте снова.');
}

$errors_array = [];

if (strlen($_POST['fio']) > 150) {
    $errors_array['fio'] = "Слишком много символов в поле ФИО.";
}
if (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s-]+$/u', $_POST['fio']) && !empty($_POST['fio'])) {
    $errors_array['fio'] = "ФИО должно содержать только буквы, пробелы и дефисы";
}
if (!preg_match('/^[\+\(\)\d\s-]+$/', $_POST['phone']) && !empty($_POST['phone'])) {
    $errors_array['phone'] = "Номер телефона может содержать цифры, +, пробелы, скобки и дефисы.";
}
if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) && !empty($_POST['email'])) {
    $errors_array['email'] = "Введите корректный email адрес.";
}

if (!empty($errors_array)) {
    foreach ($errors_array as $value) {
        print(htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . "<br>");
    }
    exit();
}

$user = 'u82467';
$pass = '5630801';

try {
    $db = new PDO('mysql:host=localhost;dbname=u82467', $user, $pass,
        [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    
    $db->beginTransaction();
    
    $stmt = $db->prepare("SELECT id FROM users WHERE userLogin = :login");
    $stmt->execute([':login' => $_SESSION['uLogin']]);
    $userId = $stmt->fetchColumn();
    
    if (!empty($_POST['fio'])) {
        $stmt = $db->prepare("UPDATE users SET fio = :fio WHERE userLogin = :login");
        $stmt->execute([
            ':fio' => $_POST['fio'],
            ':login' => $_SESSION['uLogin']
        ]);
    }
    
    if (!empty($_POST['phone'])) {
        $stmt = $db->prepare("UPDATE users SET phone = :phone WHERE userLogin = :login");
        $stmt->execute([
            ':phone' => $_POST['phone'],
            ':login' => $_SESSION['uLogin']
        ]);
    }
    
    if (!empty($_POST['email'])) {
        $stmt = $db->prepare("UPDATE users SET email = :email WHERE userLogin = :login");
        $stmt->execute([
            ':email' => $_POST['email'],
            ':login' => $_SESSION['uLogin']
        ]);
    }
    
    if (!empty($_POST['brithDate'])) {
        $stmt = $db->prepare("UPDATE users SET brithDate = :brithDate WHERE userLogin = :login");
        $stmt->execute([
            ':brithDate' => $_POST['brithDate'],
            ':login' => $_SESSION['uLogin']
        ]);
    }
    
    if (!empty($_POST['gender'])) {
        $stmt = $db->prepare("UPDATE users SET gender = :gender WHERE userLogin = :login");
        $stmt->execute([
            ':gender' => $_POST['gender'],
            ':login' => $_SESSION['uLogin']
        ]);
    }
    
    if (!empty($_POST['lang_id'])) {
        $stmt = $db->prepare("DELETE FROM user_languages WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        
        $stmt = $db->prepare("INSERT INTO user_languages (user_id, lang_id) VALUES (:user_id, :lang_id)");
        foreach ($_POST['lang_id'] as $lang_id) {
            $stmt->execute([
                ':user_id' => $userId,
                ':lang_id' => $lang_id
            ]);
        }
    }
    
    if (!empty($_POST['bio'])) {
        $stmt = $db->prepare("UPDATE users SET bio = :bio WHERE userLogin = :login");
        $stmt->execute([
            ':bio' => $_POST['bio'],
            ':login' => $_SESSION['uLogin']
        ]);
    }
    
    $db->commit();
    
} catch (PDOException $e) {
    $db->rollBack();
    // Скрываем детали ошибки
    error_log($e->getMessage());
    print('Произошла ошибка при обновлении данных. Пожалуйста, попробуйте позже.');
    exit();
}

header('Location: ?save=1');
exit();
?>