document.getElementById("file-input").addEventListener("change", function () {
  let fileInput = document.getElementById("file-input");
  let file = fileInput.files[0];

  if (file) {
    // Display file info and show buttons
    document.getElementById("file-info").classList.remove("hidden");
    document.getElementById("file-name-container").textContent =
      `File: ${file.name}`;
    document.getElementById("file-size").textContent =
      `Size: ${(file.size / 1024).toFixed(2)} KB`;

    // Show buttons
    document.getElementById("confirm-upload").classList.remove("hidden");
    document.getElementById("cancel-upload").classList.remove("hidden");

    document
      .getElementById("confirm-upload")
      .addEventListener("click", function () {
        document
          .getElementById("parsed-sched-modal")
          .classList.remove("hidden");
        let formData = new FormData();
        formData.append("file", file);

        fetch("handlers/upload.php", {
          method: "POST",
          body: formData,
        })
          .then((response) => response.text())
          .then((text) => {
            try {
              let data = JSON.parse(text); // Then attempt to parse it as JSON
              console.log("Parsed JSON:", data);

              if (data.success) {
                let schedules = data.schedules;

                displayTable(schedules);
                showToast("Parsed successfully!", "bg-green-500");
              } else {
                document
                  .getElementById("parsed-sched-modal")
                  .classList.add("hidden");
                showToast(
                  "Failed to upload file. " + (data.error || ""),
                  "bg-red-500"
                );
              }
            } catch (e) {
              console.error("Parsing error:", e);
              document
                .getElementById("parsed-sched-modal")
                .classList.add("hidden");
              showToast(
                "An error occurred while processing the file.",
                "bg-red-500"
              );
            }
          })
          .catch((error) => {
            document
              .getElementById("parsed-sched-modal")
              .classList.add("hidden");
            console.error("Error:", error);
            showToast("An error occurred.", "bg-red-500");
          });

        // Hide the preview and reset file input
        document.getElementById("file-info").classList.add("hidden");
        fileInput.value = "";
      });

    document
      .getElementById("cancel-upload")
      .addEventListener("click", function () {
        // Hide the preview and reset file input
        document.getElementById("file-info").classList.add("hidden");
        fileInput.value = "";
      });
  }
});

document.getElementById("cancel-action").addEventListener("click", function () {
  // Clear table and reset upload section
  document.getElementById("schedule-table-body").innerHTML = "";
  document.getElementById("file-info").classList.add("hidden");
  document.getElementById("file-input").value = "";
  document.getElementById("confirm-upload").classList.add("hidden");
  document.getElementById("cancel-upload").classList.add("hidden");
  document.getElementById("parsed-sched-modal").classList.add("hidden");
});

document.getElementById("save-action").addEventListener("click", () => {
  let rows = Array.from(document.querySelectorAll("#schedule-table-body tr"));

  let schedules = rows.map((row) => {
    let cells = row.children;
    return {
      subject_code: cells[0] ? cells[0].textContent : "",
      subject: cells[1] ? cells[1].textContent : "",
      section: cells[2] ? cells[2].textContent : "",
      instructor: cells[3] ? cells[3].textContent : "",
      start_time: cells[4] ? cells[4].textContent.split(" - ")[0] : "",
      end_time: cells[5] ? cells[5].textContent.split(" - ")[1] : "",
      days: cells[6] ? cells[6].textContent : "",
      type: cells[7] ? cells[7].textContent : "",
      user_department: document.getElementById("user-department").textContent, // Add user_department from a hidden element
    };
  });

  // Get selected department ID from the dropdown
  let selectedDepartmentId = document.getElementById(
    "department-dropdown"
  ).value;

  // Convert schedules to JSON, including the selected department ID
  let data = JSON.stringify({ schedules: schedules, selectedDepartmentId });

  fetch("handlers/save.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: data,
  })
    .then((response) => response.json())
    .then((result) => {
      if (result.success) {
        document.getElementById("parsed-sched-modal").classList.add("hidden");
        showToast("Schedules saved successfully!", "bg-green-500");
        document.getElementById("schedule-table-body").innerHTML = "";
        document.getElementById("file-info").classList.add("hidden");
        document.getElementById("file-input").value = "";

        // Call Fetch data function
        fetchData();
      } else {
        document.getElementById("parsed-sched-modal").classList.add("hidden");
        showToast(
          "Failed to save schedules. " + (result.error || ""),
          "bg-red-500"
        );
      }
    })
    .catch((error) => {
      document.getElementById("parsed-sched-modal").classList.add("hidden");
      console.error("Error:", error);
      showToast("An error occurred while saving the schedules.", "bg-red-500");
    });
});

let schedules = [];
let rooms = [];
let existingSchedules = [];

