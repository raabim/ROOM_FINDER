<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seeker') {
    header("Location: login.html?message=" . urlencode("Access denied. Please log in as a seeker."));
    exit();
}

$username = htmlspecialchars($_SESSION['username'] ?? $_SESSION['email']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Seeker Dashboard</title>
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

        
        :root {
            --primary-color: #e63946;
            --hover-color: #c92c3b;
            --bg-color: #f8f9fa;
            --text-color: #333;
            --card-bg: #ffffff;
            --shadow-light: rgba(0, 0, 0, 0.1);
            --shadow-medium: rgba(0, 0, 0, 0.08);
        }

        .sidebar {
            width: 240px;
            background-color: #dc3545;
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
            background-color: #e65c6a;
            border-radius: 8px;
            cursor: pointer;
            user-select: none;
            transition: background-color 0.3s ease;
            font-weight: 500;
        }

        .sidebar ul li:hover {
            background-color: #c82333;
        }

        .sidebar .logout-btn-sidebar {
            margin-top: auto;
            text-align: center;
            padding: 0.8rem 1rem;
            background-color: #b02a37;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        .sidebar .logout-btn-sidebar:hover {
            background-color: #9f242e;
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
            color: #dc3545;
            font-weight: 700;
        }


        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-top: 1.5rem;
            padding: 1.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }


        .room-card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 12px var(--shadow-medium);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            border: 1px solid #eee;
            cursor: pointer;
        }

        .room-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }


        .room-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #e0e0e0;
            border-bottom: 1px solid #f0f0f0;
        }


        .room-content {
            padding: 1.5rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }


        .room-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--primary-color);
        }


        .room-location, .room-icons {
            font-size: 0.95rem;
            color: #555;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }

        .room-location span, .room-icons span {
            margin-right: 0.5rem;
        }


        .room-description {
            font-size: 0.95rem;
            color: #666;
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }


        .room-rent {
            font-size: 1.8rem;
            font-weight: 700;
            color: #222;
            margin-top: auto;
            padding-top: 1rem;
            border-top: 1px solid #f0f0f0;
            margin-bottom: 1rem;
        }

        .room-rent span {
            font-size: 1rem;
            font-weight: 500;
            color: #777;
        }


        .book-btn {
            background: var(--primary-color);
            color: #fff;
            padding: 12px 20px; /* Adjusted padding */
            border: none;
            border-radius: 8px; /* Slightly larger border-radius */
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease; /* Smoother transition */
            width: 100%; /* Make button full width */
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .book-btn:hover {
            background: var(--hover-color); /* Use variable */
            transform: translateY(-2px);
        }

        
        .room-card-footer {
            display: none; /* Or remove this whole block if you delete the div from HTML */
        }
        .status {
            display: none; /* Or remove this whole block if you delete the span from HTML */
        }

        .table-container {
            overflow-x: auto;
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            min-width: 700px;
        }

        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        th {
            background-color: #fdd;
            color: #dc3545;
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
        .pending { background: #ffe0b2; color: #ff6f00; }
        .approved { background: #e0f7ea; color: #2e7d32; }
        .rejected { background: #ffeaea; color: #c62828; }
        .cancelled { background: #f0f0f0; color: #616161; }
        .verified { background: #c8e6c9; color: #388e3c; }
        .not-verified { background: #ffcdd2; color: #d32f2f; }


        .action-btn {
            background: #dc3545;
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
            background: #c82333;
        }

        .action-btn.cancel {
            background-color: #6c757d;
        }
        .action-btn.cancel:hover {
            background-color: #5a6268;
        }


        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px;
            border: 1px solid #888;
            border-radius: 10px;
            width: 80%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
        }

        .close-button {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            position: absolute;
            top: 10px;
            right: 20px;
        }

        .close-button:hover,
        .close-button:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-content h2 {
            text-align: center;
            color: #dc3545;
            margin-bottom: 20px;
        }

        .modal-content .form-group {
            margin-bottom: 15px;
        }

        .modal-content label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }

        .modal-content input[type="date"],
        .modal-content input[type="text"],
        .modal-content input[type="number"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .modal-content .button-group {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-content button {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        .modal-content button[type="submit"] {
            background-color: #28a745;
            color: white;
        }

        .modal-content button[type="submit"]:hover {
            background-color: #218838;
        }

        .modal-content button[type="button"] {
            background-color: #6c757d;
        }

        .modal-content button[type="button"]:hover {
            background-color: #5a6268;
        }


        #customAlertModal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            padding-top: 150px;
        }

        #customAlertContent {
            background-color: #fefefe;
            margin: auto;
            padding: 20px;
            border: 1px solid #888;
            border-radius: 8px;
            width: 80%;
            max-width: 350px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            position: relative;
        }

        #customAlertContent h3 {
            margin-bottom: 15px;
            color: #333;
        }

        #customAlertContent p {
            margin-bottom: 20px;
            font-size: 1.1em;
        }

        #customAlertContent .alert-ok-button {
            background-color: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            transition: background-color 0.3s ease;
        }

        #customAlertContent .alert-ok-button:hover {
            background-color: #c82333;
        }


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
            /* --- Search bar styling --- */
.search-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 16px 0;
    flex-wrap: wrap;
}

/* Input box */
.search-bar #searchInput {
    flex: 1;
    min-width: 260px;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
    background: #fff;
    outline: none;
    transition: border-color 0.3s ease, box-shadow 0.2s ease;
}

.search-bar #searchInput:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 3px rgba(230, 57, 70, 0.12);
}

/* Search button */
.search-bar button {
    background: var(--primary-color);
    color: #fff;
    border: none;
    padding: 10px 18px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: background-color 0.3s ease, transform 0.2s ease;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
}

.search-bar button:hover {
    background: var(--hover-color);
    transform: translateY(-2px);
}

.search-bar button:active {
    transform: translateY(0);
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
            .room-grid {
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
            .modal-content {
                width: 95%;
                margin: 20px auto;
            }
        }
        
    </style>
</head>
<body>
    <aside class="sidebar">
        <h2>Roomfinder</h2>
        <ul>
            <li onclick="showSection('browseRooms'); loadAllRooms();">Browse Rooms</li>
            <li onclick="showSection('myBookings'); loadMyBookings();">My Bookings</li>
            <li onclick="showSection('searchResults');">Search</li>
        </ul>
        <a href="logout.php" class="logout-btn-sidebar">Logout</a>
    </aside>

    <div class="main-content">
        <div class="main-content-header">
            <h1>Hello, <?php echo $username; ?>!</h1>
        </div>

        <?php
            if (isset($_GET['message'])) {
                $msg_type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'info';
                echo '<div class="msg ' . $msg_type . '">' . htmlspecialchars($_GET['message']) . '</div>';
            }
        ?>

        <div id="browseRoomsSection" class="content-section" style="display:block;">
            <h2>Available Rooms for You</h2>
            <div id="roomsGrid" class="room-grid">
                
            </div>
            <p id="noRoomsAvailableMessage" style="text-align: center; margin-top: 20px; display: none;">No rooms are currently available or verified.</p>
        </div>
            <div id="searchResultsSection" class="content-section" style="display:none;">
                <h2>Search Rooms</h2>
                <div class="search-bar" style="margin-bottom: 1rem;">
                    <input type="text" id="searchInput" placeholder="Search by type, location, rent, or description">
                    <button onclick="searchRooms()">Search</button>
                </div>
        <div id="searchResultsGrid" class="room-grid"></div>
        <p id="noResultsMessage" style="text-align: center; margin-top: 20px; display: none;">No rooms found matching your search.</p>
        </div>

        <div id="myBookingsSection" class="content-section" style="display:none;">
            <h2>My Booking History</h2>
            <div class="table-container">
                <table id="myBookingsTable">
                    <thead>
                        <tr>
                            <th>Booking ID</th>
                            <th>Room ID</th>
                            <th>Room Type</th>
                            <th>Owner Email</th>
                            <th>Booking Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="myBookingList">
                        
                    </tbody>
                </table>
            </div>
            <p id="noMyBookingsMessage" style="text-align: center; margin-top: 20px; display: none;">You haven't made any bookings yet.</p>
        </div>
        <div class="main-content-footer">
            <p>&copy; <?php echo date("Y"); ?> Roomfinder. All rights reserved.</p>
        </div>
    </div>


    <div id="bookingModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeBookingModal()">&times;</span>
            <h2>Book Room</h2>
            <form id="bookingForm">
                <div class="form-group">
                    <label for="modalRoomId">Room ID:</label>
                    <input type="text" id="modalRoomId" name="room_id" readonly>
                </div>
                <div class="form-group">
                    <label for="modalRoomType">Room Type:</label>
                    <input type="text" id="modalRoomType" name="room_type" readonly>
                </div>
                <div class="button-group">
                    <button type="submit">Submit</button>
                    <button type="button" onclick="closeBookingModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>


    <div id="customAlertModal" class="modal">
        <div id="customAlertContent">
            <h3 id="customAlertTitle"></h3>
            <p id="customAlertMessage"></p>
            <button class="alert-ok-button" onclick="closeCustomAlert()">OK</button>
        </div>
    </div>

    <script>
        // Custom alert
        function customAlert(message, title = "Notification") {
            document.getElementById('customAlertTitle').innerText = title;
            document.getElementById('customAlertMessage').innerText = message;
            document.getElementById('customAlertModal').style.display = 'block';
        }

        function closeCustomAlert() {
            document.getElementById('customAlertModal').style.display = 'none';
        }

        window.alert = customAlert;

        // Section toggle
        function showSection(sectionId) {
            document.querySelectorAll('.content-section').forEach(section => {
                section.style.display = 'none';
            });
            document.getElementById(sectionId + 'Section').style.display = 'block';

            // Load corresponding data based on section
            if (sectionId === 'browseRooms') {
                loadAllRooms();
            } else if (sectionId === 'myBookings') {
                loadMyBookings();
            } else if (sectionId === 'searchResultsSection') {
                searchRooms(); // Ensure searchRooms is called correctly here
            }
}
            
               
        

        // Room modal
        let currentRoomId = null;
        let currentRoomType = null;
        let currentRoomRent = null;

        function openBookingModal(roomId, roomType, roomRent) {
            currentRoomId = roomId;
            currentRoomType = roomType;
            currentRoomRent = roomRent;

            document.getElementById('modalRoomId').value = roomId;
            document.getElementById('modalRoomType').value = roomType;

            document.getElementById('bookingModal').style.display = 'block';
        }

        function closeBookingModal() {
            document.getElementById('bookingModal').style.display = 'none';
            document.getElementById('bookingForm').reset();
        }

        // Load rooms
        function loadAllRooms() {
            fetch("get_all_rooms.php")
                .then(res => res.json())
                .then(rooms => {
                    const container = document.getElementById("roomsGrid");
                    container.innerHTML = "";
                    if (!rooms.length) {
                        document.getElementById("noRoomsAvailableMessage").style.display = 'block';
                    } else {
                        document.getElementById("noRoomsAvailableMessage").style.display = 'none';
                        rooms.forEach(room => {
                            const roomCard = document.createElement("div");
                            roomCard.classList.add("room-card");
                            roomCard.innerHTML = `
                                ${room.image_path ? `<img src="uploads/${room.image_path}" class="room-image">` : '<img src="placeholder.jpg" class="room-image">'}
                                <div class="room-content">
                                    <h3 class="room-title">${room.type}</h3>
                                    <p class="room-location">Location: ${room.location}</p>
                                    <p class="room-icons">Floor: ${room.floor} | Capacity: ${room.capacity}</p>
                                    <p class="room-description">${room.description}</p>
                                    <div class="room-rent">Rs. ${room.rent}<span>/month</span></div>
                                    <button class="book-btn" onclick="event.stopPropagation(); openBookingModal(${room.id}, '${room.type}', ${room.rent})">Book Now</button>
                                </div>`;
                            container.appendChild(roomCard);
                        });
                    }
                })
                .catch(err => {
                    document.getElementById("roomsGrid").innerHTML = "<p style='text-align:center;color:red;'>Error loading rooms.</p>";
                    console.error(err);
                });
        }

        // Load my bookings
        function loadMyBookings() {
            fetch("get_my_bookings.php")
                .then(res => res.json())
                .then(bookings => {
                    const container = document.getElementById("myBookingList");
                    container.innerHTML = "";
                    if (!bookings.length) {
                        document.getElementById("noMyBookingsMessage").style.display = 'block';
                        document.getElementById("myBookingsTable").style.display = 'none';
                    } else {
                        document.getElementById("noMyBookingsMessage").style.display = 'none';
                        document.getElementById("myBookingsTable").style.display = 'table';
                        bookings.forEach(booking => {
                            const row = document.createElement("tr");
                            let actionsHtml = '';
                            if (booking.status === 'Pending' || booking.status === 'Approved') {
                                actionsHtml += `<form method="POST" action="initiate_payment.php">
                                            <input type="hidden" name="booking_id" value="${booking.id}">
                                            <input type="hidden" name="redirect" value="1">
                                            <button type="submit" class="action-btn pay">Pay</button>
                                     </form>`;

                            }
                            row.innerHTML = `
                                <td>${booking.id}</td>
                                <td>${booking.room_id}</td>
                                <td>${booking.room_type}</td>
                                <td>${booking.owner_email}</td>
                                <td>${booking.booking_date}</td>
                                <td><span class="status ${booking.status.toLowerCase()}">${booking.status}</span></td>
                                <td>${actionsHtml}</td>`;
                            container.appendChild(row);
                        });
                    }
                })
                .catch(err => {
                    document.getElementById("myBookingList").innerHTML = "<tr><td colspan='7'>Error loading bookings.</td></tr>";
                    console.error(err);
                });
        }
       
        // Simple linear search filtering
        function searchRooms() {
            const term = document.getElementById('searchInput').value.toLowerCase().trim();
            const grid = document.getElementById("searchResultsGrid");
            const noMsg = document.getElementById("noResultsMessage");

                if (!term) {
                    grid.innerHTML = "";
                    noMsg.style.display = 'block';
                    return;
                }

                fetch("get_all_rooms.php")
                    .then(res => res.json())
                    .then(rooms => {
                        const results = rooms.filter(r =>
                            (r.type && r.type.toLowerCase().includes(term)) ||
                            (r.location && r.location.toLowerCase().includes(term)) ||
                            (r.description && r.description.toLowerCase().includes(term)) ||
                            (r.capacity && r.capacity.toString().includes(term)) ||
                            (r.rent && r.rent.toString().includes(term))
                        );

                        displaySearchResults(results);
                    })
                    .catch(err => {
                        grid.innerHTML = "<p style='text-align:center;color:red;'>Error loading rooms.</p>";
                        console.error(err);
                    });
            }

            function displaySearchResults(rooms) {
                const container = document.getElementById("searchResultsGrid");
                const noMsg = document.getElementById("noResultsMessage");
                container.innerHTML = "";

                if (!rooms.length) {
                    noMsg.style.display = 'block';
                    return;
                }

                noMsg.style.display = 'none';

                rooms.forEach(room => {
                    const card = document.createElement("div");
                    card.classList.add("room-card");
                    const imgSrc = room.image_path ? `uploads/${room.image_path}` : 'placeholder.jpg';

                    card.innerHTML = `
                        <img src="${imgSrc}" class="room-image" alt="Room image">
                        <div class="room-content">
                            <h3 class="room-title">${room.type}</h3>
                            <p class="room-location">Location: ${room.location}</p>
                            <p class="room-icons">Floor: ${room.floor} | Capacity: ${room.capacity}</p>
                            <p class="room-description">${room.description}</p>
                            <div class="room-rent">Rs. ${room.rent}<span>/month</span></div>
                            <button class="book-btn" onclick="event.stopPropagation(); openBookingModal(${room.id}, '${room.type}', ${room.rent})">Book Now</button>
                        </div>`;
                    container.appendChild(card);
                });
            }

        // Cancel booking
        function cancelBooking(bookingId) {
            document.getElementById('customAlertTitle').innerText = "Confirm Cancellation";
            document.getElementById('customAlertMessage').innerText = `Are you sure you want to cancel booking ${bookingId}?`;
            const okButton = document.getElementById('customAlertContent').querySelector('.alert-ok-button');
            const originalOnClick = okButton.onclick;

            okButton.onclick = () => {
                closeCustomAlert();
                fetch('cancel_booking.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `booking_id=${bookingId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        customAlert(data.message, "Cancellation Successful");
                        loadMyBookings();
                        loadAllRooms();
                    } else {
                        customAlert("Error: " + data.message, "Cancellation Failed");
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    customAlert('An error occurred while canceling the booking.', "Cancellation Error");
                });
                okButton.onclick = originalOnClick;
            };
            document.getElementById('customAlertModal').style.display = 'block';
        }

        // Handle booking form submission
        document.getElementById('bookingForm').addEventListener('submit', function(event) {
            event.preventDefault();
            const roomId = document.getElementById('modalRoomId').value;

            fetch('book_room.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `room_id=${roomId}`
            })
            .then(response => response.json())
            .then(data => {
                closeBookingModal();
                if (data.success) {
                    customAlert(data.message, "Booking Successful");
                    // Reload both sections to reflect the changes
                    loadAllRooms();
                    loadMyBookings();
                } else {
                    customAlert("Error: " + data.message, "Booking Failed");
                }
            })
            .catch(error => {
                console.error('Error:', error);
                closeBookingModal();
                customAlert('An error occurred while processing your booking.', "Booking Error");
            });
        });

        // On page load
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            const message = urlParams.get('message');
            if (message) showSection('myBookings');
            else showSection('browseRooms');
        };
        


    </script>
</body>
</html>