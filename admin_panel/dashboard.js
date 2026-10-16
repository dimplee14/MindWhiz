function viewModelToggle() {
  const viewmodelOpenBtn = document.querySelectorAll(".eye-btn");
  const viewModel = document.getElementById("viewModel");
  const viewmodelCloseBtn = document.querySelector("#closeViewModel");

  viewmodelOpenBtn.forEach((button) => {
    button.addEventListener("click", () => {
      viewModel.classList.add("active");
      document.body.classList.add("modal-open");
    });
  });

  viewmodelCloseBtn.addEventListener("click", () => {
    viewModel.classList.remove("active");
    document.body.classList.remove("modal-open");
  });
}
viewModelToggle();

function deleteModelToggle() {
  const deletemodelOpenBtn = document.querySelectorAll(".delete-btn");
  const deleteModel = document.getElementById("deleteModel");
  const deletemodelCloseBtn = document.querySelector("#closeDeleteModel");
  const cancelButton = document.querySelector(".cancel-btn");

  deletemodelOpenBtn.forEach((button) => {
    button.addEventListener("click", () => {
      deleteModel.classList.add("active");
      document.body.classList.add("modal-open");
    });
    deletemodelCloseBtn.addEventListener("click", () => {
      deleteModel.classList.remove("active");
      document.body.classList.remove("modal-open");
    });
    cancelButton.addEventListener("click", () => {
      deleteModel.classList.remove("active");
      document.body.classList.remove("modal-open");
    });
  });
}
deleteModelToggle();

// Cleanup Modal Toggle
function cleanupModalToggle() {
  const cleanupBtn = document.getElementById('cleanupButton');
  const cleanupModal = document.getElementById('cleanupModal');
  const closeBtn = document.getElementById('closeCleanupModal');
  const cancelBtn = document.getElementById('cancelCleanup');

  if (cleanupBtn) {
    cleanupBtn.addEventListener('click', () => {
      cleanupModal.classList.add('active');
      document.body.classList.add('modal-open');
      loadCleanupSettings();
    });
  }

  [closeBtn, cancelBtn].forEach(btn => {
    if (btn) {
      btn.addEventListener('click', () => {
        cleanupModal.classList.remove('active');
        document.body.classList.remove('modal-open');
      });
    }
  });
}
cleanupModalToggle();

// Load Cleanup Settings
async function loadCleanupSettings() {
  try {
    const res = await fetch('../backend/db_store/cleanup_old_records.php?action=get_settings');
    const data = await res.json();
    
    if (data.success) {
      document.getElementById('retentionDays').value = data.data.retention_days;
      loadCleanupPreview();
    }
  } catch (err) {
    console.error('Failed to load cleanup settings:', err);
  }
}

// Load Cleanup Preview
async function loadCleanupPreview() {
  const previewContent = document.getElementById('cleanupPreviewContent');
  previewContent.innerHTML = '<p class="loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</p>';
  
  try {
    const res = await fetch('../backend/db_store/cleanup_old_records.php?action=preview');
    const data = await res.json();
    
    if (data.success) {
      const preview = data.data;
      
      if (preview.records_count === 0) {
        previewContent.innerHTML = `
          <div class="no-records">
            <i class="fa-solid fa-check-circle"></i>
            <p>No old records found. All records are within the ${preview.retention_days} days retention period.</p>
          </div>
        `;
        document.getElementById('confirmCleanup').disabled = true;
      } else {
        let html = `
          <div class="preview-summary">
            <p><strong>${preview.records_count}</strong> records will be deleted (older than <strong>${preview.retention_days} days</strong>)</p>
            <p class="cutoff-date">Cutoff date: ${new Date(preview.cutoff_date).toLocaleDateString()}</p>
          </div>
          <div class="preview-list">
            <h4>Sample Records:</h4>
            <ul>
        `;
        
        preview.records.forEach(record => {
          const fullName = [record.name, record.parent_name, record.surname].filter(Boolean).join(' ');
          html += `
            <li>
              <span class="record-name">${escapeHtml(fullName)}</span>
              <span class="record-email">${escapeHtml(record.email)}</span>
              <span class="record-date">${new Date(record.created_at).toLocaleDateString()}</span>
            </li>
          `;
        });
        
        html += '</ul>';
        if (preview.has_more) {
          html += `<p class="more-records">...and ${preview.records_count - preview.records.length} more</p>`;
        }
        html += '</div>';
        
        previewContent.innerHTML = html;
        document.getElementById('confirmCleanup').disabled = false;
      }
    }
  } catch (err) {
    console.error('Failed to load preview:', err);
    previewContent.innerHTML = '<p class="error">Failed to load preview</p>';
  }
}

