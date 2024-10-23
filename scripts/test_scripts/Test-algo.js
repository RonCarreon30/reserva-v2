// Sample Data Definitions
const assignedRooms = [
  {
    assignment_id: 217,
    schedule_id: 400,
    room_id: 5,
    day: "Monday",
    start_time: "19:00:00",
    end_time: "21:00:00",
  },
  {
    assignment_id: 218,
    schedule_id: 40,
    room_id: 5,
    day: "Wednesday",
    start_time: "18:00:00",
    end_time: "20:30:00",
  },
  // Overlapping with schedule_id 405 (Tuesday 11:00 to 12:30)
  {
    assignment_id: 219,
    schedule_id: 500,
    room_id: 6,
    day: "Tuesday",
    start_time: "11:00:00",
    end_time: "12:30:00",
  },
  // Overlapping with schedule_id 406 (Thursday 11:00 to 12:30)
  {
    assignment_id: 220,
    schedule_id: 600,
    room_id: 5,
    day: "Thursday",
    start_time: "11:00:00",
    end_time: "12:30:00",
  },
  // Overlapping with schedule_id 407 (Monday 14:00 to 15:30)
  {
    assignment_id: 221,
    schedule_id: 700,
    room_id: 7,
    day: "Monday",
    start_time: "14:00:00",
    end_time: "15:30:00",
  },
  // Additional assignments that overlap for further testing
  {
    assignment_id: 222,
    schedule_id: 708,
    room_id: 7,
    day: "Monday",
    start_time: "14:00:00",
    end_time: "15:30:00",
  },
  {
    assignment_id: 223,
    schedule_id: 709,
    room_id: 6,
    day: "Thursday",
    start_time: "11:00:00",
    end_time: "12:30:00",
  },
];

const schedules = [
  {
    schedule_id: 403,
    subject_code: "MATH101",
    type: "Lecture",
    pref_dept: 1,
    start_time: "09:00:00",
    end_time: "10:30:00",
    days: "Monday",
  },
  {
    schedule_id: 404,
    subject_code: "MATH101",
    type: "Lecture",
    pref_dept: 1,
    start_time: "09:00:00",
    end_time: "10:30:00",
    days: "Wednesday",
  },
  {
    schedule_id: 405,
    subject_code: "ENG202",
    type: "Lecture",
    pref_dept: 1,
    start_time: "11:00:00",
    end_time: "12:30:00",
    days: "Tuesday",
  },
  {
    schedule_id: 406,
    subject_code: "ENG202",
    type: "Lecture",
    pref_dept: 1,
    start_time: "11:00:00",
    end_time: "12:30:00",
    days: "Thursday",
  },
  {
    schedule_id: 407,
    subject_code: "SCI303",
    type: "Laboratory",
    pref_dept: 1,
    start_time: "14:00:00",
    end_time: "15:30:00",
    days: "Monday",
  },
  {
    schedule_id: 408,
    subject_code: "SCI303",
    type: "Laboratory",
    pref_dept: 1,
    start_time: "14:00:00",
    end_time: "15:30:00",
    days: "Thursday",
  },
];

const rooms = [
  {
    room_id: 5,
    room_type: "Lecture",
    building: "CEIT",
    room_status: "Available",
  },
  {
    room_id: 6,
    room_type: "Lecture",
    building: "CEIT",
    room_status: "Available",
  },
  {
    room_id: 7,
    room_type: "Laboratory",
    building: "CEIT",
    room_status: "Available",
  },
  {
    room_id: 8,
    room_type: "Lecture",
    building: "CEIT",
    room_status: "Available",
  },
  {
    room_id: 9,
    room_type: "Lecture",
    building: "CEIT",
    room_status: "Available",
  },
  {
    room_id: 10,
    room_type: "Laboratory",
    building: "CEIT",
    room_status: "Available",
  },
];

const dept_tbl = [
  { dept_id: 1, dept_name: "Information Technology", dept_building: "CEIT" },
  { dept_id: 2, dept_name: "Civil Engineering", dept_building: "CEIT" },
];

// Function to check if a room assignment overlaps with existing assignments
function isOverlap(newSchedule, roomAssignments) {
  return roomAssignments.some((assignment) => {
    // Check if the day matches and the times overlap
    return (
      assignment.day === newSchedule.days &&
      !(
        (
          newSchedule.end_time <= assignment.start_time || // New ends before existing starts
          newSchedule.start_time >= assignment.end_time
        ) // New starts after existing ends
      )
    );
  });
}