// Fetch schedules already saved in the database
async function fetchExistingSchedules() {
  try {
    const response = await fetch("handlers/fetch_existing_schedules.php");
    const data = await response.json();
    existingSchedules = data?.schedules || [];
    if (!existingSchedules.length) {
      console.error("No existing schedules found in the database.");
    }
  } catch (error) {
    console.error("Error fetching existing schedules:", error);
    existingSchedules = [];
  }
}

// Fetch schedules from the database
async function fetchSchedules() {
  try {
    const response = await fetch("handlers/fetch_schedules.php");
    const data = await response.json();
    schedules = data?.schedules || [];
    if (!schedules.length) {
      console.error("No schedules found.");
    }
  } catch (error) {
    console.error("Error fetching schedules:", error);
    schedules = [];
  }
}

// Fetch rooms for each schedule's department
async function fetchRoomsForSchedules() {
  const roomFetchPromises = schedules.map(async (schedule) => {
    try {
      const response = await fetch(
        `handlers/fetch_rooms.php?department=${encodeURIComponent(schedule.pref_dept)}`
      );
      const data = await response.json();
      return {
        department: schedule.pref_dept,
        rooms: data?.rooms || [],
      };
    } catch (error) {
      console.error(
        `Error fetching rooms for department ${schedule.pref_dept}:`,
        error
      );
      return { department: schedule.pref_dept, rooms: [] };
    }
  });
  const roomData = await Promise.all(roomFetchPromises);
  rooms = roomData.flatMap((deptData) => deptData.rooms);
}

// Fetch existing schedules, schedules, and rooms, and then run the genetic algorithm
async function fetchData() {
  await fetchExistingSchedules();
  await fetchSchedules();
  await fetchRoomsForSchedules();

  console.log("Schedules:", schedules);
  console.log("Rooms:", rooms);
  console.log("Existing Schedules:", existingSchedules);

  if (schedules.length && rooms.length) {
    runGeneticAlgorithm();
  } else {
    showToast(
      "Schedules or rooms data is missing. Please check the data sources.",
      "bg-red-500"
    );
  }
}

// Utility function to check for time conflicts between two schedules
function hasConflict(schedule1, schedule2) {
  const days1 = Array.isArray(schedule1.days)
    ? schedule1.days
    : schedule1.days.split(",").map((day) => day.trim());
  const days2 = Array.isArray(schedule2.days)
    ? schedule2.days
    : schedule2.days.split(",").map((day) => day.trim());

  if (!days1.some((day) => days2.includes(day))) return false;

  const [start1, end1] = [
    convertToMinutes(schedule1.start_time),
    convertToMinutes(schedule1.end_time),
  ];
  const [start2, end2] = [
    convertToMinutes(schedule2.start_time),
    convertToMinutes(schedule2.end_time),
  ];

  return start1 < end2 && start2 < end1;
}

// Convert time (HH:MM) to total minutes
function convertToMinutes(time) {
  const [hours, minutes] = time.split(":").map(Number);
  return hours * 60 + minutes;
}

// Function to check if a schedule is within allowed room usage times (7 AM - 9:30 PM)
function isWithinAllowedTime(schedule) {
  const start = convertToMinutes(schedule.start_time);
  const end = convertToMinutes(schedule.end_time);
  const [allowedStart, allowedEnd] = [
    convertToMinutes("07:00"),
    convertToMinutes("21:30"),
  ];
  return start >= allowedStart && end <= allowedEnd;
}

// Check for conflicts against existing schedules in DB
function checkAgainstExistingSchedules(newAssignment) {
  return newAssignment.every(
    (newSchedule) =>
      !existingSchedules.some((existingSchedule) =>
        hasConflict(newSchedule, existingSchedule)
      )
  );
}

// Fitness function
function fitnessFunction(assignments) {
  let fitness = 0;

  for (const room of rooms) {
    const roomAssignments = assignments.filter(
      (schedule) => schedule.room === room.name
    );

    // Penalize conflicts within room assignments
    for (let i = 0; i < roomAssignments.length; i++) {
      for (let j = i + 1; j < roomAssignments.length; j++) {
        if (hasConflict(roomAssignments[i], roomAssignments[j])) {
          fitness -= 100;
        }
      }
    }

    // Penalize conflicts with existing schedules
    if (room.assignedSchedules) {
      roomAssignments.forEach((newSchedule) => {
        room.assignedSchedules.forEach((existingSchedule) => {
          if (hasConflict(newSchedule, existingSchedule)) {
            fitness -= 200;
          }
        });
      });
    }
  }

  assignments.forEach((schedule) => {
    const assignedRoom = rooms.find((room) => room.name === schedule.room);
    if (assignedRoom) {
      fitness += assignedRoom.department === schedule.department ? 50 : -50;
      fitness += assignedRoom.type === schedule.room_type ? 10 : -50;
      if (!isWithinAllowedTime(schedule)) {
        fitness -= 100;
      }
    }
  });

  return fitness;
}