// Save Cleanup Settings
document.getElementById('saveSettings')?.addEventListener('click', async () => {
  const days = parseInt(document.getElementById('retentionDays').value);
  const btn = document.getElementById('saveSettings');
  
  if (days < 1 || days > 365) {
    alert('Please enter a value between 1 and 365 days');
    return;
  }
  
  btn.disabled = true;
  btn.textContent = 'Saving...';
  
  try {
    const res = await fetch('../backend/db_store/cleanup_old_records.php?action=update_settings', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ retention_days: days })
    });
    
    const data = await res.json();
    
    if (data.success) {
      alert('Settings saved successfully!');
      loadCleanupPreview();
    } else {
      alert('Failed to save settings: ' + data.message);
    }
  } catch (err) {
    console.error('Failed to save settings:', err);
    alert('Failed to save settings');
  } finally {
    btn.disabled = false;
    btn.textContent = 'Save';
  }
});

// Confirm Cleanup
document.getElementById('confirmCleanup')?.addEventListener('click', async () => {
  const days = document.getElementById('retentionDays').value;
  
  if (!confirm(`Are you sure you want to delete all records older than ${days} days? This action cannot be undone!`)) {
    return;
  }
  
  const btn = document.getElementById('confirmCleanup');
  btn.disabled = true;
  btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Deleting...';
  
  try {
    const res = await fetch('../backend/db_store/cleanup_old_records.php?action=delete', {
      method: 'POST'
    });
    
    const data = await res.json();
    
    if (data.success) {
      alert(data.message);
      document.getElementById('cleanupModal').classList.remove('active');
      document.body.classList.remove('modal-open');
      fetchStudents();
      fetchAdminCounts();
    } else {
      alert('Cleanup failed: ' + data.message);
    }
  } catch (err) {
    console.error('Cleanup failed:', err);
    alert('Cleanup failed');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fa-solid fa-trash"></i> Delete Old Records';
  }
});

// Logout button handler - redirect to backend logout
const logoutBtn = document.getElementById('logoutButton');
if (logoutBtn) {
  logoutBtn.addEventListener('click', () => {
    window.location.href = '../backend/admin/logout.php';
  });
}

