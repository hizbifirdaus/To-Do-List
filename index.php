<?php
/**
 * APLIKASI TO-DO LIST SEDERHANA
 *
 * File ini menangani semua logika untuk menambah, mengubah status, dan menghapus tugas.
 * State (data tugas) disimpan dalam PHP Session untuk persistensi selama pengguna aktif.
 *
 * @version 2.0
 * @author  HizZ
 */

// Memulai session untuk menyimpan data tugas antar request.
session_start();

// Inisialisasi data tugas jika session masih kosong (pertama kali membuka aplikasi).
if (!isset($_SESSION['tasks'])) {
    $_SESSION['tasks'] = [
        ['id' => uniqid(), 'title' => 'Install Bootstrap 5', 'status' => 'selesai'],
        ['id' => uniqid(), 'title' => 'Refactor kode ke Session', 'status' => 'selesai'],
        ['id' => uniqid(), 'title' => 'Tambah fitur hapus tugas', 'status' => 'belum'],
    ];
}

/**
 * Fungsi untuk mencari index sebuah tugas di dalam array berdasarkan ID.
 * Menggunakan fungsi ini menghindari duplikasi kode pencarian.
 *
 * @param string $taskId ID tugas yang dicari.
 * @return int|null Index dari tugas jika ditemukan, atau null jika tidak.
 */
function find_task_index_by_id($taskId)
{
    foreach ($_SESSION['tasks'] as $index => $task) {
        if ($task['id'] === $taskId) {
            return $index;
        }
    }
    return null;
}

// Menangani request POST dari form (tambah, ubah status, hapus).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        // Menambah tugas baru
        case 'add':
            $taskTitle = trim($_POST['title'] ?? '');
            if (!empty($taskTitle)) {
                $newTask = [
                    'id'     => uniqid(), // Menggunakan ID unik
                    'title'  => $taskTitle,
                    'status' => 'belum'
                ];
                $_SESSION['tasks'][] = $newTask;
            }
            break;

        // Mengubah status tugas (selesai/belum)
        case 'toggle':
            $taskId = $_POST['id'] ?? '';
            $taskIndex = find_task_index_by_id($taskId);
            if ($taskIndex !== null) {
                $currentStatus = $_SESSION['tasks'][$taskIndex]['status'];
                $_SESSION['tasks'][$taskIndex]['status'] = ($currentStatus === 'selesai') ? 'belum' : 'selesai';
            }
            break;
        
        // Menghapus tugas
        case 'delete':
            $taskId = $_POST['id'] ?? '';
            // Gunakan array_filter untuk menghapus elemen secara efisien
            $_SESSION['tasks'] = array_filter($_SESSION['tasks'], function ($task) use ($taskId) {
                return $task['id'] !== $taskId;
            });
            // Re-index array agar tidak ada "lubang"
            $_SESSION['tasks'] = array_values($_SESSION['tasks']);
            break;
    }

    // Redirect kembali ke halaman utama untuk mencegah re-submission form (P-R-G Pattern)
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✅ To-Do List Interaktif</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        /* Custom style untuk melengkapi Bootstrap */
        body { background-color: #f8f9fa; }
        .task-title.selesai { text-decoration: line-through; color: #6c757d; }
    </style>
</head>
<body>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <div class="text-center mb-4">
                    <h1 class="display-5">📝 My To-Do List</h1>
                    <p class="lead">Kelola tugas harianmu dengan mudah!</p>
                </div>

                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Tambah Tugas Baru</h5>
                        <form action="" method="POST">
                            <input type="hidden" name="action" value="add">
                            <div class="input-group">
                                <input type="text" class="form-control" name="title" placeholder="Tulis tugas baru di sini..." required>
                                <button class="btn btn-primary" type="submit">
                                    <i class="bi bi-plus-lg"></i> Tambah
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Daftar Tugas</h5>
                        
                        <?php if (empty($_SESSION['tasks'])): ?>
                            <p class="text-center text-muted">🎉 Semua tugas selesai! Saatnya istirahat.</p>
                        <?php else: ?>
                            <ul class="list-group">
                                <?php foreach ($_SESSION['tasks'] as $task): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <form action="" method="POST" class="me-3 mb-0">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                                <input class="form-check-input" type="checkbox" 
                                                    <?= ($task['status'] === 'selesai') ? 'checked' : '' ?> 
                                                    onchange="this.form.submit()">
                                            </form>
                                            <span class="task-title <?= ($task['status'] === 'selesai') ? 'selesai' : '' ?>">
                                                <?= htmlspecialchars($task['title']) ?>
                                            </span>
                                        </div>
                                        
                                        <div class="d-flex align-items-center">
                                            <?php if ($task['status'] === 'selesai'): ?>
                                                <span class="badge bg-success">Selesai</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Belum</span>
                                            <?php endif; ?>

                                            <form action="" method="POST" class="ms-3 mb-0">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0" title="Hapus tugas">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>