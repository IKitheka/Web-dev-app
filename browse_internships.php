
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Intern Connect | Browse Internships</title>
  <link rel="icon" type="image/x-icon" href="/static/images/title.png">
  <link rel="stylesheet" href="../static/css/index.css">
  <style>
   
  </style>
</head>
<body>

  <!-- Aurora Background -->
  <svg class="bg-animation" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
    <!-- SVG path here -->
  </svg>

  <!-- Header -->
  <header class="header">BROWSE INTERNSHIPS</header>

  <!-- Navigation -->
  <nav class="nav">
    <a href="#">INTERN CONNECT</a>
    <a href="#">Dashboard</a>
    <a href="#">Browse Internships</a>
    <a href="#">My Applications</a>
    <a href="#">Profile</a>
    <a href="#" class="ml-auto">Log out</a>
  </nav>

  <!-- Main Content -->
  <div class="content">
    <h1 class="welcome">Available Internship Opportunities</h1>

    <div class="filter-table-wrapper">
      <div class="filter-form">
        <form class="form" id="searchForm">
          <label for="jobTitle">Job Title:</label>
          <input type="text" id="jobTitle" name="job_title" placeholder="Search by title..." class="form-input" />

          <label for="companyName">Company Name:</label>
          <input type="text" id="companyName" name="company_name" placeholder="Search by company..." class="form-input" />

          <label for="location">Location:</label>
          <select id="location" name="location" class="form-input">
            <option value="all">All</option>
            <option value="Remote">Remote</option>
            <option value="Onsite - Farming Town">Onsite - Farming Town</option>
          </select>

          <label for="duration">Duration:</label>
          <select id="duration" name="duration" class="form-input">
            <option value="all">All</option>
            <option value="3 months">3 months</option>
            <option value="6 months">6 months</option>
          </select>

          <label for="industry">Industry:</label>
          <select id="industry" name="industry" class="form-input">
            <option value="all">All</option>
            <option value="Technology">Technology</option>
            <option value="Agriculture">Agriculture</option>
          </select>

          <button type="submit" class="btn">Search</button>
          <button type="button" class="btn" id="clearBtn" style="background-color: #6c757d;">Clear</button>
        </form>
      </div>

      <div class="internship-list">
        <div id="loadingMessage" class="loading" style="display: none;">Loading internships...</div>
        <div id="errorMessage" class="error" style="display: none;"></div>
        <div id="resultsContainer"></div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="footer">
    Intern Connect &copy; 2025 | All Rights Reserved
  </footer>

  <!-- JavaScript -->
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const form = document.getElementById('searchForm');
      const loadingMessage = document.getElementById('loadingMessage');
      const errorMessage = document.getElementById('errorMessage');
      const resultsContainer = document.getElementById('resultsContainer');
      const clearBtn = document.getElementById('clearBtn');

      loadInternships();

      form.addEventListener('submit', function (e) {
        e.preventDefault();
        loadInternships();
      });

      clearBtn.addEventListener('click', function () {
        form.reset();
        loadInternships();
      });

      function loadInternships() {
        loadingMessage.style.display = 'block';
        errorMessage.style.display = 'none';
        resultsContainer.innerHTML = '';

        const formData = new FormData(form);
        const params = new URLSearchParams();

        for (let [key, value] of formData.entries()) {
          params.append(key, value);
        }

        fetch('search_internships.php?' + params.toString())
          .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
          })
          .then(data => {
            loadingMessage.style.display = 'none';
            resultsContainer.innerHTML = data;
          })
          .catch(error => {
            loadingMessage.style.display = 'none';
            errorMessage.style.display = 'block';
            errorMessage.textContent = 'Error loading internships: ' + error.message;
          });
      }
    });
  </script>
</body>
</html>
