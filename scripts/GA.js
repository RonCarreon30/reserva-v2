// Function to fetch data from the PHP backend
async function fetchData() {
  try {
    const response = await fetch("handlers/fetch_data.php");
    if (!response.ok) {
      throw new Error(`HTTP error! Status: ${response.status}`);
    }
    const data = await response.json();
    return data;
  } catch (error) {
    console.error("Fetch error:", error);
    showToast("Failed to fetch data from server.", "bg-red-500");
    return null; // Return null if there's an error
  }
}

async function runGeneticAlgorithm() {
  const data = await fetchData();
  if (!data) {
    return; // Exit if fetching data failed
  }

  // Use the fetched data in your genetic algorithm
  const assignedRooms = data.assignedRooms;
  const schedules = data.schedules;
  const rooms = data.rooms;
  const departments = data.departments;

  // Function to check if a room assignment overlaps with existing assignments
  function isOverlap(newSchedule, roomAssignments) {
    if (!roomAssignments || roomAssignments.length === 0) {
      return false; // No overlap if there are no existing assignments
    }
    return roomAssignments.some((assignment) => {
      return (
        assignment.room_id === newSchedule.room_id && // Check same room
        assignment.day === newSchedule.days && // Check same day
        !(
          newSchedule.end_time <= assignment.start_time || // No overlap
          newSchedule.start_time >= assignment.end_time
        )
      );
    });
  }

  // Function to evaluate fitness of an assignment
  // Function to evaluate fitness of an assignment
  function evaluateFitness(assignments) {
    let fitness = 0;

    assignments.forEach((assignment) => {
      const schedule = schedules.find(
        (s) => s.schedule_id === assignment.schedule_id
      );

      if (!schedule) {
        console.error(`Schedule not found for assignment:`, assignment);
        return; // Skip this assignment if no schedule is found
      }

      console.log(`Evaluating fitness for schedule ${schedule.schedule_id}`);

      if (assignment.room_id === null) {
        console.warn(
          `No room assigned for schedule ID ${schedule.schedule_id}`
        );
        return; // Skip if no room is assigned
      }

      const room = rooms.find((r) => r.room_id === assignment.room_id);
      if (room && room.room_type === schedule.type) {
        fitness += 2; // Extra points for matching room type
      }

      console.log("Departments:", departments); // Log all departments
      console.log(
        `Checking for preferred department ID: ${schedule.pref_dept}`
      );

      // Ensure pref_dept is being compared correctly
      const preferredDept = departments.find(
        (dept) => dept.dept_id === schedule.pref_dept.toString()
      );

      if (!preferredDept) {
        console.warn(
          `Preferred department not found for schedule ID ${schedule.schedule_id} with pref_dept ${schedule.pref_dept}`
        );
        return; // Skip if preferred department is not found
      }

      console.log(`Found preferred department:`, preferredDept);

      if (
        room &&
        preferredDept &&
        room.building === preferredDept.dept_building
      ) {
        fitness += 3; // Extra points for department match
      }
    });

    console.log(`Total fitness score: ${fitness}`);
    return fitness;
  }

  // Function to create initial random population
  function createInitialPopulation(size, departments) {
    const population = [];

    for (let i = 0; i < size; i++) {
      const assignments = schedules
        .map((schedule) => {
          const availableRooms = rooms.filter(
            (room) =>
              room.room_type === schedule.type &&
              room.room_status === "Available" &&
              !isOverlap(schedule, assignedRooms) // Ensure no overlap
          );

          const preferredDept = departments.find(
            (dept) => dept.dept_id === schedule.pref_dept
          );

          const preferredDeptRooms = availableRooms.filter(
            (room) => room.building === preferredDept?.dept_building
          );

          const roomToAssign =
            preferredDeptRooms.length > 0
              ? preferredDeptRooms[
                  Math.floor(Math.random() * preferredDeptRooms.length)
                ]
              : availableRooms.length > 0
                ? availableRooms[
                    Math.floor(Math.random() * availableRooms.length)
                  ]
                : rooms[Math.floor(Math.random() * rooms.length)]; // Fallback to any room

          return {
            schedule_id: schedule.schedule_id,
            room_id: roomToAssign ? roomToAssign.room_id : null,
            days: schedule.days, // Include additional properties
            start_time: schedule.start_time,
            end_time: schedule.end_time,
            type: schedule.type,
            instructor: schedule.instructor, // Add more as needed
            section: schedule.section,
          };
        })
        .filter(Boolean); // Filter out null assignments

      population.push(assignments);
    }

    return population;
  }

  // Mutation function to randomly alter an assignment
  function mutate(assignments, mutationRate) {
    assignments.forEach((assignment) => {
      if (Math.random() < mutationRate) {
        const availableRooms = rooms.filter(
          (room) =>
            room.room_type ===
              schedules.find((s) => s.schedule_id === assignment.schedule_id)
                .type &&
            room.room_status === "Available" &&
            !isOverlap(assignment, assignedRooms)
        );

        const newRoom =
          availableRooms.length > 0
            ? availableRooms[Math.floor(Math.random() * availableRooms.length)]
            : rooms[Math.floor(Math.random() * rooms.length)];

        assignment.room_id = newRoom.room_id;
      }
    });
  }

  // Function to select parents based on fitness
  function selectParents(population) {
    const fitnessScores = population.map((assignments) =>
      evaluateFitness(assignments)
    );
    const totalFitness = fitnessScores.reduce((a, b) => a + b, 0);

    const selectionProbabilities = fitnessScores.map(
      (fitness) => fitness / totalFitness
    );
    const parents = [];

    while (parents.length < population.length) {
      const randomValue = Math.random();
      let cumulativeProbability = 0;

      for (let j = 0; j < selectionProbabilities.length; j++) {
        cumulativeProbability += selectionProbabilities[j];
        if (randomValue < cumulativeProbability) {
          parents.push(population[j]);
          break;
        }
      }
    }
    return parents;
  }

  // Crossover function to create new offspring
  function crossover(parent1, parent2) {
    const crossoverPoint = Math.floor(Math.random() * parent1.length);
    const child = parent1
      .slice(0, crossoverPoint)
      .concat(parent2.slice(crossoverPoint));
    return child;
  }

  // Main Genetic Algorithm function
  function geneticAlgorithm(generations, populationSize, mutationRate) {
    let population = createInitialPopulation(populationSize, departments);
    let bestSolution = null;
    let bestFitness = 0;

    for (let generation = 0; generation < generations; generation++) {
      const parents = selectParents(population);
      const newPopulation = [];

      for (let i = 0; i < parents.length; i += 2) {
        const parent1 = parents[i];
        const parent2 = parents[i + 1];

        if (parent2) {
          const child = crossover(parent1, parent2);
          mutate(child, mutationRate);
          newPopulation.push(child);
        }
      }

      population = newPopulation;

      population.forEach((assignments) => {
        const fitness = evaluateFitness(assignments);
        if (fitness > bestFitness) {
          bestFitness = fitness;
          bestSolution = assignments;
        }
      });
    }

    return { bestSolution, bestFitness };
  }

  // Run the Genetic Algorithm
  // After running the genetic algorithm
  try {
    const result = geneticAlgorithm(100, 50, 0.1);
    console.log("Best Assignments: ", result.bestSolution);
    console.log("Best Fitness: ", result.bestFitness);
    showToast("Genetic Algorithm completed successfully!", "bg-green-500");

    // Now save the best assignments to the database
    await saveAssignments(result.bestSolution, schedules); // Pass schedules along with assignments
  } catch (error) {
    console.error("Algorithm error:", error);
    showToast("An error occurred while running the algorithm.", "bg-red-500");
  }
}

