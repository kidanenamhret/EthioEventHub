<?php
require_once '../includes/config.php';
require_once '../includes/session.php';
requireLogin();

if ($_SESSION['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

require_once '../includes/db.php';
require_once '../includes/activity_logger.php';

if (isset($_GET['action']) && $_GET['action'] === 'generate') {
    try {
        $pdo = getDB();
        $tables = [];
        $result = $pdo->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $return = "";
        foreach ($tables as $table) {
            $result = $pdo->query("SELECT * FROM $table");
            $numFields = $result->columnCount();

            $return .= "DROP TABLE IF EXISTS $table;";
            $row2 = $pdo->query("SHOW CREATE TABLE $table")->fetch(PDO::FETCH_NUM);
            $return .= "\n\n" . $row2[1] . ";\n\n";

            while ($row = $result->fetch(PDO::FETCH_NUM)) {
                $return .= "INSERT INTO $table VALUES(";
                for ($j = 0; $j < $numFields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n", "\\n", $row[$j]);
                    if (isset($row[$j])) { $return .= '"' . $row[$j] . '"'; } else { $return .= '""'; }
                    if ($j < ($numFields - 1)) { $return .= ','; }
                }
                $return .= ");\n";
            }
            $return .= "\n\n\n";
        }

        // Log the backup action
        logActivity('DB_BACKUP_GENERATED', "Database backup created by admin");

        // Force Download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="EthioEventHub_Backup_'.date('Y-m-d_H-i-s').'.sql"');
        echo $return;
        exit;

    } catch (Exception $e) {
        die("Backup failed: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>System Maintenance - EthioEvent Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-dark text-white">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card bg-white bg-opacity-5 border-white border-opacity-10 p-5 rounded-4 shadow-lg text-center">
                    <i class="fas fa-database fa-4x text-accent mb-4"></i>
                    <h2 class="fw-bold mb-3">System Backup & Maintenance</h2>
                    <p class="text-white-50 mb-5">Securely export your entire database for safekeeping. This backup includes all events, users, bookings, and logs.</p>
                    
                    <div class="d-grid gap-3">
                        <a href="?action=generate" class="btn btn-primary btn-lg py-3 rounded-pill fw-bold">
                            <i class="fas fa-download me-2"></i> GENERATE FULL SQL BACKUP
                        </a>
                        <a href="admin_dashboard.php" class="btn btn-outline-light py-3 rounded-pill">
                            RETURN TO COMMAND CENTER
                        </a>
                    </div>

                    <div class="mt-5 p-4 rounded-3 bg-white bg-opacity-5 text-start">
                        <h6 class="small fw-bold text-uppercase text-accent mb-3">Best Practices:</h6>
                        <ul class="small text-white-50 mb-0">
                            <li>Run a backup before major platform updates.</li>
                            <li>Store your exported .sql files in a secure, offline location.</li>
                            <li>Activity is logged: Every backup action is tracked in the audit trail.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
