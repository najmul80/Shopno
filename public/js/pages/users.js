// Filename: public/js/pages/users.js
$(document).ready(function() {

    function authApi() {
        const token = localStorage.getItem('access_token');
        if (!token) {
            window.location.href = '/login';
            return null;
        }
        return axios.create({
            baseURL: '/api/v1/',
            headers: { 'Authorization': `Bearer ${token}` }
        });
    }

    // --- DataTable Initialization ---
    const table = $('#users-table').DataTable({
        responsive: true,
        processing: true,
        columns: [
            { data: 'id', title: 'ID' },
            { data: 'name', title: 'Name' },
            { data: 'email', title: 'Email' },
            { 
                data: 'roles', 
                title: 'Role(s)',
                render: roles => roles.map(role => `<span class="badge bg-info me-1">${role.name}</span>`).join(' ')
            },
            { data: 'store.name', title: 'Store', defaultContent: 'N/A' },
            { 
                data: 'is_active', 
                title: 'Status',
                render: data => data ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-danger">Inactive</span>'
            },
            { 
                data: null, 
                title: 'Actions', 
                orderable: false, 
                searchable: false,
                render: (data, type, row) => `
                    <button class="btn btn-sm btn-info edit-btn" data-id="${row.id}">Edit</button>
                    <button class="btn btn-sm btn-danger delete-btn ms-1" data-id="${row.id}">Delete</button>
                `
            }
        ],
        language: { emptyTable: "Loading users..." }
    });

    // --- Data Loading Functions ---

    function loadUsers() {
        const api = authApi();
        if (!api) return;

        api.get('users', { params: { per_page: 500 } })
            .then(response => {
                if (response.data && Array.isArray(response.data.data)) {
                    table.clear().rows.add(response.data.data).draw();
                } else {
                    table.settings()[0].oLanguage.sEmptyTable = "No users found.";
                    table.clear().draw();
                }
            })
            .catch(error => console.error("Failed to load users:", error));
    }

    function loadRoles(selectedRoles = []) {
        const api = authApi();
        if (!api) return;

        api.get('admin/roles') // As per your api.php for super-admin
            .then(response => {
                const rolesSelect = $('#user_roles');
                rolesSelect.empty();
                response.data.data.forEach(role => {
                    const isSelected = selectedRoles.includes(role.name);
                    rolesSelect.append(`<option value="${role.name}" ${isSelected ? 'selected' : ''}>${role.name}</option>`);
                });
            });
    }

    function loadStores(selectedStoreId = null) {
        const api = authApi();
        if (!api) return;
        
        api.get('stores', { params: { per_page: 500 } })
            .then(response => {
                const storeSelect = $('#user_store_id');
                storeSelect.empty().append('<option value="">No Store</option>');
                response.data.data.forEach(store => {
                    storeSelect.append(`<option value="${store.id}" ${store.id == selectedStoreId ? 'selected' : ''}>${store.name}</option>`);
                });
            });
    }

    // --- Event Handlers ---

    $('#addNewUserBtn').on('click', function() {
        $('#userModalLabel').text('Add New User');
        $('#user-form')[0].reset();
        $('#user_id').val('');
        $('#user_is_active').prop('checked', true);
        loadRoles();
        loadStores();
        $('#userModal').modal('show');
    });

    $('#users-table tbody').on('click', '.edit-btn', function() {
        const userId = $(this).data('id');
        const api = authApi();
        if(!api) return;

        api.get(`users/${userId}`)
            .then(response => {
                const user = response.data.data;
                $('#userModalLabel').text(`Edit User: ${user.name}`);
                $('#user_id').val(user.id);
                $('#user_name').val(user.name);
                $('#user_email').val(user.email);
                $('#user_password').val('').attr('placeholder', 'Leave empty to keep current password');
                $('#user_password_confirmation').val('');
                $('#user_is_active').prop('checked', user.is_active);
                
                const userRoleNames = user.roles.map(role => role.name);
                loadRoles(userRoleNames);
                loadStores(user.store_id);

                $('#userModal').modal('show');
            });
    });

    $('#saveUserBtn').on('click', function() {
        const api = authApi();
        if (!api) return;

        const form = $('#user-form')[0];
        const formData = new FormData(form);
        const userId = $('#user_id').val();
        
        if (!$('#user_is_active').is(':checked')) {
            formData.set('is_active', 0);
        }

        let url = 'users';
        if (userId) {
            url = `users/${userId}`;
            formData.append('_method', 'PUT');
        }
        
        api.post(url, formData)
            .then(response => {
                $('#userModal').modal('hide');
                Swal.fire('Success!', 'User saved successfully!', 'success');
                loadUsers();
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    let errorHtml = '<ul class="text-start ps-3">';
                    for (const field in error.response.data.errors) {
                        errorHtml += `<li>${error.response.data.errors[field][0]}</li>`;
                    }
                    errorHtml += '</ul>';
                    Swal.fire({ title: 'Validation Failed', html: errorHtml, icon: 'error' });
                } else {
                    Swal.fire('Error', 'Could not save the user.', 'error');
                }
            });
    });

    $('#users-table tbody').on('click', '.delete-btn', function() {
        const userId = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const api = authApi();
                if(!api) return;
                api.delete(`users/${userId}`)
                    .then(response => {
                        Swal.fire('Deleted!', 'The user has been deleted.', 'success');
                        loadUsers();
                    })
                    .catch(error => Swal.fire('Error', 'Could not delete the user.', 'error'));
            }
        });
    });

    // --- Initial Load ---
    loadUsers();
});