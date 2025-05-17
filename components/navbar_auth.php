<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<header class="header">
    <div class="container header-inner auth-header">
        <a href="index.php" class="logo auth-logo"><span class="text-green">Arti</span><span class="text-blue">Sell</span></a>
        
        <div class="header-right">
            <?php if (basename($_SERVER['PHP_SELF']) === 'login.php'): ?>
                <a href="signup.php" class="btn btn-primary btn-sm auth-btn">Sign Up</a>
            <?php elseif (basename($_SERVER['PHP_SELF']) === 'signup.php'): ?>
                <a href="login.php" class="btn btn-outline btn-sm auth-btn">Login</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-outline btn-sm auth-btn">Login</a>
                <a href="signup.php" class="btn btn-primary btn-sm auth-btn">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<style>
    .text-green {
        color: #008a39;
    }
    
    .text-blue {
        color: #0066cc;
    }
    
    .auth-header {
        padding: 10px 0;
    }
    
    .auth-header .header-inner {
        justify-content: space-between;
    }
    
    .auth-logo {
        font-size: 28px;
        font-weight: 700;
        letter-spacing: -0.5px;
    }
    
    .auth-btn {
        border-radius: 24px;
        padding: 8px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-outline.auth-btn {
        border: 2px solid #2E8B57;
        color: #2E8B57;
    }
    
    .btn-outline.auth-btn:hover {
        background-color: rgba(46, 139, 87, 0.1);
    }
    
    .btn-primary.auth-btn {
        background-color: #2E8B57;
        color: white;
        border: 2px solid #2E8B57;
    }
    
    .btn-primary.auth-btn:hover {
        background-color: #236e45;
        border-color: #236e45;
    }
</style>

<!-- Add Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> 