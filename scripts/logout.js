function showCustomDialog() {
  document.getElementById("custom-dialog").classList.remove("hidden");
}

function confirmLogout() {
  // Redirect to logout.php to perform logout
  window.location.href = "logout.php";
}

function cancelLogout() {
  // Handle cancelation if needed
  document.getElementById("custom-dialog").classList.add("hidden");
}