// Function to save the best assignments to the database
async function saveAssignments(assignments) {
  try {
    const response = await fetch("handlers/save_assignments.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify(assignments), // Send full assignments as JSON
    });

    const result = await response.json();

    if (result.success) {
      showToast("Assignments saved successfully!", "bg-green-500");
    } else {
      console.error("Failed to save assignments:", result.error);
      showToast(
        "Failed to save assignments. " + (result.error || ""),
        "bg-red-500"
      );
    }
  } catch (error) {
    console.error("Save assignments error:", error);
    showToast("An error occurred while saving assignments.", "bg-red-500");
  }
}

function showToast(message, bgColor) {
  let toast = document.getElementById("toast");
  let messageSpan = document.getElementById("toast-message");

  // Ensure the toast starts hidden
  toast.classList.add("opacity-0", "translate-y-4");
  toast.classList.remove("opacity-100", "translate-y-0");

  // Clear existing background color classes
  toast.classList.remove("bg-green-500", "bg-red-500", "bg-blue-500");

  // Set the new background color class
  toast.classList.add(bgColor);

  // Determine the icon based on bgColor
  let icon;
  if (bgColor === "bg-green-500") {
    icon = "fas fa-check-circle"; // Success icon
  } else if (bgColor === "bg-red-500") {
    icon = "fas fa-exclamation-circle"; // Error icon
  } else if (bgColor === "bg-blue-500") {
    icon = "fas fa-spinner fa-spin"; // Loading icon
  } else {
    icon = "fas fa-info-circle"; // Default info icon
  }

  // Set the toast message with the appropriate icon
  messageSpan.innerHTML = `<i class="${icon}"></i> ${message}`;

  // Show the toast
  toast.classList.remove("opacity-0", "translate-y-4");
  toast.classList.add("opacity-100", "translate-y-0");

  // Hide the toast after 3 seconds (or keep it for loading until manually closed)
  if (bgColor !== "bg-blue-500") {
    // Only auto-hide if not a loading message
    setTimeout(() => {
      toast.classList.remove("opacity-100", "translate-y-0");
      toast.classList.add("opacity-0", "translate-y-4");
    }, 5000);
  }
}

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
      user_department: document.getElementById("user-department").textContent,
    };
  });

  let selectedDepartmentId = document.getElementById(
    "department-dropdown"
  ).value;
  let data = JSON.stringify({ schedules: schedules, selectedDepartmentId });

  // Show loading spinner
  const loader = document.getElementById("loading-spinner");
  loader.classList.remove("hidden");

  fetch("handlers/save.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: data,
  })
    .then((response) => response.json())
    .then((result) => {
      loader.classList.add("hidden"); // Hide loading spinner after fetching data

      if (result.success) {
        document.getElementById("parsed-sched-modal").classList.add("hidden");
        showToast("Schedules saved successfully!", "bg-green-500");
        document.getElementById("schedule-table-body").innerHTML = "";
        document.getElementById("file-info").classList.add("hidden");
        document.getElementById("file-input").value = "";

        // Run Genetic Algorithm after saving
        showToast("Running genetic algorithm...", "bg-blue-500");
        // Trigger the schedule assignment process
        runGeneticAlgorithm();
      } else {
        document.getElementById("parsed-sched-modal").classList.add("hidden");
        showToast(
          "Failed to save schedules. " + (result.error || ""),
          "bg-red-500"
        );
      }
    })
    .catch((error) => {
      loader.classList.add("hidden"); // Hide loading spinner on error
      document.getElementById("parsed-sched-modal").classList.add("hidden");
      console.error("Error:", error);
      showToast("An error occurred while saving the schedules.", "bg-red-500");
    });
});

document.getElementById("file-input").addEventListener("change", function () {
  let fileInput = document.getElementById("file-input");
  let file = fileInput.files[0];

  if (file) {
    // Display file info and show buttons
    //document.getElementById("file-info").classList.remove("hidden");
    //document.getElementById("file-name-container").textContent =
    //  `File: ${file.name}`;
    //document.getElementById("file-size").textContent =
    `Size: ${(file.size / 1024).toFixed(2)} KB`;

    // Show buttons
    //document.getElementById("confirm-upload").classList.remove("hidden");
    //document.getElementById("cancel-upload").classList.remove("hidden");

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
  //document.getElementById("file-info").classList.add("hidden");
  document.getElementById("file-input").value = "";
  //document.getElementById("confirm-upload").classList.add("hidden");
  //document.getElementById("cancel-upload").classList.add("hidden");
  document.getElementById("parsed-sched-modal").classList.add("hidden");
});

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
