<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Configuração para Vercel
$dbPath = __DIR__ . '/database.sqlite';

// Função para conectar ao SQLite
function connectDB($dbPath) {
    try {
        $pdo = new PDO("sqlite:$dbPath");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Criar tabelas se não existirem
        createTables($pdo);
        
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

// Função para criar tabelas
function createTables($pdo) {
    $sql = "
    CREATE TABLE IF NOT EXISTS keys (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        key_value TEXT UNIQUE NOT NULL,
        status TEXT DEFAULT 'unused',
        hwid TEXT,
        created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        used_date DATETIME
    );
    
    CREATE TABLE IF NOT EXISTS accounts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password TEXT NOT NULL,
        hwid TEXT NOT NULL,
        activation_key TEXT NOT NULL,
        created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME,
        is_active INTEGER DEFAULT 1
    );
    
    CREATE TABLE IF NOT EXISTS access_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT,
        hwid TEXT NOT NULL,
        action TEXT NOT NULL,
        ip_address TEXT,
        user_agent TEXT,
        created_date DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    ";
    
    $pdo->exec($sql);
}

// Função para gerar HWID único
function generateHWID($computerName, $userName, $serialNumber) {
    return $computerName . '_' . $userName . '_' . $serialNumber;
}

// Função para validar chave
function validateKey($pdo, $key) {
    $stmt = $pdo->prepare("SELECT * FROM keys WHERE key_value = ? AND status = 'unused'");
    $stmt->execute([$key]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Função para marcar chave como usada
function markKeyAsUsed($pdo, $key, $hwid) {
    $stmt = $pdo->prepare("UPDATE keys SET status = 'used', hwid = ?, used_date = datetime('now') WHERE key_value = ?");
    return $stmt->execute([$hwid, $key]);
}

// Função para verificar se chave já foi usada neste PC
function isKeyUsedByThisPC($pdo, $key, $hwid) {
    $stmt = $pdo->prepare("SELECT * FROM keys WHERE key_value = ? AND hwid = ?");
    $stmt->execute([$key, $hwid]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Função para verificar se chave foi usada em outro PC
function isKeyUsedByOtherPC($pdo, $key, $hwid) {
    $stmt = $pdo->prepare("SELECT * FROM keys WHERE key_value = ? AND hwid != ? AND status = 'used'");
    $stmt->execute([$key, $hwid]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Função para salvar conta
function saveAccount($pdo, $username, $password, $hwid, $key) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO accounts (username, password, hwid, activation_key) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$username, $hashedPassword, $hwid, $key]);
}

// Função para verificar conta
function checkAccount($pdo, $username, $password, $hwid) {
    $stmt = $pdo->prepare("SELECT * FROM accounts WHERE username = ?");
    $stmt->execute([$username]);
    $account = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($account && password_verify($password, $account['password'])) {
        return $account['hwid'] === $hwid;
    }
    return false;
}

// Função para verificar se usuário existe
function userExists($pdo, $username) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM accounts WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetchColumn() > 0;
}

// Função para log de acesso
function logAccess($pdo, $username, $hwid, $action) {
    $stmt = $pdo->prepare("INSERT INTO access_logs (username, hwid, action, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $username,
        $hwid,
        $action,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
}

// Processar requisição
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'OPTIONS') {
    exit(0);
}

$pdo = connectDB($dbPath);
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$response = ['success' => false, 'message' => 'Invalid action'];

switch ($action) {
    case 'validate_key':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $key = $data['key'] ?? '';
            $computerName = $data['computer_name'] ?? '';
            $userName = $data['user_name'] ?? '';
            $serialNumber = $data['serial_number'] ?? '';
            
            $hwid = generateHWID($computerName, $userName, $serialNumber);
            
            logAccess($pdo, null, $hwid, 'key_validation');
            
            // Verificar se chave já foi usada neste PC
            if (isKeyUsedByThisPC($pdo, $key, $hwid)) {
                $response = ['success' => true, 'message' => 'Key already used on this PC', 'hwid' => $hwid];
            }
            // Verificar se chave foi usada em outro PC
            elseif (isKeyUsedByOtherPC($pdo, $key, $hwid)) {
                $response = ['success' => false, 'message' => 'Key already used on another PC'];
            }
            // Verificar se chave é válida
            elseif (validateKey($pdo, $key)) {
                if (markKeyAsUsed($pdo, $key, $hwid)) {
                    $response = ['success' => true, 'message' => 'Key validated successfully', 'hwid' => $hwid];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to mark key as used'];
                }
            } else {
                $response = ['success' => false, 'message' => 'Invalid or already used key'];
            }
        }
        break;
        
    case 'create_account':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';
            $hwid = $data['hwid'] ?? '';
            $key = $data['key'] ?? '';
            
            if (strlen($username) < 3) {
                $response = ['success' => false, 'message' => 'Username must have at least 3 characters'];
            } elseif (strlen($password) < 4) {
                $response = ['success' => false, 'message' => 'Password must have at least 4 characters'];
            } elseif (userExists($pdo, $username)) {
                $response = ['success' => false, 'message' => 'Username already exists'];
            } elseif (empty($key)) {
                $response = ['success' => false, 'message' => 'Activation key not validated'];
            } else {
                if (saveAccount($pdo, $username, $password, $hwid, $key)) {
                    logAccess($pdo, $username, $hwid, 'account_creation');
                    $response = ['success' => true, 'message' => 'Account created successfully'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to create account'];
                }
            }
        }
        break;
        
    case 'login':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $username = $data['username'] ?? '';
            $password = $data['password'] ?? '';
            $computerName = $data['computer_name'] ?? '';
            $userName = $data['user_name'] ?? '';
            $serialNumber = $data['serial_number'] ?? '';
            
            $hwid = generateHWID($computerName, $userName, $serialNumber);
            
            if (checkAccount($pdo, $username, $password, $hwid)) {
                logAccess($pdo, $username, $hwid, 'login');
                $response = ['success' => true, 'message' => 'Login successful'];
            } else {
                logAccess($pdo, $username, $hwid, 'failed_login');
                $response = ['success' => false, 'message' => 'Invalid username, password or HWID mismatch'];
            }
        }
        break;
        
    case 'generate_keys':
        if ($method === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $count = $data['count'] ?? 1;
            $prefix = $data['prefix'] ?? 'GOU';
            
            $keys = [];
            for ($i = 0; $i < $count; $i++) {
                $key = $prefix . '-' . strtoupper(substr(md5(uniqid()), 0, 4)) . '-' . 
                       strtoupper(substr(md5(uniqid()), 0, 4)) . '-' . 
                       strtoupper(substr(md5(uniqid()), 0, 4));
                
                $stmt = $pdo->prepare("INSERT INTO keys (key_value, status) VALUES (?, 'unused')");
                if ($stmt->execute([$key])) {
                    $keys[] = $key;
                }
            }
            
            $response = ['success' => true, 'message' => 'Keys generated successfully', 'keys' => $keys];
        }
        break;
        
    case 'get_stats':
        if ($method === 'GET') {
            $stmt = $pdo->query("SELECT COUNT(*) as total_keys FROM keys");
            $totalKeys = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as used_keys FROM keys WHERE status = 'used'");
            $usedKeys = $stmt->fetchColumn();
            
            $stmt = $pdo->query("SELECT COUNT(*) as total_accounts FROM accounts");
            $totalAccounts = $stmt->fetchColumn();
            
            $response = [
                'success' => true,
                'stats' => [
                    'total_keys' => $totalKeys,
                    'used_keys' => $usedKeys,
                    'unused_keys' => $totalKeys - $usedKeys,
                    'total_accounts' => $totalAccounts
                ]
            ];
        }
        break;
}

echo json_encode($response);
?> 