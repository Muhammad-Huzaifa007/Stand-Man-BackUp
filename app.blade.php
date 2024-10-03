<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
       @import url('https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap');

        body {
            font-family: "Manrope", sans-serif;
            font-optical-sizing: auto;
            font-weight: <weight>;
            font-style: normal;
            background-color: #f8f9fa;
            margin: 0;
        }

        .sidebar {
            height: calc(100vh - 60px); /* Full height minus header */
            background-color: #212529;
            padding-top: 20px;
            position: fixed;
            width: 250px;
            top: 60px; /* Position below the header */
            z-index: 90; /* Ensure the sidebar is below the footer */
        }

        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            padding: 15px;
            display: block;
            font-weight: 500;
            transition: background 0.3s, color 0.3s;
        }

        .sidebar a:hover {
            background-color: #007bff;
            color: white;
        }

        .submenu {
            display: none;
            background-color: #343a40;
        }

        .submenu a {
            padding-left: 40px;
            font-size: 14px;
        }

        .sidebar a:hover + .submenu, .submenu:hover {
            display: block;
        }

        .content {
            margin-left: 250px;
            margin-top: 60px; /* Space for header */
            padding: 20px;
            height: calc(100vh - 60px - 25px); /* Adjust height for footer */
            overflow-y: auto; /* Adding vertical scroll only for content */
            position: relative; /* Positioning relative to sidebar */
            top: 0; /* Reset top position for content */
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center; /* Center items vertically */
            padding: 0 20px; /* Adjust padding as needed */
            background-color: #007bff;
            color: white;
            position: fixed; /* Fixed position for header */
            top: 0;
            left: 0;
            width: 100%; /* Full width for header */
            height: 60px; /* Set a fixed height for the navbar */
            z-index: 100; /* Ensure header is above content */
        }
        .header div{
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .navbar-logo {
            height: 60px; /* Make logo fill the navbar height */
            width: auto; /* Maintain aspect ratio */
            padding: 2px 5px; /* Optional: add some horizontal padding */
            margin-bottom: 10px
        }

        .admin-logo {
            height: 40px; /* Keep the height of the admin logo consistent */
            width: auto; /* Maintain aspect ratio */
            cursor: pointer; /* Make the image clickable */
        }

        .footer {
            display: flex;
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
            height: 25px; /* Adjust height */
            background-color: #007bff;
            color: white;
            position: fixed;
            bottom: 0;
            left: 0; /* Reset left position */
            width: 100%; /* Full width for footer */
            font-size: 12px; /* Adjust font size */
            margin: 0;
            z-index: 100; /* Ensure footer is above the sidebar */
        }

        @media (max-width: 767px) {
            .sidebar {
                width: 100%;
                height: auto;
                top: 60px; /* Adjust top for small screens */
            }

            .content {
                margin-left: 0;
                margin-top: 60px; /* Space for header on small screens */
            }

            .header {
                width: 100%; /* Full width for header on small screens */
                left: 0; /* Reset left position for small screens */
            }

            .footer {
                width: 100%; /* Full width for footer on small screens */
                left: 0; /* Reset left position for small screens */
            }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <div class="header">
        <div>
            <img src="{{ asset('images/huzaifa_logo2.png') }}" alt="Logo" class="navbar-logo">
            <h2>StandMan</h2>
        </div>
        <!-- Admin image with modal trigger -->
        <img src="{{ asset('images/huzaifa_admin.png') }}" alt="Admin Logo" data-toggle="modal" data-target="#adminModal" class="admin-logo">
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <a href="/dashboardd" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href=""><i class="fas fa-users"></i> Users</a>
        <div class="submenu">
            <a href="/customers"><i class="fas fa-user"></i> Customers</a>
            <a href="/employees"><i class="fas fa-user-tie"></i> Employees</a>
        </div>

        <a href="#"><i class="fas fa-briefcase"></i> Jobs</a>
        <div class="submenu">
            <a href="/startedjobs"><i class="fas fa-play"></i> Started Jobs</a>
            <a href="/completedjobs"><i class="fas fa-check"></i> Completed Jobs</a>
            <a href="/cancelledjobs"><i class="fas fa-times"></i> Cancelled Jobs</a>
        </div>

        <a href="#"><i class="fas fa-cog"></i> Settings</a>
        <div class="submenu">
            <a href="/generalsettings"><i class="fas fa-wrench"></i> General Settings</a>
            <a href="#"><i class="fas fa-lock"></i> Privacy Settings</a>
        </div>

        <!-- Static report route -->
        <a href="#"><i class="fas fa-chart-bar"></i> Reports</a>
    </div>

    <!-- Content -->
    <div class="content">
        @yield('content')
    </div>

    <!-- Footer -->
    <div class="footer">
        <p class="mt-3">&copy; 2024 StandMan - All Rights Reserved</p>
    </div>

    <!-- Admin Modal -->
    <div class="modal fade" id="adminModal" tabindex="-1" aria-labelledby="adminModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="adminModalLabel">Admin Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><strong>Email:</strong> {{ Session::get('admin_email') }}</p>
                </div>
                <div class="modal-footer">
                    <!-- Logout Form -->
                    <form action="{{ route('logout') }}" method="POST" style="display: inline;">
                        @csrf <!-- CSRF Token -->
                        <button type="submit" class="btn btn-danger">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
