<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Manual - RESERVA</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <style>
        html {
            scroll-behavior: smooth;
        }
        a:hover {
            text-decoration: underline;
            color: #2563eb;
        }
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-out;
        }
        .accordion-content.show {
            max-height: 1000px;
        }
        .toc {
            position: -webkit-sticky;
            position: sticky;
            top: 20px;
        }
        .accordion-toggle:hover {
            cursor: pointer;
            text-decoration: underline;
        }
        .breadcrumb {
            background: #f1f5f9;
            padding: 10px;
            border-radius: 5px;
        }
        .search-input {
            width: 100%;
            padding: 8px;
            margin-bottom: 1rem;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        /* Print-Friendly Styles */
        @media print {
            body {
                font-size: 12px;
                margin: 0;
                padding: 0;
            }
            .toc, .search-input, .breadcrumb, footer, .accordion-toggle {
                display: none;
            }
        }
        .progress-bar {
            height: 5px;
            background: #4caf50;
            width: 0%;
        }
    </style>
</head>
<body class="bg-gray-100 text-gray-800 font-sans">

<!-- Header -->
<header class="bg-plv-blue text-white p-4">
    <h1 class="text-3xl font-bold">User Manual for RESERVA</h1>
    <p class="text-sm mt-1">A facility reservation and room loading system for Pamantasan ng Lungsod ng Valenzuela (PLV).</p>
</header>

<!-- Main Content -->
<main class="container mx-auto p-6">
    <!-- Search Bar -->
    <section class="mb-8">
        <input type="text" id="search" class="search-input" placeholder="Search in Table of Contents..." onkeyup="searchTOC()">
    </section>

    <!-- Table of Contents -->
    <section class="mb-8" data-aos="fade-up">
        <h2 class="text-2xl font-semibold mb-4">Table of Contents</h2>
        <ul id="toc" class="list-disc list-inside ml-5 toc">
            <li><a href="#getting-started" class="text-blue-600">Getting Started</a></li>
            <li><a href="#student-rep" class="text-blue-600">Student Representative</a></li>
            <li><a href="#chairperson" class="text-blue-600">Chairperson</a></li>
            <li><a href="#gso" class="text-blue-600">General Services Office (GSO)</a></li>
            <li><a href="#registrar" class="text-blue-600">Registrar</a></li>
            <li><a href="#troubleshooting" class="text-blue-600">Troubleshooting</a></li>
            <li><a href="#faq" class="text-blue-600">FAQs</a></li>
            <li><a href="#support" class="text-blue-600">Support</a></li>
        </ul>
    </section>

    <!-- Progress Bar -->
    <div id="progress-bar" class="progress-bar mb-4"></div>

    <!-- Accordion for each section (now with Expand/Collapse All functionality)-->
    <button onclick="toggleAllAccordions()" class="bg-blue-600 text-white px-4 py-2 rounded mb-4">
        Expand All / Collapse All
    </button>

    <!-- User Role Sections -->
    <section id="getting-started" class="mb-8" data-aos="fade-up">
        <h3 class="text-xl font-semibold mb-2">Getting Started</h3>
        <div>
            <p>To access the system, click <a href="https://reserva.infinityfreeapp.com/reserva/" target="_blank" class="text-blue-500 ">here</a> and log in with your provided credentials. Each user role has specific permissions as outlined in this manual.</p>
        </div>
    </section>

    <section id="student-rep" class="mb-8" data-aos="fade-up">
        <h3 class="text-xl font-semibold mb-2 accordion-toggle">Student Representative</h3>
        <div class="accordion-content">
            <p class="mt-4">Pages:</p>
            <ul class="list-disc list-inside ml-5">
                <li>Dashboard: View event/reservation calendar.</li>
                <li>My Reservations: View, edit, and delete pending reservations.</li>
                <li>Facility Reservation: Browse and reserve facilities.</li>
            </ul>
            <p class="mt-4">Guides:</p>
            <ol class="list-decimal list-inside ml-5">
                <li><strong>Viewing Your Reservations:</strong> Navigate to the "My Reservations" section. You can see a list of your current reservations, along with options to view, edit, or cancel them.
                    <div class="mt-2">
                        <!-- Video Embed -->
                        <iframe width="560" height="315" src="https://www.youtube.com/embed/your_video_id_here" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                </li>
                <li><strong>Making a New Reservation:</strong> Go to the "Facility Reservation" section, select the desired room or facility, choose the date and time, then submit your request.
                    <div class="mt-2">
                        <!-- Image Embed -->
                        <img src="path_to_your_image.jpg" alt="Reservation Example" class="rounded-lg shadow-lg">
                    </div>
                </li>
                <li><strong>Logging Out:</strong> Always log out after using the system for security. Click on the "Logout" button located at the bottom part of the sidebar.
                    <div class="mt-2">
                        <!-- Visual Guide -->
                        <p>See the logout button below:</p>
                        <img src="path_to_logout_button_image.png" alt="Logout Button" class="rounded-lg shadow-lg">
                    </div>
                </li>
            </ol>
        </div>
    </section>

<section id="chairperson" class="mb-8" data-aos="fade-up">
    <h3 class="text-xl font-semibold mb-2 accordion-toggle">Chairperson</h3>
    <div class="accordion-content">
        <p class="mt-4">Pages:</p>
        <ul class="list-disc list-inside ml-5">
            <li>Dashboard: View department event/reservation calendar.</li>
            <li>Class Schedules: View and export timetables for class schedules.</li>
            <li>Room Loading: Download and upload schedules with automatic room assignments.</li>
            <li>Department Reservations: View and manage department reservations.</li>
            <li>Facility Reservation: Browse and reserve facilities.</li>
        </ul>
        <p class="mt-4">Guides:</p>
        <ol class="list-decimal list-inside ml-5">
            <li><strong>Viewing of Room Loads:</strong> Learn how to view room loads in your department.
                <div class="mt-2">
                    <img src="path_to_your_gif.gif" alt="Viewing Room Loads" class="rounded-lg shadow-lg">
                </div>
            </li>
            <li><strong>Exporting Loads:</strong> Export faculty, section, or room loads to a file.
                <div class="mt-2">
                    <img src="path_to_your_gif.gif" alt="Exporting Loads" class="rounded-lg shadow-lg">
                </div>
            </li>
            <li><strong>Room Loading:</strong> Assign rooms to schedules efficiently.
                <div class="mt-2">
                    <img src="path_to_your_gif.gif" alt="Room Loading" class="rounded-lg shadow-lg">
                </div>
            </li>
            <li><strong>Viewing Department Reservations:</strong> Check all reservations within your department.
                <div class="mt-2">
                    <img src="path_to_your_gif.gif" alt="Viewing Reservations" class="rounded-lg shadow-lg">
                </div>
            </li>
            <li><strong>Making a Reservation:</strong> Reserve rooms or facilities for department use.
                <div class="mt-2">
                    <img src="path_to_your_gif.gif" alt="Making a Reservation" class="rounded-lg shadow-lg">
                </div>
            </li>
        </ol>
    </div>
</section>

<section id="gso" class="mb-8" data-aos="fade-up">
    <h3 class="text-xl font-semibold mb-2 accordion-toggle">General Services Office (GSO)</h3>
    <div class="accordion-content">
        <p class="mt-4">Pages:</p>
        <ul class="list-disc list-inside ml-5">
            <li>Dashboard: View event and reservation calendar.</li>
            <li>Facility Management: Add, edit, and delete facilities.</li>
            <li>Reservations Management: Approve/decline reservations, edit and delete pending reservations.</li>
            <li>Facility Reservation: Reserve facilities without needing approval.</li>
        </ul>
        <p class="mt-4">Guides:</p>
        <ol class="list-decimal list-inside ml-5">
            <li><strong>Managing Facilities:</strong> Add, edit, or delete facilities as needed.
                <div class="mt-2">
                    <img src="path_to_your_gif.gif" alt="Managing Facilities" class="rounded-lg shadow-lg">
                </div>
            </li>
            <li><strong>Reservation Management:</strong> Approve, reject, cancel, edit, or delete reservations.
                <div class="mt-2">
                    <img src="path_to_your_gif.gif" alt="Managing Reservations" class="rounded-lg shadow-lg">
                </div>
            </li>
            <li><strong>Making a Reservation:</strong> Create a reservation directly in the system.
                <div class="mt-2">
                    <img src="path_to_your_gif.gif" alt="Making a Reservation" class="rounded-lg shadow-lg">
                </div>
            </li>
        </ol>
    </div>
</section>

<section id="registrar" class="mb-8" data-aos="fade-up">
    <h3 class="text-xl font-semibold mb-2 accordion-toggle">Registrar</h3>
    <div class="accordion-content">
        <p class="mt-4">Pages:</p>
        <ul class="list-disc list-inside ml-5">
            <li>Dashboard: View event and reservation calendar.</li>
            <li>Account Management: Add, edit, and delete users.</li>
            <li>Data Setup: Manage academic year, departments, and buildings.</li>
            <li>Class Schedules: View and export timetables.</li>
            <li>Room Loading: Download/upload schedules with room assignments.</li>
            <li>Room Management: Add, edit, and delete rooms.</li>
        </ul>
        <p class="mt-4">Guides:</p>
        <ol class="list-decimal list-inside ml-5">
            <li><strong>Account Management:</strong> Add, edit, or delete users for the system.
                <div class="mt-2">
                    <img src="path_to_your_gif.gif" alt="Account Management" class="rounded-lg shadow-lg">
                </div>
            </li>
            <li><strong>Manage Academic Year:</strong> Set up the academic year for scheduling.
                <div class="mt-2">
                    <img src="path_to_your_gif.gif" alt="Manage Academic Year" class="rounded-lg shadow-lg">
                </div>
            </li>
            <li><strong>Managing Rooms:</strong> Add, edit, or delete rooms for room loading.
                <div class="mt-2">
                    <img src="path_to_your_gif.gif" alt="Managing Rooms" class="rounded-lg shadow-lg">
                </div>
            </li>
            <li><strong>Exporting Loads:</strong> Export faculty, section, or room loads.
                <div class="mt-2">
                    <img src="path_to_your_gif.gif" alt="Exporting Loads" class="rounded-lg shadow-lg">
                </div>
            </li>
            <li><strong>Room Loading:</strong> Assign rooms to schedules efficiently.
                <div class="mt-2">
                    <img src="path_to_your_gif.gif" alt="Room Loading" class="rounded-lg shadow-lg">
                </div>
            </li>
        </ol>
    </div>
</section>


    <!-- Troubleshooting -->
    <section id="troubleshooting" class="mb-8" data-aos="fade-up">
        <h3 class="text-xl font-semibold mb-2">Troubleshooting</h3>
        <p>Here are some common issues and their solutions:</p>
        <ul class="list-disc list-inside ml-5">
            <li><strong>Login Issues:</strong> Verify username and password. Contact support if issues persist.</li>
            <li><strong>File Upload Errors:</strong> Ensure your file is formatted correctly (Excel/CSV).</li>
            <li><strong>Permissions Error:</strong> Check if your user role has sufficient access rights.</li>
        </ul>
    </section>

    <!-- FAQ -->
    <section id="faq" class="mb-8" data-aos="fade-up">
        <h3 class="text-xl font-semibold mb-2">Frequently Asked Questions</h3>
        <ul class="list-disc list-inside ml-5">
            <li><strong>How do I reserve a room?</strong> Navigate to the "Room Reservations" section and select the desired room and time.</li>
            <li><strong>Can I edit my reservations?</strong> Yes, reservations can be edited before approval by the GSO or Admins.</li>
            <li><strong>What should I do if I face technical issues?</strong> Contact support via the support section.</li>
            <li><strong>How to logout?:</strong>Click on the "Logout" button located at the bottom part of the sidebar.
                <div class="mt-2">
                    <!-- Visual Guide -->
                    <p>See the logout button below:</p>
                    <img src="gif/logout.gif" alt="Logout Button" class="rounded-lg shadow-lg">
                </div>
            </li>
        </ul>
    </section>

    <!-- Support -->
    <section id="support" class="mb-8" data-aos="fade-up">
        <h3 class="text-xl font-semibold mb-2">Support</h3>
        <p>If you encounter issues or need assistance, please contact our support team at <strong>infinityfree.reserva@gmail.com</strong></p>
    </section>
</main>

<!-- Footer -->
<footer class="bg-plv-blue text-white p-4 text-center">
    <p>&copy; 2024 PLV: RESERVA.</p>
</footer>

<script>
    AOS.init();

    // Accordion Toggle
    const accordionToggles = document.querySelectorAll('.accordion-toggle');
    accordionToggles.forEach(toggle => {
        toggle.addEventListener('click', () => {
            const content = toggle.nextElementSibling;
            content.classList.toggle('show');
        });
    });

    // Expand All / Collapse All
    function toggleAllAccordions() {
        const accordionContents = document.querySelectorAll('.accordion-content');
        accordionContents.forEach(content => {
            content.classList.toggle('show');
        });
    }

    // Search Table of Contents
    function searchTOC() {
        const query = document.getElementById('search').value.toLowerCase();
        const items = document.querySelectorAll('#toc li');
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            if (text.includes(query)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Update progress bar as user scrolls
    window.onscroll = function() {
        const scrollHeight = document.documentElement.scrollHeight - window.innerHeight;
        const scrollPosition = window.scrollY;
        const progressBar = document.getElementById('progress-bar');
        progressBar.style.width = ((scrollPosition / scrollHeight) * 100) + '%';
    };
</script>

</body>
</html>
