<?php require_once __DIR__ . '/../backend/admin/session_check.php'; ?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Document</title>
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />

    <link rel="stylesheet" href="dashboard.css" />

    <!-- script -->
    <script defer src="dashboard.js"></script>
  </head>
  <body>
    <section class="admin-container">
      <header>
        <div>
          <h1>Admin Dashboard</h1>
          <p>Memory Test Student Records</p>
        </div>
        <div class="header-actions">
          <button id="cleanupButton" class="cleanup-btn">
            <i class="fa-solid fa-trash-can"></i> Data Cleanup
          </button>
          <button id="logoutButton">Logout</button>
        </div>
      </header>
      <main>
        <section class="dashboard-cards">
          <div class="card">
            <h3>Total Students</h3>
            <p id="totalStudents">0</p>
            <span>Total Applicants</span>
          </div>
          <div class="card">
            <h3>Students Passed</h3>
            <p id="passedStudents">0</p>
            <span>Qualified students</span>
          </div>
          <div class="card">
            <h3>Attempts Today</h3>
            <p id="todayTests">0</p>
            <span id="currentDate"><?php echo date('d F Y'); ?></span>
          </div>
        </section>
        <section class="student-table">
          <header>
            <h2>Student Records</h2>
            <div class="header-actions">
              <div class="search-box">
                <input
                  type="text"
                  id="searchInput"
                  placeholder="Search by name or email..."
                />
              </div>
              <button id="exportExcelBtn" class="export-btn">
                <i class="fa-solid fa-file-excel"></i> Export to Excel
              </button>
            </div>
          </header>
          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Email(s)</th>
                  <th>Phone Number</th>
                  <th>City</th>
                  <th>Standard</th>
                  <th>School Name</th>
                  <th>Language</th>
                  <th>Score</th>
                  <th>Status</th>
                  <th>Attempts</th>
                  <th>Attempt Date</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="studentTableBody">
              </tbody>
            </table>
          </div>
        </section>
      </main>
    </section>

    <section id="viewModel">
      <div class="view">
        <div class="model-head">
          <h1>Student Details</h1>
          <button id="closeViewModel">&times;</button>
        </div>
        <div class="model-body">
          <div class="detail-row">
            <span class="detail-label">Full Name:</span>
            <span class="detail-value" id="detailName">Raj Ramesh Patel</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Email(s):</span>
            <span class="detail-value" id="detailEmail"
              >john.doe@example.com</span
            >
          </div>
          <div class="detail-row">
            <span class="detail-label">Phone Number:</span>
            <span class="detail-value" id="detailPhone">+1 234 567 8901</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">City:</span>
            <span class="detail-value" id="detailCity">Ahmedabad</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Standard:</span>
            <span class="detail-value" id="detailStandard">10th</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">School Name:</span>
            <span class="detail-value" id="detailSchool"
              >Example High School</span
            >
          </div>
          
          <div class="detail-row">
            <span class="detail-label">Score:</span>
            <span class="detail-value" id="detailScore">25</span>
          </div>
          <div class="detail-row">
            <span class="detail-label">Status:</span>
            <span class="detail-value pass" id="detailStatus">Passed</span>
          </div>
          <div class="detail-row Attempts">
            <span class="detail-label">Attempts:</span>
            <div class="attempt-list" id="detailAttempts">
              <div class="attempt-item">
                <div>
                <span class="attempt-number">1</span>
                </div>
                <div class="details">
                <span class="attempt-date">2024-06-10</span>
                <span class="attempt-score passed">score: <span>25 - passed</span></span>
                </div>
              </div>
              <div class="attempt-item">
                <div>
                <span class="attempt-number">2</span>
                </div>
                <div class="details">
                <span class="attempt-date">2024-06-15</span>
                <span class="attempt-score failed">score: <span>18 - failed</span></span>
                </div>
              </div>

              
              

            </div>
            
        </div>
      </div>
    </section>
  <section id="deleteModel" >
    <div class="delete-view">
      <div class="delete-head">
        <h1>Confirm Deletion</h1>
        <button id="closeDeleteModel">&times;</button>
      </div>
      <div class="delete-body">
        <p>Are you sure you want to delete this student record?</p>
        <div class="delete-actions">
          <button id="cancelDelete" class="cancel-btn">Cancel</button>
          <button id="confirmDelete" class="confirm-btn">Delete</button>
        </div>
      </div>
    </div>

  </section>

  <!-- Cleanup Modal -->
  <section id="cleanupModal">
    <div class="cleanup-modal-view">
      <div class="cleanup-modal-head">
        <h1><i class="fa-solid fa-broom"></i> Data Cleanup Manager</h1>
        <button id="closeCleanupModal">&times;</button>
      </div>
      <div class="cleanup-modal-body">
        <!-- Settings Section -->
        <div class="cleanup-settings">
          <h3>Cleanup Settings</h3>
          <div class="setting-row">
            <label for="retentionDays">Delete records older than:</label>
            <div class="setting-input">
              <input type="number" id="retentionDays" min="1" max="365" value="30">
              <span>days</span>
              <button id="saveSettings" class="save-btn">Save</button>
            </div>
          </div>
          <p class="setting-note">
            <i class="fa-solid fa-info-circle"></i>
            Records older than the specified days will be eligible for deletion.
          </p>
        </div>

        <!-- Preview Section -->
        <div class="cleanup-preview">
          <h3>Records to be Deleted</h3>
          <div id="cleanupPreviewContent">
            <p class="loading">Loading...</p>
          </div>
        </div>

        <!-- Actions -->
        <div class="cleanup-actions">
          <button id="cancelCleanup" class="cancel-btn">Cancel</button>
          <button id="confirmCleanup" class="confirm-cleanup-btn">
            <i class="fa-solid fa-trash"></i> Delete Old Records
          </button>
        </div>
      </div>
    </div>
  </section>

  </body>
</html>
