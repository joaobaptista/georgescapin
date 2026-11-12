<?php

declare(strict_types=1);

/**
 * Consulta a trilha de auditoria: quem acessou ou alterou dados sensíveis.
 *
 *   php scripts/audit-log.php --patient=12            acessos ao prontuário do paciente 12
 *   php scripts/audit-log.php --user=5 [--limit=100]  ações do usuário 5
 *   php scripts/audit-log.php                         últimas ações
 */

use App\Infrastructure\Persistence\Db;

if (PHP_SAPI !== 'cli') {
    exit(1);
}
$container = require dirname(__DIR__) . '/bootstrap/app.php';
$db = $container->get(Db::class);

$options = getopt('', ['patient:', 'user:', 'limit:']);
$where = [];
$params = [];
if (isset($options['patient'])) {
    $where[] = 'l.patient_id = ?';
    $params[] = (int) $options['patient'];
}
if (isset($options['user'])) {
    $where[] = 'l.actor_user_id = ?';
    $params[] = (int) $options['user'];
}
$limit = max(1, min(1000, (int) ($options['limit'] ?? 50)));

$rows = $db->all(
    'SELECT l.created_at, l.action, l.actor_user_id, u.name AS actor, l.patient_id, l.subject_type, l.subject_id, l.ip
     FROM audit_logs l LEFT JOIN users u ON u.id = l.actor_user_id'
    . ($where !== [] ? ' WHERE ' . implode(' AND ', $where) : '')
    . " ORDER BY l.created_at DESC, l.id DESC LIMIT {$limit}",
    $params,
);

if ($rows === []) {
    echo "Nenhum registro encontrado.\n";
    exit(0);
}
foreach ($rows as $row) {
    printf(
        "%s  %-30s  %-28s  paciente %-6s %s  ip %s\n",
        $row['created_at'],
        $row['action'],
        ($row['actor'] ?? '—') . ($row['actor_user_id'] ? " (#{$row['actor_user_id']})" : ''),
        $row['patient_id'] ?? '—',
        $row['subject_type'] ? "{$row['subject_type']} #{$row['subject_id']}" : '',
        $row['ip'] ?? '—',
    );
}
