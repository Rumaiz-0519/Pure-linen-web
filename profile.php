<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'connect.php';

if (isset($_POST['update'])) {
    $userId = $_SESSION['user_id'];
    $firstName = mysqli_real_escape_string($conn, $_POST['fname']);
    $lastName = mysqli_real_escape_string($conn, $_POST['lname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (password_verify($currentPassword, $user['password'])) {
        $updateQuery = "UPDATE users SET firstName=?, lastName=?, email=?";
        $params = array($firstName, $lastName, $email);
        $types = "sss";

        if (!empty($newPassword)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateQuery .= ", password=?";
            $params[] = $hashedPassword;
            $types .= "s";
        }

        $updateQuery .= " WHERE id=?";
        $params[] = $userId;
        $types .= "i";

        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $_SESSION['firstName'] = $firstName;
            $_SESSION['email'] = $email;
            $success = "Profile updated successfully!";
        } else {
            $error = "Update failed!";
        }
    } else {
        $error = "Current password is incorrect!";
    }
}

$userId = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="body-1">
    <div class="container-1">
        <h1 class="form-title">Update Profile</h1>
        <?php if(isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="fname" value="<?php echo $user['firstName']; ?>" required>
                <label>First Name</label>
            </div>
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="lname" value="<?php echo $user['lastName']; ?>" required>
                <label>Last Name</label>
            </div>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" value="<?php echo $user['email']; ?>" required>
                <label>Email</label>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="current_password" required>
                <label>Current Password</label>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="new_password">
                <label>New Password (leave blank to keep current)</label>
            </div>
            <input type="submit" name="update" value="Update Profile" class="btn">
            <a href="index.php" class="btn" style="display: block; text-align: center; margin-top: 10px; text-decoration: none;">Back to Home</a>
        </form>
    </div>
</body>
</html>