// Crossover function
function crossover(parent1, parent2) {
  const point = Math.floor(Math.random() * parent1.length);
  return [
    [...parent1.slice(0, point), ...parent2.slice(point)],
    [...parent2.slice(0, point), ...parent1.slice(point)],
  ];
}

// Mutation function
function mutate(individual) {
  const index = Math.floor(Math.random() * individual.length);
  individual[index].room = assignRoomBasedOnDepartment(individual[index]);
  return individual;
}

// Assign room based on department and room type
function assignRoomBasedOnDepartment(schedule) {
  const availableRooms = rooms.filter(
    (room) =>
      room.room_type === schedule.type &&
      room.departments
        .split(" and ")
        .map((dept) => dept.trim())
        .includes(schedule.pref_dept)
  );

  if (availableRooms.length) return availableRooms[0].name;

  const otherRooms = rooms.filter((room) => room.room_type === schedule.type);
  return otherRooms.length ? otherRooms[0].name : null;
}

// Initialize population for the genetic algorithm
function initializePopulation(size) {
  return Array.from({ length: size }, () =>
    schedules.map((schedule) => ({
      ...schedule,
      room: assignRoomBasedOnDepartment(schedule),
    }))
  );
}

// Selection function for genetic algorithm
function select(population) {
  return population
    .sort((a, b) => fitnessFunction(b) - fitnessFunction(a))
    .slice(0, Math.floor(population.length / 2));
}

// Genetic algorithm function
function geneticAlgorithm() {
  let population = initializePopulation(100);
  for (let generation = 0; generation < 50; generation++) {
    const newPopulation = [];
    while (newPopulation.length < population.length) {
      const parents = select(population);
      if (parents.length < 2) break;
      const [parent1, parent2] = [parents[0], parents[1]];
      const [child1, child2] = crossover(parent1, parent2);
      newPopulation.push(mutate(child1), mutate(child2));
    }
    population = newPopulation;
  }
  return select(population)[0];
}

// Run genetic algorithm and handle conflicts with existing schedules
function runGeneticAlgorithm() {
  console.log("Schedules before algorithm:", schedules);
  console.log("Rooms before algorithm:", rooms);

  const bestAssignment = geneticAlgorithm();

  if (checkAgainstExistingSchedules(bestAssignment)) {
    console.log(
      "Best Room Assignment:",
      JSON.stringify(bestAssignment, null, 2)
    );

    // Save the best assignment
    fetch("handlers/save_assignments.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(bestAssignment),
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.success) {
          console.log("Save response:", data);
        } else {
          console.error("Error saving assignments:", data.error);
        }
      })
      .catch((error) => console.error("Error saving assignments:", error));
  } else {
    console.error("Conflict detected with existing schedules. Aborting save.");
    showToast("Conflict with existing schedules. Save aborted.", "bg-red-500");
  }
}

function showToast(message, bgColor) {
  let toast = document.getElementById("toast");
  let messageSpan = document.getElementById("toast-message");

  // Ensure the toast starts hidden
  toast.classList.add("opacity-0", "translate-y-4");
  toast.classList.remove("opacity-100", "translate-y-0");

  // Clear existing background color classes
  toast.classList.remove("bg-green-500", "bg-red-500");

  // Set the new background color class
  toast.classList.add(bgColor);

  // Set the toast message
  messageSpan.textContent = message;

  // Show the toast
  toast.classList.remove("opacity-0", "translate-y-4");
  toast.classList.add("opacity-100", "translate-y-0");

  // Hide the toast after 3 seconds
  setTimeout(() => {
    toast.classList.remove("opacity-100", "translate-y-0");
    toast.classList.add("opacity-0", "translate-y-4");
  }, 5000);
}

function displayTable(schedules) {
  let tableBody = document.getElementById("schedule-table-body");
  tableBody.innerHTML = "";

  schedules.forEach((schedule) => {
    let row = document.createElement("tr");
    row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap">${schedule.subject_code}</td>
                    <td class="hidden px-6 py-4 whitespace-nowrap">${schedule.subject}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${schedule.section}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${schedule.instructor}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${schedule.start_time} - ${schedule.end_time}</td>
                    <td class="hidden px-6 py-4 whitespace-nowrap">${schedule.start_time} - ${schedule.end_time}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${schedule.days}</td>
                    <td class="px-6 py-4 whitespace-nowrap">${schedule.type}</td>
                    
                `;
    tableBody.appendChild(row);
  });
}
