<?php
/**
 * Admin Export - CSV and PDF
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/lang.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}

// Get parameters
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$userId = $_GET['user_id'] ?? '';
$format = $_GET['format'] ?? 'csv';

$db = getDB();
if (!$db) {
    die('Database error');
}

// Build query
$where = [];
$params = [];

if ($dateFrom) {
    $where[] = "cl.date >= ?";
    $params[] = $dateFrom;
}

if ($dateTo) {
    $where[] = "cl.date <= ?";
    $params[] = $dateTo;
}

if ($userId) {
    $where[] = "cl.user_id = ?";
    $params[] = $userId;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get logs
$query = "SELECT u.name, cl.date, 
    TIME(cl.checkin_time) as checkin_time, 
    TIME(cl.checkout_time) as checkout_time,
    cl.duration_minutes,
    cl.status
    FROM check_log cl 
    JOIN users u ON cl.user_id = u.id 
    $whereClause
    ORDER BY cl.date DESC, cl.checkin_time DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Calculate totals
$totalMinutes = 0;
foreach ($logs as $log) {
    $totalMinutes += $log['duration_minutes'] ?? 0;
}
$totalHours = floor($totalMinutes / 60);
$totalMins = $totalMinutes % 60;

if ($format === 'csv') {
    // CSV Export
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="checktrack_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Header
    fputcsv($output, ['Name', 'Date', 'Check-in', 'Check-out', 'Duration', 'Status']);
    
    // Data
    foreach ($logs as $log) {
        $duration = '';
        if ($log['duration_minutes']) {
            $h = floor($log['duration_minutes'] / 60);
            $m = $log['duration_minutes'] % 60;
            $duration = $h > 0 ? "{$h}h {$m}m" : "{$m}m";
        }
        
        fputcsv($output, [
            $log['name'],
            $log['date'],
            $log['checkin_time'] ?? '',
            $log['checkout_time'] ?? '',
            $duration,
            $log['status']
        ]);
    }
    
    // Footer
    fputcsv($output, []);
    fputcsv($output, ['Total Records:', count($logs), '', '', "{$totalHours}h {$totalMins}m", '']);
    
    fclose($output);
    exit;
    
} else {
    // PDF Export (HTML-based)
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>CheckTrack Attendance Report</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 20px;
                color: #333;
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 2px solid #3b82f6;
                padding-bottom: 20px;
            }
            .header h1 {
                color: #3b82f6;
                margin: 0;
            }
            .header p {
                color: #666;
                margin: 5px 0;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 20px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 10px;
                text-align: left;
            }
            th {
                background-color: #f3f4f6;
                font-weight: bold;
            }
            .footer {
                margin-top: 30px;
                padding-top: 20px;
                border-top: 1px solid #ddd;
                text-align: right;
            }
            @media print {
                body { margin: 0; }
                .no-print { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>CheckTrack Attendance Report</h1>
            <p>
                <?php if ($dateFrom || $DateTo): ?>
                    Period: <?php echo $dateFrom ?: 'Start'; ?> to <?php echo $dateTo ?: 'End'; ?>
                <?php else ?>
                    All Records
                <?php endif; ?>
            </p>
            <p>Generated: <?php echo date('d/m/Y H:i'); ?></p>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Duration</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($log['name']); ?></td>
                        <td><?php echo $log['date']; ?></td>
                        <td><?php echo $log['checkin_time'] ?? '--'; ?></td>
                        <td><?php echo $log['checkout_time'] ?? '--'; ?></td>
                        <td>
                            <?php 
                            if ($log['duration_minutes']) {
                                $h = floor($log['duration_minutes'] / 60);
                                $m = $log['duration_minutes'] % 60;
                                echo $h > 0 ? "{$h}h {$m}m" : "{$m}m";
                            } else {
                                echo '--';
                            }
                            ?>
                        </td>
                        <td><?php echo ucfirst($log['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="footer">
            <p><strong>Total Records:</strong> <?php echo count($logs); ?></p>
            <p><strong>Total Hours:</strong> <?php echo $totalHours; ?>h <?php echo $totalMins; ?>m</p>
        </div>
        
        <div class="no-print" style="text-align: center; margin-top: 30px;">
            <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">
                Print Report
            </button>
        </div>
        
        <script>
            // Auto-print on load
            window.onload = function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            };
        </script>
    </body>
    </html>
    <?php
    exit;
}
