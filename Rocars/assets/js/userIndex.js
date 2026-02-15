
// Elements
const searchInput = document.getElementById('autoSearch');
const addBranchBtn = document.getElementById('addBranch');
const branchModal = document.getElementById('addbranchModal');
const closeBtn = document.getElementById('closeBtn');
const branchForm = document.getElementById('branchForm');

const createAccountModal = document.getElementById('createAccountModal');
const accountForm = document.getElementById('accountForm');
const createAccountbtn = document.getElementById('createAccount');
const closeBtns = document.getElementById('closeBtns');

const technicianSearch = document.getElementById('searchInput');
const mechanicsTableBody = document.getElementById('mechanicsTableBody');
const mechanicspaginationControls = document.getElementById('mechanicspaginationControls');

const archivedTableBody = document.getElementById('archivedmechanicsTableBody');
const archivedPagination = document.getElementById('archivemechanicspaginationControls');

const addTechnicianBtn = document.getElementById('addTechnicians');
const addTechnicianModal = document.getElementById('addTechnicianModal');
const technicianForm = document.getElementById('technicianForm');

const editUserModal = document.getElementById('editUserModal');
const editUserForm = document.getElementById('editUserForm');

const editModal = document.getElementById('editMechanicModal');
const editForm = document.getElementById('editMechanicForm');

addTechnicianBtn.addEventListener('click', () => {
    addTechnicianModal.style.display = 'flex';
});

/* CLOSE MODAL */
closeBtn.addEventListener('click', () => {
    addTechnicianModal.style.display ='none';
});

/* CLOSE WHEN CLICK OUTSIDE */
addTechnicianModal.addEventListener('click', (e) => {
    if (e.target === addTechnicianModal) {
        addTechnicianModal.style.display='none';
    }
});


function showToast(message, duration = 3000) {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
    }, duration);
}

// Modal Toggle Logic
addBranchBtn.addEventListener('click', () => branchModal.style.display = 'flex');
closeBtn.addEventListener('click', () => branchModal.style.display = 'none');
closeBtns.addEventListener('click', () => createAccountModal.style.display = 'none');

window.addEventListener('click', e => {
    if (e.target === branchModal) branchModal.style.display = 'none';
    if(e.target === createAccountModal) createAccountModal.style.display = 'none';
    if(e.target === editMechanicModal) editMechanicModal.style.display = 'none';
     if(e.target === editUserModal) editUserModal.style.display = 'none';
    
});

createAccountbtn.addEventListener('click', () => createAccountModal.style.display = 'flex');


// Search Logic
searchInput.addEventListener('input', () => {
    loadUsers(1);
    loadUsers2(1);
});

accountForm.addEventListener('submit', async e => {
    e.preventDefault(); 
    
    // 1. Capture the form data
    const formData = new FormData(accountForm);
    const password = formData.get('password');
    const confirmPassword = formData.get('confirm_password');

    if (password !== confirmPassword) {
        showToast("Error: Passwords do not match!", 5000);
        
        accountForm.querySelector('input[name="confirm_password"]').style.borderColor = 'red';
        return; 
    }

    // Reset border color if they do match
    accountForm.querySelector('input[name="confirm_password"]').style.borderColor = '';

    try {
        const res = await fetch('actions/insertUser.php', {
            method: 'POST',
            body: formData
        });

        if (!res.ok) throw new Error('Server responded with an error');
        const data = await res.json(); 

        if (data.success) {
            showToast(data.message);
            accountForm.reset();
            createAccountModal.style.display = 'none'; 
            
            loadUsers(1); 
            loadUsers2(1);
        } else {
            showToast("Error: " + data.message, 5000);
        }
    } catch (err) {
        console.error(err);
        showToast("Request failed: " + err.message, 5000);
    }
});
branchForm.addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData(branchForm);

    try {
        const res = await fetch('actions/addbranch.php', {
            method: 'POST',
            body: formData
        });

        if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);

        const text = await res.text();
        console.log('Server response:', text); 
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showToast(data.message);
                branchForm.reset();
                branchModal.style.display = 'none'; 
            setTimeout(() => {
                location.reload();
            }, 1000);
            } else {
                showToast("Error: " + data.message, 5000);
                showToast(data.message);
            }
        } catch (jsonErr) {
            console.error("Invalid JSON:", text);
            showToast("Server error: Invalid response format.", 5000);
        }
    } catch (err) {
        console.error("Fetch Error:", err);
        showToast("Request Failed: " + err.message, 5000);
        // Modal stays open here
    }
});

