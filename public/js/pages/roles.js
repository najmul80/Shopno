// Filename: public/js/pages/roles.js
$(document).ready(function() {

    // Helper function for authenticated API calls
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

    // --- DataTable Initialization for Roles ---
    const table = $('#roles-table').DataTable({
        responsive: true,
        processing: true,
        columns: [
            { data: 'id', title: 'ID' },
            { data: 'name', title: 'Role Name' },
            { 
                data: 'permissions', 
                title: 'Permissions',
                orderable: false,
                render: permissions => {
                    if (!permissions || permissions.length === 0) return '<span class="badge bg-secondary">None</span>';
                    const maxToShow = 5;
                    let html = permissions.slice(0, maxToShow).map(p => `<span class="badge bg-info me-1 mb-1">${p.name}</span>`).join('');
                    if (permissions.length > maxToShow) {
                        html += `<span class="badge bg-dark">${permissions.length - maxToShow}+ more</span>`;
                    }
                    return html;
                } 
            },
            { 
                data: null, title: 'Actions', orderable: false, searchable: false,
                render: (data, type, row) => {
                    if (row.name === 'super-admin') return '<span>Locked</span>';
                    return `
                        <button class="btn btn-sm btn-info edit-btn" data-id="${row.id}">Edit</button>
                        <button class="btn btn-sm btn-danger delete-btn ms-1" data-id="${row.id}">Delete</button>
                    `;
                }
            }
        ],
        language: { emptyTable: "Loading roles..." }
    });

    // --- Function to fetch roles and populate the table ---
    function loadRoles() {
        const api = authApi();
        if (!api) return;

        api.get('admin/roles', { params: { per_page: 100 } }) // Fetch all roles
            .then(response => {
                // *** THIS IS THE CRUCIAL FIX ***
                // Your API response is paginated, so the actual array is inside response.data.data.data
                if (response.data && response.data.data && Array.isArray(response.data.data.data)) {
                    table.clear().rows.add(response.data.data.data).draw();
                } else {
                    console.error("Unexpected API response structure for roles:", response.data);
                    table.settings()[0].oLanguage.sEmptyTable = "No roles found or invalid format.";
                    table.clear().draw();
                }
            })
            .catch(error => {
                console.error("Failed to load roles:", error.response || error);
                const status = error.response?.status;
                let message = "Could not load roles.";
                if (status === 403) {
                    message = "You do not have permission to view roles.";
                }
                table.settings()[0].oLanguage.sEmptyTable = `Error: ${message}`;
                table.clear().draw();
            });
    }

    // --- Function to fetch all available permissions for the modal ---
    function loadPermissions(assignedPermissionIds = []) {
        const api = authApi();
        if (!api) return;

        api.get('admin/roles/all-permissions').then(response => {
            const container = $('#permissions-container');
            container.empty();
            if (response.data && response.data.data) {
                const groupedPermissions = response.data.data.reduce((acc, permission) => {
                    const group = permission.name.split(' ')[1] || 'general';
                    if (!acc[group]) acc[group] = [];
                    acc[group].push(permission);
                    return acc;
                }, {});

                for (const groupName in groupedPermissions) {
                    let groupHtml = `<div class="col-md-4 mb-3"><h6>${groupName.charAt(0).toUpperCase() + groupName.slice(1)}</h6>`;
                    groupedPermissions[groupName].forEach(p => {
                        const isChecked = assignedPermissionIds.includes(p.id);
                        groupHtml += `
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="${p.id}" id="perm_${p.id}" ${isChecked ? 'checked' : ''}>
                                <label class="form-check-label" for="perm_${p.id}">${p.name}</label>
                            </div>
                        `;
                    });
                    groupHtml += '</div>';
                    container.append(groupHtml);
                }
            }
        });
    }

    // --- Event Handlers ---

    $('#addNewRoleBtn').on('click', function() {
        $('#roleModalLabel').text('Add New Role');
        $('#role-form')[0].reset();
        $('#role_id').val('');
        loadPermissions();
        $('#roleModal').modal('show');
    });

    $('#roles-table tbody').on('click', '.edit-btn', function() {
        const roleId = $(this).data('id');
        const api = authApi();
        if(!api) return;
        api.get(`admin/roles/${roleId}`).then(response => {
            const role = response.data.data;
            $('#roleModalLabel').text(`Edit Role: ${role.name}`);
            $('#role_id').val(role.id);
            $('#role_name').val(role.name);
            const assignedIds = role.permissions.map(p => p.id);
            loadPermissions(assignedIds);
            $('#roleModal').modal('show');
        });
    });

    $('#saveRoleBtn').on('click', function() {
        const api = authApi();
        if (!api) return;
        const data = {
            name: $('#role_name').val(),
            permissions: $('input[name="permissions[]"]:checked').map(function() { return $(this).val(); }).get()
        };
        const roleId = $('#role_id').val();
        const url = roleId ? `admin/roles/${roleId}` : 'admin/roles';
        const method = roleId ? 'put' : 'post';
        
        api[method](url, data)
            .then(response => {
                $('#roleModal').modal('hide');
                Swal.fire('Success!', 'Role saved successfully!', 'success');
                loadRoles();
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
                    Swal.fire('Error', 'Could not save the role.', 'error');
                }
            });
    });

    $('#roles-table tbody').on('click', '.delete-btn', function() {
        const roleId = $(this).data('id');
        Swal.fire({
            title: 'Are you sure?', text: "This action cannot be undone!", icon: 'warning',
            showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                const api = authApi();
                if(!api) return;
                api.delete(`admin/roles/${roleId}`).then(response => {
                    Swal.fire('Deleted!', 'The role has been deleted.', 'success');
                    loadRoles();
                }).catch(error => Swal.fire('Error', 'Could not delete the role.', 'error'));
            }
        });
    });

    // --- Initial Load ---
    loadRoles();
});