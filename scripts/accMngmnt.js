function showUserForm() {
  document.getElementById("addUserForm").classList.remove("hidden");
}

function SubmitForm() {
  // Refresh page
  document.getElementById("addUserForm").classList.add("hidden");
  window.location.href = "accManagement";
}

function closeForm() {
  document.getElementById("addUserForm").classList.add("hidden");
}

// Prevent form redirection on submit
// Prevent form redirection on submit
document
  .getElementById("createUserForm")
  .addEventListener("submit", function (event) {
    event.preventDefault(); // Prevent the default form submission (page reload/redirect)

    // Extract form data
    const formData = new FormData(this);

    // Use fetch API to send form data asynchronously
    fetch("handlers/create_user.php", {
      method: "POST",
      body: formData,
    })
      .then((response) => response.json()) // Assuming response is JSON
      .then((data) => {
        if (data.success) {
          // Show success message in modal
          showSuccessModal(data.message);

          // Optionally reset the form
          document.getElementById("createUserForm").reset();
        } else {
          // Show error message in modal
          showSuccessModal(data.message, false); // Passing `false` to indicate an error
        }
      })
      .catch((error) => {
        console.error("Error:", error);
        // Show generic error message in modal
        showSuccessModal("There was an error processing the form.", false); // Passing `false` for error
      });
  });