// Function to evaluate fitness of an assignment
function evaluateFitness(assignments) {
  let fitness = 0;

  assignments.forEach((assignment) => {
    const schedule = schedules.find(
      (s) => s.schedule_id === assignment.schedule_id
    );
    if (assignment.room_id !== null) {
      // Log assignment details for debugging
      console.log(
        `Evaluating fitness for schedule ${schedule.schedule_id} with room ${assignment.room_id}`
      );

      // Increase fitness for successful assignment
      fitness += 1;

      // Check if room assignment matches type
      const room = rooms.find((r) => r.room_id === assignment.room_id);
      if (room && room.room_type === schedule.type) {
        fitness += 2; // Extra points for matching room type
      }

      // Check for preferred department
      const preferredDept = dept_tbl.find(
        (dept) => dept.dept_id === schedule.pref_dept
      );
      if (room && room.building === preferredDept.dept_building) {
        fitness += 3; // Extra points for department match
      }
    }
  });

  return fitness;
}

// Function to create initial random population
function createInitialPopulation(size) {
  const population = [];

  for (let i = 0; i < size; i++) {
    const assignments = schedules.map((schedule) => {
      const availableRooms = rooms.filter(
        (room) =>
          room.room_type === schedule.type &&
          room.room_status === "Available" &&
          !isOverlap(schedule, assignedRooms)
      );

      // Prioritize rooms based on department
      const preferredDeptRooms = availableRooms.filter(
        (room) =>
          room.building ===
          dept_tbl.find((dept) => dept.dept_id === schedule.pref_dept)
            .dept_building
      );

      const roomToAssign =
        preferredDeptRooms.length > 0
          ? preferredDeptRooms[
              Math.floor(Math.random() * preferredDeptRooms.length)
            ]
          : availableRooms.length > 0
            ? availableRooms[Math.floor(Math.random() * availableRooms.length)]
            : rooms[Math.floor(Math.random() * rooms.length)]; // Select from all rooms if none fit

      return {
        schedule_id: schedule.schedule_id,
        room_id: roomToAssign.room_id, // Ensure room_id is never null
      };
    });

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

      if (availableRooms.length > 0) {
        const newRoom =
          availableRooms[Math.floor(Math.random() * availableRooms.length)];
        assignment.room_id = newRoom.room_id;
      } else {
        // Select any available room if no fitting room is available
        const anyRoom = rooms[Math.floor(Math.random() * rooms.length)];
        assignment.room_id = anyRoom.room_id;
      }
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

// Mutation function to randomly alter an assignment
/*function mutate(assignments, mutationRate) {
  assignments.forEach((assignment) => {
    if (Math.random() < mutationRate) {
      const availableRooms = rooms.filter(
        (room) =>
          room.room_type ===
            schedules.find((s) => s.schedule_id === assignment.schedule_id)
              .type && room.room_status === "Available"
      );

      // Check for overlap with assigned rooms before reassigning
      const tempAssignment = {
        days: schedules.find((s) => s.schedule_id === assignment.schedule_id)
          .days,
        start_time: schedules.find(
          (s) => s.schedule_id === assignment.schedule_id
        ).start_time,
        end_time: schedules.find(
          (s) => s.schedule_id === assignment.schedule_id
        ).end_time,
      };

      if (availableRooms.length > 0) {
        const newRoom = availableRooms.find(
          (room) => !isOverlap(tempAssignment, assignedRooms)
        );

        assignment.room_id = newRoom ? newRoom.room_id : null;
      } else {
        assignment.room_id = null; // No available room
      }
    }
  });
}*/

// Main Genetic Algorithm function
function geneticAlgorithm(generations, populationSize, mutationRate) {
  let population = createInitialPopulation(populationSize);
  let bestSolution = null;
  let bestFitness = 0;

  for (let generation = 0; generation < generations; generation++) {
    const parents = selectParents(population);
    const newPopulation = [];

    for (let i = 0; i < parents.length; i += 2) {
      const parent1 = parents[i];
      const parent2 = parents[i + 1];

      if (parent2) {
        // Ensure that parent2 is defined
        const child = crossover(parent1, parent2);
        mutate(child, mutationRate);
        newPopulation.push(child);
      }
    }

    population = newPopulation;

    // Evaluate best solution in current population
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
const result = geneticAlgorithm(100, 50, 0.1);
console.log("Best Assignments: ", result.bestSolution);
console.log("Best Fitness: ", result.bestFitness);