// Load Active Users
function loadUsers(page = 1) {
    const search = searchInput.value;
    fetch(`user.php?action=fetch&search=${encodeURIComponent(search)}&page=${page}`)
        .then(res => res.text())
        .then(data => {
            const parts = data.split('||SEP||');
            if(parts.length < 2) return;
            document.getElementById('userTableBody').innerHTML = parts[0];
            document.getElementById('paginationControls').innerHTML = parts[1];
            
            document.querySelectorAll('#paginationControls a').forEach(a => {
                a.addEventListener('click', e => {
                    e.preventDefault();
                    loadUsers(a.dataset.page);
                });
            });
        });
}




function loadArchivedMechanics(page = 1) {
   const search = technicianSearch.value.trim();

    fetch(`archivetechnicianTable.php?action=fetch&search=${encodeURIComponent(search)}&page=${page}`)
        .then(res => res.json())
        .then(data => {
            archivedTableBody.innerHTML = data.table;
            archivedPagination.innerHTML = data.pagination;

            document.querySelectorAll('.page-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    loadArchivedMechanics(btn.dataset.page);
                });
            });
        })
        .catch(err => console.error('Fetch error:', err));
}

// Load on page load
document.addEventListener('DOMContentLoaded', () => {
    loadArchivedMechanics();
});

// Load Archived Users
function loadUsers2(page = 1) {
    const search = searchInput.value;
    fetch(`archivedUser.php?action=fetch&search=${encodeURIComponent(search)}&page=${page}`)
        .then(res => res.text())
        .then(data => {
            const parts = data.split('||SEP||');
            if(parts.length < 2) return;
            document.getElementById('archivedUserTableBody').innerHTML = parts[0];
            // FIXED: ID name case sensitivity matched
            document.getElementById('archivedPaginationControls').innerHTML = parts[1];
            
            document.querySelectorAll('#archivedPaginationControls a').forEach(a => {
                a.addEventListener('click', e => {
                    e.preventDefault();
                    loadUsers2(a.dataset.page); // Fixed: Call loadUsers2 for archived
                });
            });
        });
}


async function loadMechanics(page = 1) {
    try {
        const search = technicianSearch.value.trim();
        const res = await fetch(`technicianTable.php?action=fetch&search=${encodeURIComponent(search)}&page=${page}`);
        const data = await res.json(); // PHP should return JSON { table: ..., pagination: ... }

        mechanicsTableBody.innerHTML = data.table;
        mechanicspaginationControls.innerHTML = data.pagination;
    } catch (err) {
        console.error('Error loading mechanics:', err);
    }
}

// Event delegation for pagination clicks
mechanicspaginationControls.addEventListener('click', e => {
    if (e.target.tagName === 'BUTTON') {
        const page = e.target.dataset.page;
        loadMechanics(page);
    }
});

// Optional: Load mechanics on typing
technicianSearch.addEventListener('input', () => {
    loadMechanics(1);          // Refresh Active Mechanics
    loadArchivedMechanics(1);  // Refresh Archived Mechanics
});


loadMechanics();


async function archiveUser(userId) {
    if (!confirm('Archive this user?')) return;
    performUserAction('actions/archive_user.php', userId);
}

async function restoreUser(userId) {
    if (!confirm('Restore this user?')) return;
    performUserAction('actions/restore_user.php', userId);
}

async function performUserAction(url, userId) {
    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `user_id=${userId}`
        });
        const data = await response.json();
        alert(data.message);
        loadUsers(1);
        loadUsers2(1);
    } catch(error) {
        alert('Action failed');
        console.error(error);
    }
}

function restoreTechnician(id) {
    if (!confirm("Are you sure you want to restore this technician?")) return;

    const formData = new FormData();
    formData.append('mechanic_id', id);

    fetch('actions/restoreTechnician.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            loadMechanics(1);
            loadArchivedMechanics(1);

        } else {
            alert("Error: " + data.
            message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("A system error occurred.");
    });
}