// Render students into table
function renderStudents(students) {
  const tbody = document.getElementById('studentTableBody');
  tbody.innerHTML = '';
  students.forEach((s) => {
    const tr = document.createElement('tr');
    // Determine status strictly by the latest attempt
    let statusClass = '';
    let statusText = 'N/A';
    if (s.last_passed == 1) { statusClass = 'pass'; statusText = 'Passed'; }
    else if (s.last_passed == 0) { statusClass = 'fail'; statusText = 'Failed'; }

    // Create full name with format: Name ParentName Surname
    let fullName = escapeHtml(s.name) || '';
    if (s.parent_name) {
      fullName += ` ${escapeHtml(s.parent_name)}`;
    }
    if (s.surname) {
      fullName += ` ${escapeHtml(s.surname)}`;
    }

    // Display all emails if available, otherwise show single email
    const emailDisplay = s.emails && s.emails.length > 0 ? s.emails.join(', ') : escapeHtml(s.email);

    // Language display (convert code to readable)
    let langDisplay = '-';
    if (s.language === 'eng') langDisplay = 'English';
    else if (s.language === 'guj') langDisplay = 'Gujarati';
    else if (s.language) langDisplay = escapeHtml(s.language);

    tr.innerHTML = `
      <td>${fullName}</td>
      <td>${emailDisplay}</td>
      <td>${escapeHtml(s.phone)}</td>
      <td>${escapeHtml(s.city)}</td>
      <td>${escapeHtml(s.standard)}</td>
      <td>${escapeHtml(s.school)}</td>
      <td>${langDisplay}</td>
      <td>${(s.last_score !== null ? s.last_score : '-')}</td>
      <td><div class="${statusClass}">${statusText}</div></td>
      <td>${s.attempts}</td>
      <td>${(s.last_attempt_at ? s.last_attempt_at.split(' ')[0] : '-')}</td>
      <td class="action-buttons">
        <button class="eye-btn" data-id="${s.id}"><i class="fa-regular fa-eye" style="color: #1d2b44"></i></button>
        <button class="delete-btn" data-id="${s.id}"><i class="fa-regular fa-trash-can" style="color: #6d3649"></i></button>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

function escapeHtml(str){
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

// Fetch students from backend and render
async function fetchStudents(q=''){
  try{
    const url = new URL('../backend/db_store/get_students.php', window.location.href);
    if (q) url.searchParams.set('q', q);
    const res = await fetch(url.toString(), { cache: 'no-store' });
    if (!res.ok) throw new Error('Network error');
    const data = await res.json();
    renderStudents(data.students || []);
  }catch(err){
    console.error('Failed to fetch students', err);
  }
}

// delegated click handlers for view and delete
document.addEventListener('click', async (e) => {
  const eye = e.target.closest('.eye-btn');
  if (eye) {
    const id = eye.getAttribute('data-id');
    try{
      const res = await fetch(`../backend/db_store/get_student_details.php?id=${encodeURIComponent(id)}`);
      if (!res.ok) throw new Error('Failed to load details');
      const payload = await res.json();
      const s = payload.student;
      const attempts = payload.attempts || [];
      const allStudents = payload.all_students || [];
      
      // Create full name with format: Name ParentName Surname
      let fullName = s.name || '';
      if (s.parent_name) {
        fullName += ` ${s.parent_name}`;
      }
      if (s.surname) {
        fullName += ` ${s.surname}`;
      }
      
      document.getElementById('detailName').textContent = fullName;
      
      // Display all emails for students with same name
      const emails = allStudents.map(st => st.email).join(', ');
      document.getElementById('detailEmail').textContent = emails || s.email || '';
      
      document.getElementById('detailPhone').textContent = s.phone || '';
      document.getElementById('detailCity').textContent = s.city || '';
      document.getElementById('detailStandard').textContent = s.standard || '';
      document.getElementById('detailSchool').textContent = s.school || '';
      document.getElementById('detailScore').textContent = attempts[0] && attempts[0].score !== null ? attempts[0].score : '-';
      
      const statusEl = document.getElementById('detailStatus');
      if (attempts[0] && attempts[0].passed == 1) { statusEl.textContent = 'Passed'; statusEl.classList.remove('fail'); statusEl.classList.add('pass'); }
      else if (attempts[0] && attempts[0].passed == 0) { statusEl.textContent = 'Failed'; statusEl.classList.remove('pass'); statusEl.classList.add('fail'); }
      else { statusEl.textContent = 'N/A'; statusEl.classList.remove('pass'); statusEl.classList.remove('fail'); }

      const list = document.getElementById('detailAttempts');
      list.innerHTML = '';
      attempts.forEach((a, idx) => {
        const div = document.createElement('div');
        div.className = 'attempt-item';
        const passedClass = a.passed == 1 ? 'passed' : (a.passed == 0 ? 'failed' : '');
        div.innerHTML = `<div><span class="attempt-number">${a.attempt_number}</span></div><div class="details"><span class="attempt-date">${a.attempt_at.split(' ')[0]}</span><span class="attempt-score ${passedClass}">score: <span>${a.score !== null ? a.score : '-'} ${a.passed == 1 ? '- passed' : (a.passed == 0 ? '- failed' : '')}</span></span></div>`;
        list.appendChild(div);
      });

      const viewModel = document.getElementById('viewModel');
      viewModel.classList.add('active');
      document.body.classList.add('modal-open');
    }catch(err){ console.error(err); }
  }

  const del = e.target.closest('.delete-btn');
  if (del) {
    const deleteModel = document.getElementById('deleteModel');
    deleteModel.classList.add('active');
    document.body.classList.add('modal-open');
    // store id on confirm button for later handling
    const confirm = document.getElementById('confirmDelete');
    confirm.setAttribute('data-id', del.getAttribute('data-id'));
  }
});

// wire modal close buttons
document.getElementById('closeViewModel').addEventListener('click', () => { document.getElementById('viewModel').classList.remove('active'); document.body.classList.remove('modal-open'); });
document.getElementById('closeDeleteModel').addEventListener('click', () => { document.getElementById('deleteModel').classList.remove('active'); document.body.classList.remove('modal-open'); });
document.getElementById('cancelDelete').addEventListener('click', () => { document.getElementById('deleteModel').classList.remove('active'); document.body.classList.remove('modal-open'); });

// search input
document.getElementById('searchInput').addEventListener('input', (e) => { fetchStudents(e.target.value); });

// Export to Excel button handler
document.getElementById('exportExcelBtn').addEventListener('click', async () => {
  const btn = document.getElementById('exportExcelBtn');
  const originalText = btn.innerHTML;
  try {
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Exporting...';
    
    const response = await fetch('../backend/db_store/export_students.php', {
      method: 'GET',
      credentials: 'same-origin'
    });
    
    if (!response.ok) {
      // Try to get error text
      const text = await response.text();
      console.error('Export error:', text);
      throw new Error('Export failed: ' + (text.substring(0, 100) || response.statusText));
    }
    
    // Check if response is actually an Excel file
    const contentType = response.headers.get('Content-Type');
    if (!contentType || !contentType.includes('spreadsheet')) {
      const text = await response.text();
      console.error('Unexpected content type:', contentType, 'Body:', text);
      throw new Error('Server did not return an Excel file. Check console for details.');
    }
    
    // Get the filename from Content-Disposition header if available
    const contentDisposition = response.headers.get('Content-Disposition');
    let filename = 'Student_Records_' + new Date().toISOString().slice(0, 10) + '.xlsx';
    if (contentDisposition) {
      const filenameMatch = contentDisposition.match(/filename="?(.+)"?/);
      if (filenameMatch && filenameMatch[1]) {
        filename = filenameMatch[1];
      }
    }
    
    // Create blob and download
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
    
  } catch (err) {
    console.error('Export failed:', err);
    alert('Failed to export data: ' + err.message);
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalText;
  }
});

// initial load
fetchStudents();

// Fetch admin counts and update dashboard cards
async function fetchAdminCounts() {
  try {
    const res = await fetch('../backend/db_store/admin_counts.php', { cache: 'no-store' });
    if (!res.ok) throw new Error('Network response was not ok');
    const data = await res.json();
    const totalEl = document.getElementById('totalStudents');
    const passedEl = document.getElementById('passedStudents');
    const todayEl = document.getElementById('todayTests');
    if (totalEl) totalEl.textContent = data.total_students ?? '0';
    if (passedEl) passedEl.textContent = data.passed_students ?? '0';
    if (todayEl) todayEl.textContent = data.attempts_today ?? '0';
  } catch (err) {
    console.error('Failed to fetch admin counts:', err);
  }
}

// Initial fetch and periodic refresh
fetchAdminCounts();
setInterval(fetchAdminCounts, 60 * 1000); // refresh every 60s

// Confirm delete handler - sends POST to backend to remove student and attempts
document.getElementById('confirmDelete').addEventListener('click', async (e) => {
  const btn = document.getElementById('confirmDelete');
  const id = btn.getAttribute('data-id');
  if (!id) return;
  btn.disabled = true;
  try {
    const res = await fetch('../backend/db_store/delete_student.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ id: id })
    });
    const payload = await res.json();
    if (!res.ok || payload.error) throw new Error(payload.error || 'Delete failed');
    document.getElementById('deleteModel').classList.remove('active');
    document.body.classList.remove('modal-open');
    fetchStudents();
    fetchAdminCounts();
  } catch (err) {
    alert('Failed to delete student: ' + (err.message || err));
    console.error(err);
  } finally {
    btn.disabled = false;
    btn.removeAttribute('data-id');
  }
});
