//for Facility from
function showFacilityForm() {
    // Handle cancelation if needed
    document.getElementById('facility-form').classList.remove('hidden');
}

function closeFacilityForm() {
    // Handle cancelation if needed
    document.getElementById('facility-form').classList.add('hidden');
}

//For Room Form
function showRoomForm() {
    // Handle cancelation if needed
    document.getElementById('room-form').classList.remove('hidden');
}

function closeRoomForm() {
    // Handle cancelation if needed
    document.getElementById('room-form').classList.add('hidden');
}

function closeSchedForm() {
    document.getElementById('amyModal').classList.add('hidden');
  }