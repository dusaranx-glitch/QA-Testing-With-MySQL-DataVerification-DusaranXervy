<?php
// ---------------------------------------------------------------
// Kara David's Wish Note - single-file PHP application
// ---------------------------------------------------------------

// Local MariaDB connection settings
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "birthday_wishnote";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . htmlspecialchars($conn->connect_error));
}

$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Capture all four variables from the form
    $FirstName   = trim($_POST["FirstName"] ?? "");
    $LastName    = trim($_POST["LastName"] ?? "");
    $GovPosition = trim($_POST["GovPosition"] ?? "");
    $Location    = trim($_POST["Location"] ?? "");

    // ----------------------------------------------------------
    // INTENTIONAL FLAW (for QA testing purposes):
    // The Location value is captured above, but it is deliberately
    // left out of the INSERT statement AND the bind_param call below.
    // The form and success message will look correct, but the
    // Location column in the database will remain NULL.
    // ----------------------------------------------------------
    $sql = "INSERT INTO main_corrupt_politicians (FirstName, LastName, GovPosition) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sss", $FirstName, $LastName, $GovPosition); // Location intentionally omitted
        if ($stmt->execute()) {
            $message = "Success! The wish note has been sent.";
            $messageType = "success";
        } else {
            $message = "Error: the wish note could not be saved.";
            $messageType = "error";
        }
        $stmt->close();
    } else {
        // Fallback error message if the query fails to prepare
        $message = "System Note: Could not prepare query. Ensure table 'main_corrupt_politicians' exists.";
        $messageType = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kara David's Wish Note</title>
<style>
  * { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh; background: #f4f6f8;
    font-family: Arial, Helvetica, sans-serif; color: #1f2d3d;
    display: flex; align-items: center; justify-content: center; padding: 20px;
  }
  .card {
    background: #fff; width: 100%; max-width: 440px; padding: 32px 28px;
    border-radius: 10px; border-top: 6px solid #0d5cb6;
    box-shadow: 0 6px 20px rgba(0,0,0,0.10);
  }
  h2 { margin: 0 0 22px; text-align: center; color: #0d5cb6; }
  label { display: block; margin: 16px 0 6px; font-weight: bold; font-size: 15px; }
  input[type="text"] {
    width: 100%; padding: 12px; font-size: 16px;
    border: 1px solid #c3ccd6; border-radius: 6px;
  }
  input[type="text"]:focus { outline: none; border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,0.15); }
  .req { color: #c0392b; }
  button {
    border: none; border-radius: 6px; font-size: 16px; font-weight: bold;
    padding: 13px; cursor: pointer; transition: background 0.2s;
  }
  .btn-primary { width: 100%; margin-top: 24px; background: #0d6efd; color: #fff; }
  .btn-primary:hover { background: #0a4fc4; }
  .btn-cancel { background: #e3e8ee; color: #1f2d3d; }
  .btn-cancel:hover { background: #cfd7e0; }
  .msg { padding: 12px 14px; border-radius: 6px; margin-bottom: 18px; font-size: 15px; }
  .msg.success { background: #e3f1ff; color: #0a4fc4; border: 1px solid #b5d6fb; }
  .msg.error { background: #fdecea; color: #a52a1f; border: 1px solid #f5c2bd; }
  .overlay {
    display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5);
    align-items: center; justify-content: center; padding: 20px; z-index: 10;
  }
  .overlay.show { display: flex; }
  .modal {
    background: #fff; width: 100%; max-width: 400px; padding: 26px;
    border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.25);
  }
  .modal h3 { margin: 0 0 16px; color: #0d5cb6; text-align: center; }
  .modal dl { margin: 0 0 20px; }
  .modal dt { font-size: 13px; color: #5b6b7c; margin-top: 10px; }
  .modal dd { margin: 2px 0 0; font-weight: bold; word-break: break-word; }
  .actions { display: flex; gap: 10px; }
  .actions button { flex: 1; }
  .btn-confirm { background: #0d6efd; color: #fff; }
  .btn-confirm:hover { background: #0a4fc4; }
</style>
</head>
<body>
<div class="card">
  <h2>Kara David's Wish Note</h2>

  <?php if ($message !== ""): ?>
    <div class="msg <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></div>
  <?php endif; ?>

  <form id="wishForm" method="POST" action="">
    <label for="FirstName">Target's First Name <span class="req">*</span></label>
    <input type="text" id="FirstName" name="FirstName" maxlength="30" required>

    <label for="LastName">Target's Last Name <span class="req">*</span></label>
    <input type="text" id="LastName" name="LastName" maxlength="30" required>

    <label for="GovPosition">Target's Government Position <span class="req">*</span></label>
    <input type="text" id="GovPosition" name="GovPosition" maxlength="20" required>

    <label for="Location">Target's Location</label>
    <input type="text" id="Location" name="Location" maxlength="40">

    <button type="submit" class="btn-primary">Send Wish Note</button>
  </form>
</div>

<div class="overlay" id="overlay">
  <div class="modal">
    <h3>Verify Target Data</h3>
    <dl>
      <dt>First Name</dt><dd id="mFirst"></dd>
      <dt>Last Name</dt><dd id="mLast"></dd>
      <dt>Government Position</dt><dd id="mPos"></dd>
      <dt>Location</dt><dd id="mLoc"></dd>
    </dl>
    <div class="actions">
      <button type="button" class="btn-cancel" id="cancelBtn">Cancel</button>
      <button type="button" class="btn-confirm" id="confirmBtn">Confirm &amp; Send</button>
    </div>
  </div>
</div>

<script>
  var form = document.getElementById("wishForm");
  var overlay = document.getElementById("overlay");

  form.addEventListener("submit", function (e) {
    e.preventDefault(); // intercept the submission
    document.getElementById("mFirst").textContent = form.FirstName.value;
    document.getElementById("mLast").textContent = form.LastName.value;
    document.getElementById("mPos").textContent = form.GovPosition.value;
    document.getElementById("mLoc").textContent = form.Location.value || "(none)";
    overlay.classList.add("show");
  });

  document.getElementById("cancelBtn").addEventListener("click", function () {
    overlay.classList.remove("show");
  });

  document.getElementById("confirmBtn").addEventListener("click", function () {
    form.submit(); // programmatic submit bypasses the submit event listener
  });
</script>
</body>
</html>
