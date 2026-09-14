<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Configurações de banco de dados (Ajuste conforme o ambiente)
$host = 'localhost';
$db = 'postgres';
$user = 'postgres';
$pass = 'postgres';

try {
    $pdo = new PDO("pgsql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

$action = $_POST['action'] ?? '';

if ($action === 'newsletter') {
    $email = $_POST['email'] ?? '';
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?) ON CONFLICT DO NOTHING");
        $stmt->execute([$email]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid email']);
    }
} elseif ($action === 'contact') {
    $nome = $_POST['nome'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $mensagem = $_POST['mensagem'] ?? '';
    
    if ($nome && $telefone && $mensagem) {
        $stmt = $pdo->prepare("INSERT INTO contact_messages (nome, telefone, mensagem) VALUES (?, ?, ?)");
        $stmt->execute([$nome, $telefone, $mensagem]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing fields']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