function deleteUser(userId) {
    if (!confirm("Are you sure you want to permanently delete this user?")) return;

    fetch('actions/delete_user.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'user_id=' + userId
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            loadUsers2(page = 1);
        }
    })
    .catch(err => {
        console.error(err);
        alert("Delete failed.");
    });
}


function archiveMechanic(mechanic_id) {
    if (!confirm("Are you sure you want to archive this mechanic?")) return;

    fetch('actions/archive_mechanic.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'mechanic_id=' + mechanic_id
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            loadMechanics(1) 
            loadArchivedMechanics(1)

        }
    })
    .catch(err => {
        console.error(err);
        alert("Something went wrong.");
    });
}

function deleteTechnician(id) {
    if (!confirm("This action is permanent. Delete this technician?")) return;

    fetch('actions/delete_technician.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'mechanic_id=' + id
    })
    .then(res => res.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
           loadArchivedMechanics(1)
        }
    })
    .catch(err => {
        console.error(err);
        alert("Delete failed.");
    });
}


technicianForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(technicianForm);

    try {
        const res = await fetch('actions/add_technician.php', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();

        if (data.success) {
            showToast(data.message); 
            technicianForm.reset();
            addTechnicianModal.style.display='none';
            loadMechanics(1);

            
        } else {
            alert(data.message);
        }

    } catch (error) {
        console.error(error);
        alert('Something went wrong.');
    }
});


function EditUser(userId) {
    fetch(`actions/get_user.php?user_id=${userId}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.message);
                return;
            }

            // POPULATE FORM
            document.getElementById('edit_user_id').value = data.user.user_id;
            document.getElementById('edit_username').value = data.user.username;
            document.getElementById('edit_fullname').value = data.user.fullname;
            document.getElementById('edit_email').value = data.user.email;
            document.getElementById('edit_contact').value = data.user.contact_number;
            document.getElementById('edit_branch').value = data.user.branch_id;
            document.getElementById('edit_role').value = data.user.role;

            // SHOW MODAL
            editUserModal.style.display = 'flex';
        })
        .catch(err => {
            console.error(err);
            alert('Failed to load user data.');
        });
}


function closeEditModal() {
    editUserModal.style.display = 'none';
}

editUserModal.addEventListener('click', (e) => {
    if (e.target === editUserModal) closeEditModal();
});

editUserForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(editUserForm);

    try {
        const res = await fetch('actions/update_user.php', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();

        if (data.success) {
            loadUsers(1);
            showToast(data.message);
            closeEditModal();
            
        } else {
            alert(data.message);
        }
    } catch (err) {
        console.error(err);
        alert('Something went wrong while updating the user.');
    }
});





// 1. Open Modal and Fetch Data
async function EditMechanic(id) {
    try {
        // You need a small PHP file to get a single mechanic's info
        const res = await fetch(`actions/get_mechanic.php?id=${id}`);
        const mechanic = await res.json();

        if (mechanic.success) {
            document.getElementById('edit_mechanic_id').value = mechanic.data.mechanic_id;
            document.getElementById('edit_mechanic_name').value = mechanic.data.mechanic_name;
            document.getElementById('editMechanic_branch').value = mechanic.data.branch_id;
            document.getElementById('edit_contact_number').value = mechanic.data.contact_number;
            document.getElementById('editMechanic_email').value = mechanic.data.email;
            
            editModal.style.display = 'flex';
        } else {
            alert("Could not load technician data.");
        }
    } catch (err) {
        console.error(err);
    }
}

// 2. Handle Form Submission
editForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(editForm);

    const res = await fetch('actions/update_mechanic.php', {
        method: 'POST',
        body: formData
    });
    
    const result = await res.json();
    if (result.success) {
        showToast(result.message);
        editModal.style.display = 'none';
        loadMechanics(1); // Refresh your table
    } else {
        alert(result.message);
    }
});


// Initial Load
window.onload = () => {
    loadUsers(1);
    loadUsers2(1);
    
};
