//for Facility from
function showFacilityForm() {
  // Handle cancelation if needed
  document.getElementById("facility-form").classList.remove("hidden");
}

function closeFacilityForm() {
  // Handle cancelation if needed
  document.getElementById("facility-form").classList.add("hidden");
}

//For Room Form
function showRoomForm() {
  // Handle cancelation if needed
  document.getElementById("room-form").classList.remove("hidden");
}

function closeRoomForm() {
  // Handle cancelation if needed
  document.getElementById("room-form").classList.add("hidden");
}

function closeSchedForm() {
  document.getElementById("amyModal").classList.add("hidden");
}

function togglePasswordVisibility(inputId, iconElement) {
  const inputField = document.getElementById(inputId);
  const isPasswordVisible = inputField.type === "text";

  // Toggle input type
  inputField.type = isPasswordVisible ? "password" : "text";

  // Change the icon class
  const icon = iconElement.querySelector("i");
  icon.classList.toggle("fa-eye", isPasswordVisible);
  icon.classList.toggle("fa-eye-slash", !isPasswordVisible);
}
