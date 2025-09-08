<?php
session_start();
require 'db.php';

// Check if the user is logged in and is an owner

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.html?message=" . urlencode("Access denied. Please log in as an owner."));
    exit();
}

$username = htmlspecialchars($_SESSION['username'] ?? $_SESSION['email']); // Prefer username, fallback to email
$owner_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Owner Dashboard</title>
    
    <style>
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: #f8f9fa;
            color: #333;
            display: flex;
            min-height: 100vh;
        }

        
        .sidebar {
            width: 240px;
            background-color: #e53935; 
            color: white;
            height: 100vh;
            padding: 1.5rem 1rem;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
        }

        .sidebar h2 {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
            font-weight: 700;
        }

        .sidebar ul {
            list-style: none;
            flex-grow: 1;
        }

        .sidebar ul li {
            margin-bottom: 0.8rem;
            padding: 0.8rem 1rem;
            background-color: #ff6f61; 
            border-radius: 8px;
            cursor: pointer;
            user-select: none;
            transition: background-color 0.3s ease;
            font-weight: 500;
        }

        .sidebar ul li:hover {
            background-color: #d32f2f; 
        }

        .sidebar .logout-btn-sidebar {
            margin-top: auto;
            text-align: center;
            padding: 0.8rem 1rem;
            background-color: #c62828; 
            border-radius: 8px;
            color: white;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .sidebar .logout-btn-sidebar:hover {
            background-color: #a31515;
        }

        /* Main content area */
        .main-content {
            flex: 1;
            padding: 2rem;
            background-color: #fdfdfd;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .main-content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .main-content-header h1 {
            font-size: 2rem;
            color: #e53935;
            font-weight: 700;
        }

        /* Overview section */
        .overview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .overview-card {
            background-color: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            text-align: center;
            transition: transform 0.2s ease;
        }

        .overview-card:hover {
            transform: translateY(-5px);
        }

        .overview-card h3 {
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 0.5rem;
        }

        .overview-card p {
            font-size: 2.5rem;
            font-weight: 700;
            color: #e53935;
        }

        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        th {
            background-color: #ffe5e5;
            color: #e53935;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9em;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .status {
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            text-transform: capitalize;
        }

     
        .available { background: #e0f7ea; color: #2e7d32; } 
        .occupied { background: #ffeaea; color: #c62828; } 
        .maintenance { background: #fff3cd; color: #856404; } 
        .pending { background: #e0f2f7; color: #0277bd; } 
        .approved { background: #e0f7ea; color: #2e7d32; } 
        .rejected { background: #ffeaea; color: #c62828; } 
        .cancelled { background: #f0f0f0; color: #616161; } 


        
        .action-btn {
            background: #e53935;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-right: 5px;
            white-space: nowrap; 
        }

        .action-btn:hover {
            background: #b71c1c;
        }

        .action-btn.approve {
            background-color: #28a745; 
        }
        .action-btn.approve:hover {
            background-color: #218838;
        }

        .action-btn.reject {
            background-color: #dc3545; 
        }
        .action-btn.reject:hover {
            background-color: #c82333;
        }

        .action-btn.cancel {
            background-color: #6c757d; 
        }
        .action-btn.cancel:hover {
            background-color: #5a6268;
        }


        
        #addRoomFormContainer {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            max-width: 700px;
            margin: 0 auto 2rem auto;
        }

        #addRoomFormContainer h2 {
            text-align: center;
            color: #e53935;
            margin-bottom: 1.5rem;
        }

        #addRoomFormContainer form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        #addRoomFormContainer form .form-group {
            display: flex;
            flex-direction: column;
        }

        #addRoomFormContainer form label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }

        #addRoomFormContainer form input,
        #addRoomFormContainer form textarea,
        #addRoomFormContainer form select {
            padding: 12px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 6px;
            width: 100%;
        }

        #addRoomFormContainer form textarea {
            resize: vertical;
        }

        #addRoomFormContainer form .full-width {
            grid-column: 1 / -1;
        }

        #addRoomFormContainer form .button-group {
            grid-column: 1 / -1;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 1rem;
        }

        #addRoomFormContainer form button {
            padding: 10px 20px;
            font-size: 1em;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        #addRoomFormContainer form button[type="submit"] {
            background-color: #28a745;
            color: white;
            border: none;
        }

        #addRoomFormContainer form button[type="submit"]:hover {
            background-color: #218838;
        }

        #addRoomFormContainer form button[type="button"] {
            background-color: #6c757d;
            color: white;
            border: none;
        }

        #addRoomFormContainer form button[type="button"]:hover {
            background-color: #5a6268;
        }

        /* Messages */
        .msg {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 1em;
            font-weight: 500;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .msg.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .msg.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .msg.info { 
            background-color: #e2e3e5;
            color: #383d41;
            border: 1px solid #d6d8db;
        }

        /* Footer  */
        .main-content-footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 1rem 2rem;
            margin-top: auto;
            box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
            font-size: 0.9em;
            border-radius: 0 0 10px 10px;
        }

        .main-content-footer p {
            margin: 0;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                padding: 1rem;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            }
            .sidebar h2 {
                margin-bottom: 0;
            }
            .sidebar ul {
                display: none; 
            }
            .sidebar .logout-btn-sidebar {
                margin-top: 0;
            }
            .main-content {
                padding: 1rem;
            }
            .overview-grid {
                grid-template-columns: 1fr;
            }
            #addRoomFormContainer form {
                grid-template-columns: 1fr;
            }
            th, td {
                padding: 10px 8px; 
                font-size: 0.8em;
            }
            .action-btn {
                padding: 6px 10px;
                font-size: 0.7em;
                margin-right: 2px;
            }
            table {
                font-size: 0.9em; 
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <h2>Roomfinder</h2>
        <ul>
            <li onclick="showSection('overview'); loadDashboardStats();">Overview</li>
            <li onclick="showSection('manageRooms'); loadRooms();">Manage Rooms</li>
            <li onclick="showSection('addRoomForm')">Add Room</li>
            <li onclick="showSection('bookings'); loadBookings();">Bookings</li>
        </ul>
        <a href="logout.php" class="logout-btn-sidebar">Logout</a>
    </aside>

    <div class="main-content">
        <div class="main-content-header">
            <h1>Welcome, <?php echo $username; ?>!</h1>
        </div>

        <?php
           
            if (isset($_GET['message'])) {
                $msg_type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'info';
                echo '<div class="msg ' . $msg_type . '">' . htmlspecialchars($_GET['message']) . '</div>';
            }
        ?>

        <div id="overviewSection" class="content-section">
            <h2>Dashboard Overview</h2>
            <div class="overview-grid">
                <div class="overview-card">
                    <h3>Total Rooms</h3>
                    <p id="totalRooms">0</p>
                </div>
                <div class="overview-card">
                    <h3>Available Rooms</h3>
                    <p id="availableRooms">0</p>
                </div>
                <div class="overview-card">
                    <h3>Pending Bookings</h3>
                    <p id="pendingBookings">0</p>
                </div>
                <div class="overview-card">
                    <h3>Approved Bookings</h3>
                    <p id="approvedBookings">0</p>
                </div>
            </div>
        </div>

        <div id="manageRoomsSection" class="content-section" style="display:none;">
            <h2>Manage Your Rooms</h2>
            <table id="roomTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Room Type</th>
                        <th>Floor</th>
                        <th>Rent (Rs.)</th>
                        <th>Capacity</th>
                        <th>Location</th>
                        <th>Description</th>
                        <th>Image</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="roomCards">
                    </tbody>
            </table>
            <p id="noRoomsMessage" style="text-align: center; margin-top: 20px; display: none;">No rooms found. Add a new room to get started!</p>
        </div>

        <div id="addRoomFormSection" class="content-section" style="display:none;">
            <div id="addRoomFormContainer">
                <h2>Add New Room</h2>
                <form id="addRoomForm" action="add_room.php" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="type">Room Type:</label>
                        <input type="text" name="type" id="type" required>
                    </div>

                    <div class="form-group">
                        <label for="floor">Floor:</label>
                        <input type="number" name="floor" id="floor" min="0" required>
                    </div>

                    <div class="form-group">
                        <label for="rent">Rent (Rs.):</label>
                        <input type="number" name="rent" id="rent" min="0" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label for="capacity">Capacity (persons):</label>
                        <input type="number" name="capacity" id="capacity" min="1" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="location">Location:</label>
                        <input type="text" name="location" id="location" required>
                    </div>

                    <div class="form-group full-width">
                        <label for="description">Description:</label>
                        <textarea name="description" id="description" rows="4" required></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label for="image">Image:</label>
                        <input type="file" name="image" id="image" accept="image/*">
                    </div>

                    <div class="form-group full-width">
                        <label for="status">Status:</label>
                        <select name="status" id="status" required>
                            <option value="Available">Available</option>
                            <option value="Occupied">Occupied</option>
                            <option value="Maintenance">Maintenance</option>
                        </select>
                    </div>

                    <div class="button-group">
                        <button type="submit">Add Room</button>
                        <button type="button" onclick="showSection('manageRooms'); loadRooms();">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div id="bookingsSection" class="content-section" style="display:none;">
            <h2>Manage Bookings</h2>
            <table id="bookingsTable">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Room ID</th>
                        <th>Room Type</th>
                        <th>Booked By</th>
                     <th>Booking Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="bookingRequests">
                    </tbody>
            </table>
            <p id="noBookingsMessage" style="text-align: center; margin-top: 20px; display: none;">No bookings found.</p>
        </div>

        <div class="main-content-footer">
            <p>&copy; <?php echo date("Y"); ?> Roomfinder. All rights reserved.</p>
        </div>
    </div>

    <script>
        function showSection(sectionId) {
            document.querySelectorAll('.content-section').forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById(sectionId + 'Section').style.display = 'block';
        }

        // to load dashboard statistics
        function loadDashboardStats() {
            fetch("get_dashboard_stats.php")
                .then(res => res.json())
                .then(stats => {
                    document.getElementById("totalRooms").innerText = stats.totalRooms;
                    document.getElementById("availableRooms").innerText = stats.availableRooms;
                    document.getElementById("pendingBookings").innerText = stats.pendingBookings;
                    document.getElementById("approvedBookings").innerText = stats.approvedBookings;
                })
                .catch(err => {
                    console.error("Error loading dashboard stats:", err);
                    document.getElementById("totalRooms").innerText = "N/A";
                    document.getElementById("availableRooms").innerText = "N/A";
                    document.getElementById("pendingBookings").innerText = "N/A";
                    document.getElementById("approvedBookings").innerText = "N/A";
                });
        }

        // to load room listings
        function loadRooms() {
            fetch("getrooms.php")
                .then(res => res.json())
                .then(rooms => {
                    const container = document.getElementById("roomCards");
                    container.innerHTML = ""; 
                    if (rooms.length === 0) {
                        document.getElementById("noRoomsMessage").style.display = 'block';
                        document.getElementById("roomTable").style.display = 'none';
                    } else {
                        document.getElementById("noRoomsMessage").style.display = 'none';
                        document.getElementById("roomTable").style.display = 'table';
                        rooms.forEach(room => {
                            const row = document.createElement("tr");
                            row.innerHTML = `
                                <td>${room.id}</td>
                                <td>${room.type}</td>
                                <td>${room.floor}</td>
                                <td>Rs. ${room.rent}</td>
                                <td>${room.capacity} person(s)</td>
                                <td>${room.location}</td>
                                <td>${room.description}</td>
                                <td>
                                    ${room.image_path ? `<img src="${room.image_path}" alt="Room Image" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;">` : 'No Image'}
                                </td>
                                <td><span class="status ${room.status.toLowerCase()}">${room.status}</span></td>
                                <td>
                                    <button class="action-btn" onclick="location.href='edit_room.php?id=${room.id}'">Edit</button>
                                    <button class="action-btn reject" onclick="deleteRoom(${room.id})">Delete</button>
                                </td>
                            `;
                            container.appendChild(row);
                        });
                    }
                })
                .catch(err => {
                    const container = document.getElementById("roomCards");
                    container.innerHTML = "<tr><td colspan='10'>Error loading rooms.</td></tr>"; // Adjusted colspan
                    document.getElementById("noRoomsMessage").style.display = 'none';
                    document.getElementById("roomTable").style.display = 'table';
                    console.error(err);
                });
        }

        //to delete a room
        function deleteRoom(roomId) {
            if (confirm("Are you sure you want to delete this room? This action cannot be undone.")) {
                fetch('delete_room.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + roomId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        loadRooms(); 
                        loadDashboardStats(); 
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while deleting the room.');
                });
            }
        }

        
        function loadBookings() {
            fetch("get_bookings.php")
                .then(res => res.json())
                .then(bookings => {
                    const container = document.getElementById("bookingRequests");
                    container.innerHTML = ""; 
                    if (bookings.length === 0) {
                        document.getElementById("noBookingsMessage").style.display = 'block';
                        document.getElementById("bookingsTable").style.display = 'none';
                    } else {
                        document.getElementById("noBookingsMessage").style.display = 'none';
                        document.getElementById("bookingsTable").style.display = 'table';
                        bookings.forEach(booking => {
                            const row = document.createElement("tr");
                            let actionsHtml = '';
                            if (booking.status === 'Pending') {
                                actionsHtml = `
                                   <button onclick="updateBookingStatus(${booking.id}, 'Approved')">Approve</button>
                                <button onclick="updateBookingStatus(${booking.id}, 'Cancelled')">Cancel</button>

                                `;
                            } else if (booking.status === 'Approved') {
                                actionsHtml = `
                                    <button class="action-btn cancel" onclick="updateBookingStatus(${booking.id}, 'Cancelled')">Cancel</button>
                                `;
                            }
                            // No actions for Rejected or Cancelled bookings

                            row.innerHTML = `
                                <td>${booking.id}</td>
                                <td>${booking.room_id}</td>
                                <td>${booking.room_type}</td>
                                <td>${booking.seeker_email}</td>
                                <td>${booking.booking_date}</td>
                                <td><span class="status ${booking.status.toLowerCase()}">${booking.status}</span></td>
                                <td>${actionsHtml}</td>
                            `;
                            container.appendChild(row);
                        });
                    }
                })
                .catch(err => {
                    const container = document.getElementById("bookingRequests");
                    container.innerHTML = "<tr><td colspan='9'>Error loading bookings.</td></tr>";
                    document.getElementById("noBookingsMessage").style.display = 'none';
                    document.getElementById("bookingsTable").style.display = 'table';
                    console.error(err);
                });
        }

        // Function to update booking status
        function updateBookingStatus(bookingId, newStatus) {
            if (confirm(`Are you sure you want to change the status of booking ${bookingId} to ${newStatus}?`)) {
                fetch('update_booking_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `booking_id=${bookingId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        loadBookings(); // Reload bookings after update
                        loadDashboardStats(); // Update stats
                    } else {
                        alert("Error: " + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the booking status.');
                });
            }
        }

        // show section based on URL message,
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const message = urlParams.get('message');
            const type = urlParams.get('type');

            if (message) {
               
                showSection('manageRooms');
                loadRooms();
            } else {
                
                showSection('overview');
            }
            
            loadDashboardStats();
        };
    </script>
</body>
</html>
