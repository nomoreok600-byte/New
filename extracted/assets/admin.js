// Admin AJAX helpers (CSRF token injected on every admin page via window.VL_CSR)
function vlAdminCsrf() {
    var input = document.querySelector('input[name="csrf"]');
    return (input && input.value) || window.VL_CSR || '';
}

function adminPost(body, cb) {
    fetch(window.location.pathname, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        credentials: 'same-origin',
        body: body + '&csrf=' + encodeURIComponent(vlAdminCsrf())
    })
        .then(function (r) {
            return r.text().then(function (t) {
                try {
                    return JSON.parse(t);
                } catch (e) {
                    console.error('Admin action: non-JSON response', t && t.slice(0, 300));
                    throw new Error('Server error (non-JSON response). Check the PHP error log.');
                }
            });
        })
        .then(cb)
        .catch(function (err) {
            alert(err && err.message ? err.message : 'Request failed');
        });
}

function deleteRow(action, id, label) {
    if (!confirm('Delete this ' + label + '? This cannot be undone.')) return;
    adminPost('ajax_action=' + action + '&id=' + id, function (data) {
        if (data.success) location.reload();
        else alert(data.message || 'Action failed');
    });
}

function toggleField(action, id) {
    adminPost('ajax_action=' + action + '&id=' + id, function (data) {
        if (data.success) location.reload();
        else alert(data.message || 'Action failed');
    });
}

function removeThumbFile(id) {
    if (!confirm("Remove this video's uploaded thumbnail file?")) return;
    adminPost('ajax_action=delete_thumb_file&id=' + id, function (data) {
        if (data.success) location.reload();
        else alert(data.message || 'Action failed');
    });
}

// Select-all checkbox for bulk actions + live table filter
document.addEventListener('DOMContentLoaded', function () {
    var selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('input[name="ids[]"]').forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
        });
    }

    var filter = document.getElementById('tableFilter');
    if (filter) {
        filter.addEventListener('input', function () {
            var q = filter.value.toLowerCase();
            document.querySelectorAll('table tbody tr').forEach(function (tr) {
                tr.style.display = tr.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
            });
        });
    }
});
