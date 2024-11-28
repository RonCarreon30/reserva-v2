<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PLVScheds</title>
    <link
      href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="css/style.css" />
  </head>
  <body
    class="min-h-screen flex items-center justify-center bg-cover bg-center font-[sans-serif]"
    style="background-image: url(&quot;img/bg-image.jpg&quot;)"
  >
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    <!-- Full-page overlay -->
    <div
      class="relative flex flex-col items-center justify-center w-full min-h-screen p-4"
    >
      <div
        class="grid md:grid-cols-2 items-center gap-8 max-w-4xl w-full bg-white bg-opacity-80 rounded-lg shadow-lg overflow-hidden"
      >
        <div class="hidden md:block p-8">
          <h2
            class="text-4xl lg:text-5xl font-extrabold text-gray-800 leading-tight"
          >
            RESERVA
          </h2>
          <p class="text-lg mt-4 text-gray-700">
            Unlocking Effortless Room Assignments and Facility Reservations at
            Pamantasan ng Lungsod ng Valenzuela!
          </p>
        </div>
        <div class="p-8">
          <form id="loginForm" class="space-y-6">
            <h3 class="text-3xl font-extrabold text-gray-800 text-center">
              Sign in
            </h3>
            <div
              id="error-message"
              class="hidden text-red-600 text-center"
            ></div>
            <div class="mt-4">
              <input
                name="email"
                type="email"
                autocomplete="email"
                required
                class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500"
                placeholder="Email address"
              />
            </div>
            <div class="mt-4">
              <input
                name="password"
                type="password"
                autocomplete="current-password"
                required
                class="w-full px-4 py-3 bg-gray-100 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500"
                placeholder="Password"
              />
            </div>
            <div class="flex items-center justify-between mt-4">
              <label class="flex items-center">
                <input
                  id="remember-me"
                  name="remember-me"
                  type="checkbox"
                  class="text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <span class="ml-2 text-sm text-gray-600">Remember me</span>
              </label>
              <a
                onclick="showForgetPass()"
                class="text-sm text-blue-600 hover:text-blue-500 font-semibold cursor-pointer"
              >
                Forgot your password?
              </a>
            </div>
            <div class="mt-6">
              <button
                type="submit"
                class="w-full py-3 text-white font-semibold bg-plv-blue rounded-md shadow-lg hover:bg-plv-highlight focus:outline-none"
              >
                Log in
              </button>
            </div>
                <!-- Include the FAQs section here -->
    <div class=""><?php include 'faqBtn.php'; ?></div>
          </form>
        </div>
      </div>
    </div>

    <!-- Forgot Password Dialog -->
    <div
      id="forgetPass"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden"
    >
      <div class="bg-white p-6 rounded-lg shadow-lg w-11/12 max-w-md">
        <div class="flex flex-col items-center text-center">
          <img
            id="forgetPassImage"
            src="img/forgotPass.svg"
            alt="Forgot Password"
            class="w-32 mb-4"
          />
          <h2
            id="header-container"
            class="text-xl font-bold text-gray-800 mb-4"
          >
            Forgot Password
          </h2>
          <div
            id="loadingIndicator"
            class="hidden flex items-center justify-center mt-4"
          >
            <svg
              class="animate-spin h-5 w-5 text-blue-600"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              ></circle>
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v8z"
              ></path>
            </svg>
            <span class="ml-2 text-gray-700 font-semibold">Processing...</span>
          </div>

          <p id="response-container" class="text-gray-600 mb-6">
            Please enter the email associated with your account.
          </p>

          <form id="forgetPassForm" class="w-full">
            <div class="mb-4">
              <label for="email" class="block text-gray-600 mb-2">Email:</label>
              <input
                type="email"
                id="email"
                name="email"
                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:border-blue-500"
              />
            </div>
            <div class="flex justify-between mt-6">
              <button
                type="submit"
                class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
              >
                Submit
              </button>
              <button
                type="button"
                onclick="hideForgetPass()"
                class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400"
              >
                Cancel
              </button>
            </div>
          </form>
          <div
            id="DoneButton"
            class="flex hidden justify-center mt-6"
            onclick="doneForgetPass()"
          >
            <button
              class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700"
            >
              Done
            </button>
          </div>
        </div>
      </div>
    </div>

    <script>
      // Handle the login form submission
      document
        .getElementById("loginForm")
        .addEventListener("submit", function (event) {
          event.preventDefault();
          var formData = new FormData(this);

          fetch("login.php", {
            method: "POST",
            body: formData,
          })
            .then((response) => response.json())
            .then((data) => {
              if (data.success) {
                window.location.href = data.redirect;
              } else {
                document.getElementById("error-message").textContent =
                  data.message;
                document
                  .getElementById("error-message")
                  .classList.remove("hidden");
              }
            })
            .catch((error) => {
              console.error("Error:", error);
            });
        });

      // Handle the forgot password form submission
      document
        .getElementById("forgetPassForm")
        .addEventListener("submit", function (event) {
          event.preventDefault();

          const emailInput = document.getElementById("email");
          const email = emailInput.value.trim();
          const loadingIndicator = document.getElementById("loadingIndicator");
          const responseContainer =
            document.getElementById("response-container");

          if (!email) {
            responseContainer.textContent =
              "Please enter a valid email address.";
            return;
          }

          const formData = new FormData();
          formData.append("email", email);

          // Show the loading spinner
          loadingIndicator.classList.remove("hidden");
          responseContainer.textContent = ""; // Clear any previous messages

          fetch("handlers/forgetPass.php", {
            method: "POST",
            body: formData,
          })
            .then((response) => response.json())
            .then((data) => {
              // Hide the loading spinner
              loadingIndicator.classList.add("hidden");

              if (data.success) {
                document.getElementById("header-container").textContent =
                  "Password Reset Email Sent!";
                responseContainer.textContent = data.message;
                document.getElementById("forgetPassImage").src =
                  "img/success.svg";
                document
                  .getElementById("forgetPassForm")
                  .classList.add("hidden");
                document
                  .getElementById("DoneButton")
                  .classList.remove("hidden");
              } else {
                responseContainer.textContent = `${data.message}`;
                responseContainer.classList.add(
                  "text-red-500",
                  "font-semibold"
                );
              }
            })
            .catch((error) => {
              console.error("Error:", error);
              // Hide the loading spinner
              loadingIndicator.classList.add("hidden");
              responseContainer.textContent =
                "An error occurred. Please try again later.";
            });
        });

      // Functions to show and hide the forgot password modal
      function showForgetPass() {
        document.getElementById("forgetPass").classList.remove("hidden");
      }

      function hideForgetPass() {
        document.getElementById("forgetPass").classList.add("hidden");
        document.getElementById("email").value = "";
      }

      // Reset the form and UI state when the Done button is clicked
      function doneForgetPass() {
        hideForgetPass();
        document.getElementById("forgetPassForm").classList.remove("hidden");
        document.getElementById("DoneButton").classList.add("hidden");
        document.getElementById("response-container").textContent =
          "Please enter the email associated with your account.";
        document.getElementById("forgetPassImage").src = "img/forgotPass.svg";
        document.getElementById("email").value = ""; // Clear the email field
      }
    </script>
  </body>
</html>
