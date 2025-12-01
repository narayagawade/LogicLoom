<?php
session_start();
include 'db.php'; // must define $conn and connect to DB

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit;
}

$user_id = $_SESSION['user_id'];

// If profile already exists, redirect to dashboard
$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$profile = $res->fetch_assoc();
$stmt->close();

if ($profile) {
    header("Location: user_dashboard.php");
    exit;
}

// Fetch basic user info for display
$u = $conn->query("SELECT id, full_name, email FROM users WHERE id = $user_id")->fetch_assoc();
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Complete Profile - LogicLoom</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-[#A7E29E] to-[#8FCC88] flex items-center justify-center p-6">

  <div class="max-w-3xl w-full bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg overflow-hidden">
    <div class="p-6 md:flex gap-6">
      <div class="md:w-1/3 flex flex-col items-center justify-start">
        <div class="w-28 h-28 rounded-full bg-gray-100 border p-2 overflow-hidden">
          <img id="avatarPreview" src="/img/loginlamge.png" alt="avatar preview" class="w-full h-full object-cover rounded-full"/>
        </div>
        <h3 class="mt-4 font-semibold text-gray-800"><?php echo htmlspecialchars($u['full_name']); ?></h3>
        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($u['email']); ?></p>
      </div>

      <div class="md:w-2/3">
        <h2 class="text-2xl font-bold text-gray-800 mb-3">Complete Your Profile</h2>
        <form action="profile_update.php" method="POST" enctype="multipart/form-data" class="space-y-4">

          <div>
            <label class="block text-sm font-medium text-gray-700">Profile Picture</label>
            <input type="file" name="avatar" id="avatar" accept="image/*" class="mt-2 block w-full text-sm"/>
            <p class="text-xs text-gray-500 mt-1">Max 2MB. JPG/PNG only.</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Nickname</label>
            <input type="text" name="nickname" required class="mt-2 w-full p-2 border rounded-md" placeholder="Your display name (optional)"/>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700">Qualification</label>
              <input type="text" name="qualification" class="mt-2 w-full p-2 border rounded-md" placeholder="e.g. B.E. Computer Science"/>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Pronouns</label>
              <input type="text" name="pronouns" class="mt-2 w-full p-2 border rounded-md" placeholder="e.g. he/him"/>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Company</label>
            <input type="text" name="company" class="mt-2 w-full p-2 border rounded-md" placeholder="Company (optional)"/>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Alternate Email</label>
            <input type="email" name="alt_email" class="mt-2 w-full p-2 border rounded-md" placeholder="Another email (optional)"/>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Address</label>
            <textarea name="address" class="mt-2 w-full p-2 border rounded-md" rows="2"></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700">Short Bio</label>
            <textarea name="bio" class="mt-2 w-full p-2 border rounded-md" rows="3" placeholder="Tell others about you"></textarea>
          </div>

          <div class="flex gap-3 items-center">
            <button type="submit" class="px-4 py-2 bg-[#5AA053] hover:bg-[#6EAF66] text-white rounded-md font-semibold">Save Profile</button>
            <a href="user_dashboard.php" class="text-sm text-gray-600 hover:underline">Skip for now</a>
          </div>

        </form>
      </div>
    </div>
  </div>

<script>
  // preview avatar
  document.getElementById('avatar')?.addEventListener('change', function(e){
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(ev){ document.getElementById('avatarPreview').src = ev.target.result; }
    reader.readAsDataURL(file);
  });
</script>
</body>
</html>
