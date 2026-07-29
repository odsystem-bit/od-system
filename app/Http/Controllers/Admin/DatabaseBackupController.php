<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use PDO;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    /**
     * Télécharge un dump SQL complet de la base de données.
     * Utilise PDO natif (pas besoin de mysqldump sur le serveur).
     */
    public function download(Request $request): BinaryFileResponse
    {
        if (config('database.default') !== 'mysql') {
            abort(500, 'Le backup automatique ne supporte que MySQL.');
        }

        $host     = (string) config('database.connections.mysql.host');
        $port     = (string) config('database.connections.mysql.port', '3306');
        $database = (string) config('database.connections.mysql.database');
        $username = (string) config('database.connections.mysql.username');
        $password = (string) config('database.connections.mysql.password');

        $filename = 'mantota_backup_' . date('Y-m-d_His') . '.sql';
        $filepath = storage_path('app/private/' . $filename);

        // Créer le répertoire si nécessaire
        if (! is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        // Connexion PDO directe — aucune dépendance shell
        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]);

        $sql = $this->generateDump($pdo, $database);
        file_put_contents($filepath, $sql);

        if (! file_exists($filepath) || filesize($filepath) === 0) {
            abort(500, 'Échec du backup : fichier vide.');
        }

        AuditLogService::log('database_backup', 'System', 0, [], [
            'filename' => $filename,
            'size_kb'  => round(filesize($filepath) / 1024, 1),
        ]);

        return response()
            ->download($filepath, $filename, ['Content-Type' => 'application/sql'])
            ->deleteFileAfterSend(true);
    }

    // ──────────────────────────────────────────────
    //  Générateur de dump SQL pur PHP (sans mysqldump)
    // ──────────────────────────────────────────────

    private function generateDump(PDO $pdo, string $database): string
    {
        $lines   = [];
        $lines[] = '-- MANTOTA Database Backup';
        $lines[] = '-- Generated : ' . date('Y-m-d H:i:s T');
        $lines[] = '-- Database  : ' . $database;
        $lines[] = '-- PHP dump  : no shell required';
        $lines[] = '';
        $lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
        $lines[] = "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';";
        $lines[] = 'SET NAMES utf8mb4;';
        $lines[] = '';

        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            $lines[] = '';
            $lines[] = '-- --------------------------------------------------------';
            $lines[] = "-- Table : `{$table}`";
            $lines[] = '-- --------------------------------------------------------';
            $lines[] = "DROP TABLE IF EXISTS `{$table}`;";

            // DDL
            $row     = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            $ddl     = $row['Create Table'] ?? array_values($row)[1] ?? '';
            $lines[] = $ddl . ';';
            $lines[] = '';

            // Data — par blocs de 500 lignes
            $total = (int) $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();

            if ($total > 0) {
                $chunkSize = 500;
                $offset    = 0;

                while ($offset < $total) {
                    $rows = $pdo
                        ->query("SELECT * FROM `{$table}` LIMIT {$chunkSize} OFFSET {$offset}")
                        ->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($rows)) {
                        break;
                    }

                    $columns = '`' . implode('`, `', array_keys($rows[0])) . '`';

                    // Regrouper par 100 pour des INSERT lisibles
                    foreach (array_chunk($rows, 100) as $chunk) {
                        $groups = [];

                        foreach ($chunk as $row) {
                            $vals     = array_map(
                                static fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v),
                                $row
                            );
                            $groups[] = '(' . implode(', ', $vals) . ')';
                        }

                        $lines[] = "INSERT INTO `{$table}` ({$columns}) VALUES";
                        $lines[] = implode(",\n", $groups) . ';';
                    }

                    $offset += $chunkSize;
                }
            }
        }

        $lines[] = '';
        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';

        return implode("\n", $lines);
    }
}
