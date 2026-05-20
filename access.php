<?php
require_once __DIR__ . '/auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = $_POST['access_key'] ?? '';
    if ($key === ACCESS_KEY) {
        $_SESSION['authenticated'] = true;
        header("Location: employees.php");
        exit;
    } else {
        $error = "Invalid access key.";
    }
}

// If already authenticated, redirect to dashboard
if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header("Location: employees.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access - Clinic System</title>
    <!-- Google Fonts: Inter for Geometric Sans look -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script>
        // Apply theme immediately to prevent FOIT/flashing
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--color-canvas);
            margin: 0;
        }
        .access-card {
            background: var(--color-surface);
            padding: 40px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            width: 100%;
            max-width: 400px;
            border: 1px solid var(--color-border);
            text-align: center;
            animation: fadeIn 0.4s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .brand-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        h2 {
            margin-bottom: 8px;
            font-size: var(--text-2xl);
            color: var(--color-text-primary);
        }
        p {
            color: var(--color-text-secondary);
            margin-bottom: 32px;
            font-size: var(--text-sm);
        }
        .form-group {
            text-align: left;
            margin-bottom: 24px;
        }
        .btn-brand {
            width: 100%;
            height: 48px;
            background: var(--color-brand);
            color: white;
            font-weight: 700;
            border-radius: var(--radius-sm);
            font-size: var(--text-base);
        }
        .btn-brand:hover {
            background: var(--color-brand-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .error {
            color: var(--color-danger);
            background: hsl(0, 75%, 95%);
            padding: 12px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-size: var(--text-sm);
            font-weight: 600;
            border: 1px solid hsl(0, 75%, 85%);
        }
    </style>
</head>
<body>

<div class="access-card">
    <div class="brand-icon">🏥</div>
    <h2>Health Service Office</h2>
    <p>Please enter the access key to continue.</p>

    <?php if ($error !== ''): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <input type="password" name="access_key" placeholder="Enter Access Key..." required autofocus style="height: 48px; font-size: 16px; text-align: center; letter-spacing: 2px;">
        </div>
        <button type="submit" class="btn btn-brand">Grant Access</button>
    </form>
</div>

</body>
</html>
