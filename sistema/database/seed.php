<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use App\Core\Auth;
use App\Core\Database;
use Dotenv\Dotenv;

if (file_exists(dirname(__DIR__) . '/.env')) {
    $dotenv = Dotenv::createImmutable(dirname(__DIR__));
    $dotenv->safeLoad();
}

echo "Populando banco de dados com dados iniciais...\n";

$db = Database::getConnection();

$driver = $_ENV['DB_CONNECTION'] ?? 'sqlite';

// Desativar restrições de FK para limpeza
if ($driver === 'mysql') {
    $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
}

// Limpar tabelas
$db->exec("DELETE FROM password_reset_tokens");
$db->exec("DELETE FROM personal_access_tokens");
$db->exec("DELETE FROM medical_records");
$db->exec("DELETE FROM appointments");
$db->exec("DELETE FROM procedures");
$db->exec("DELETE FROM patients");
$db->exec("DELETE FROM professionals");
$db->exec("DELETE FROM users");

if ($driver === 'mysql') {
    $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
}

$now = date('Y-m-d H:i:s');
$password = Auth::hashPassword('senha123');

// 1. Criar Médico Dr. Gabriel
$proUserId = Database::insert(
    "INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, 'professional', ?, ?)",
    ['Dr. Gabriel Silva', 'dr.gabriel@nuva.com.br', $password, $now, $now]
);

$proId = Database::insert(
    "INSERT INTO professionals (user_id, specialty, crm, address_city, address_state, bio, rating, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
    [$proUserId, 'Médico Dermatologista / Esteta', 'CRM/SP 123456', 'São Paulo', 'SP', 'Especialista em procedimentos estéticos faciais e corporais com mais de 10 anos de experiência.', 5.0, $now, $now]
);

// 2. Procedimentos
$proc1 = Database::insert(
    "INSERT INTO procedures (professional_id, name, category, description, base_price, duration_minutes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
    [$proId, 'Toxina Botulínica', 'Harmonização Facial', 'Aplicação de toxina botulínica para rugas de expressão.', 1200.0, 45, $now, $now]
);

$proc2 = Database::insert(
    "INSERT INTO procedures (professional_id, name, category, description, base_price, duration_minutes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
    [$proId, 'Preenchimento Labial', 'Preenchedores', 'Preenchimento labial com ácido hialurônico para volume e contorno.', 1500.0, 60, $now, $now]
);

$proc3 = Database::insert(
    "INSERT INTO procedures (professional_id, name, category, description, base_price, duration_minutes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
    [$proId, 'Fios de PDO', 'Bioestimuladores', 'Tração e estímulo de colágeno facial.', 2200.0, 90, $now, $now]
);

// 3. Pacientes
$pat1UserId = Database::insert(
    "INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, 'patient', ?, ?)",
    ['Alegra Urach', 'alegra@example.com', $password, $now, $now]
);
$pat1Id = Database::insert(
    "INSERT INTO patients (user_id, professional_id, cpf, phone, birth_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
    [$pat1UserId, $proId, '972.371.590-20', '(11) 98765-4321', '1995-04-12', $now, $now]
);

$pat2UserId = Database::insert(
    "INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, 'patient', ?, ?)",
    ['Jheniffer Machado', 'jheniffer@example.com', $password, $now, $now]
);
$pat2Id = Database::insert(
    "INSERT INTO patients (user_id, professional_id, cpf, phone, birth_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
    [$pat2UserId, $proId, '123.456.789-00', '(11) 91122-3344', '1992-08-23', $now, $now]
);

$pat3UserId = Database::insert(
    "INSERT INTO users (name, email, password, role, created_at, updated_at) VALUES (?, ?, ?, 'patient', ?, ?)",
    ['Jefferson Rubim', 'jefferson@example.com', $password, $now, $now]
);
$pat3Id = Database::insert(
    "INSERT INTO patients (user_id, professional_id, cpf, phone, birth_date, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)",
    [$pat3UserId, $proId, '987.654.321-11', '(11) 99988-7766', '1988-11-30', $now, $now]
);

// 4. Consultas / Agendamentos
$sched1 = date('Y-m-d 14:30:00', strtotime('+1 day'));
Database::insert(
    "INSERT INTO appointments (patient_id, professional_id, procedure_id, scheduled_at, status, notes, created_at, updated_at) VALUES (?, ?, ?, ?, 'confirmed', 'Primeira sessão de botox testa e glabela', ?, ?)",
    [$pat1Id, $proId, $proc1, $sched1, $now, $now]
);

$sched2 = date('Y-m-d 10:00:00', strtotime('+3 days'));
Database::insert(
    "INSERT INTO appointments (patient_id, professional_id, procedure_id, scheduled_at, status, notes, created_at, updated_at) VALUES (?, ?, ?, ?, 'scheduled', 'Avaliação de preenchimento labial', ?, ?)",
    [$pat2Id, $proId, $proc2, $sched2, $now, $now]
);

// 5. Prontuários
$pastDate = date('Y-m-d H:i:s', strtotime('-7 days'));
Database::insert(
    "INSERT INTO medical_records (patient_id, professional_id, clinical_notes, prescription, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)",
    [
        $pat1Id,
        $proId,
        "Avaliação inicial realizada. Sem alergias relatadas. Queixa principal: linhas de expressão na região frontal.",
        "Evitar exposição solar intensa nas primeiras 48h pós-procedimento. Uso de protetor solar FPS 50+.",
        $pastDate,
        $pastDate,
    ]
);

echo "✅ Banco de dados populado com sucesso!\n";
echo "--- Usuários criados ---\n";
echo "👨‍⚕️ Médico: dr.gabriel@nuva.com.br / senha123\n";
echo "👤 Paciente 1: alegra@example.com / senha123\n";
echo "👤 Paciente 2: jheniffer@example.com / senha123\n";
echo "👤 Paciente 3: jefferson@example.com / senha123\n";
