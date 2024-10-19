

function showForgetPass() {
    document.getElementById('forgetPass').classList.remove('hidden');
}

function hideForgetPass() {
    document.getElementById('forgetPass').classList.add('hidden');
}

function doneForgetPass() {
    document.getElementById('forgetPass').classList.add('hidden');
}

document.getElementById('forgetPassForm').addEventListener('submit', function (event) {
    event.preventDefault(); // Prevent default form submission

    // Fetch data from the form
    const formData = new FormData(this);
    const email = formData.get('email');

    // Here you should handle the logic to send the reset password link to the user's email.
    // For this example, we'll just show a confirmation message.
    const confirmationMessage = document.getElementById('forgetPassMessage');
    confirmationMessage.textContent = `A reset password link has been sent to ${email}. Please check your email.`;
    const forgetPassImage = document.getElementById('forgetPassImage');
    forgetPassImage.src = 'img/success.svg';
    document.getElementById('buttons').classList.add('hidden');
    document.getElementById('DoneButton').classList.remove('hidden');
    document.getElementById('forgetPassForm').classList.add('hidden');

    // Reset the form
    this.reset();

    // Optionally, you can hide the forget password dialog after submission
    // hideForgetPass();
});
