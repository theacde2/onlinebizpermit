<?php
require './db.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = $_POST['name'];
    $email    = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role     = $_POST['role']; // admin / staff / customer

   $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, is_approved) VALUES (?, ?, ?, ?, 0)");
   $stmt->bind_param("ssss", $name, $email, $password, $role);


    if ($stmt->execute()) {
        header("Location: staff_login.php?success=1");
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <style>
    body {
      font-family: 'Inter', sans-serif;
      display: flex; justify-content: center; align-items: center;
      height: 100vh; margin: 0;
      background: linear-gradient(135deg, #6a11cb, #2575fc);
    }
    .container {
      display: flex;
      background: #fff;
      border-radius: 15px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.3);
      overflow: hidden;
      width: 750px;
    }
    .image { flex: 1; }
    .image img { width: 100%; height: 100%; object-fit: cover; }
    .form-box {
      flex: 1; padding: 40px;
      display: flex; flex-direction: column; justify-content: center;
    }
    h2 { margin-bottom: 20px; color: #333; }
    input, select {
      padding: 12px; margin-bottom: 15px;
      border: 1px solid #ccc; border-radius: 10px;
      width: 100%; font-size: 14px;
      transition: 0.3s;
    }
    input:focus, select:focus { border-color: #2575fc; outline: none; }
    button {
      padding: 12px; background: #2575fc; border: none;
      color: #fff; border-radius: 10px; font-weight: bold;
      cursor: pointer; transition: 0.3s;
    }
    button:hover { background: #6a11cb; }
    a { color: #2575fc; text-decoration: none; }
  </style>
</head>
<body>
  <div class="container">
    <div class="image"><img src="staff.png" alt="Staff"></div>
    <div class="form-box">
      <h2>Register</h2>
      <form method="POST">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <select name="role" required>
          <option value="customer">Applicants</option>
          <option value="staff">Staff</option>
        </select>
        <button type="submit">Register</button>
      </form>
      <p>Already have an account? <a href="staff_login.php">Login</a></p>
    </div>
  </div>
</body>
</html>
