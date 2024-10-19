function showUserForm() {
    document.getElementById('addUserForm').classList.remove('hidden');
  }

  function SubmitForm() {
      // Refresh page
      document.getElementById('addUserForm').classList.add('hidden')
      window.location.href = "accManagement.html";
  }

  function closeForm() {
    document.getElementById('addUserForm').classList.add('hidden');
  }

