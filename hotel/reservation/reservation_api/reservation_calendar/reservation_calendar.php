<?php include '../../../db_connect.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Reservation Calendar</title>
	<link rel="stylesheet" href="../../reservation_css/base.css">
	<link rel="stylesheet" href="../../reservation_css/back_button.css">
	<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
	<style>
		.calendar-btn {
			position: fixed;
			bottom: 80px;
			right: 20px;
			width: 40px;
			height: 40px;
			background-color: #111111;
			border-radius: 50%;
			box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
			display: flex;
			justify-content: center;
			align-items: center;
			cursor: pointer;
			z-index: 1001;
		}
		.calendar-btn img {
			width: 24px;
			height: 24px;
			opacity: 0.8;
			filter: invert(1);
		}
		.calendar-table {
			border-collapse: collapse;
			width: 100%;
			margin-top: 20px;
		}
		.calendar-table th, .calendar-table td {
			border: 1px solid #ccc;
			width: 14.2%;
			height: 60px;
			text-align: center;
			vertical-align: top;
			position: relative;
		}
		.circle {
			border-radius: 50%;
			width: 32px;
			height: 32px;
			display: inline-block;
			line-height: 32px;
			color: #fff;
			font-weight: bold;
		}
		.circle.green { background: #4caf50; }
		.circle.red { background: #e53935; }
		.circle.yellow { background: #ffd600; color: #222; }
		.circle.orange { background: orange; }
		.circle.selected { border: 2px solid #222; }
	</style>
</head>
<body>
<div class="container">
	<h1>Reservation Calendar</h1>
	<div class="calendar-search-controls">
		<input type="text" id="searchInput" placeholder="Search room or guest...">
		<select id="searchType">
			<option value="room">Room</option>
			<option value="guest">Guest</option>
		</select>
		<select id="statusFilter">
			<option value="">All Statuses</option>
			<option value="reserved">Reserved</option>
			<option value="occupied">Occupied</option>
			<option value="maintenance">Maintenance</option>
			<option value="checked_in">Checked In</option>
			<option value="checked_out">Checked Out</option>
			<option value="extend_stay">Extend Stay</option>
		</select>
		<button class="dashboard-item search-btn" onclick="searchEntity()">Search</button>
		<select id="entitySelect" style="display:none;"></select>
	</div>
	<div id="calendar-controls" style="display:flex;align-items:center;justify-content:center;margin-bottom:10px;gap:10px;">
		   <button id="prevMonth" class="search-btn" style="background:black;width:auto;padding:3px 8px;display:flex;align-items:center;justify-content:center;">
			   <img src="../../reservation_img/prev_icon.png" alt="Prev" style="width:11px;height:11px;">
		   </button>
		<span id="calendarMonth" style="color:#ffd700;font-weight:bold;font-size:1.2em;"></span>
		   <button id="nextMonth" class="search-btn" style="background:black;width:auto;padding:3px 8px;display:flex;align-items:center;justify-content:center;">
			   <img src="../../reservation_img/next_icon.png" alt="Next" style="width:11px;height:11px;">
		   </button>
	</div>
	<div id="calendar"></div>
</div>
<a href="../../reservation.php" class="back-button" title="Back to Dashboard">
	<img src="../../reservation_img/back_icon.png" alt="Back">
</a>
<script>

const calendarDiv = document.getElementById('calendar');
const calendarMonthSpan = document.getElementById('calendarMonth');
const prevMonthBtn = document.getElementById('prevMonth');
const nextMonthBtn = document.getElementById('nextMonth');
let selectedType = 'room';
let selectedId = null;
let currentYear, currentMonth;

function renderCalendar(year, month, marks) {
	const date = new Date(year, month, 1);
	const daysInMonth = new Date(year, month+1, 0).getDate();
	let html = '<table class="calendar-table"><tr>';
	const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
	for (let d of days) html += `<th>${d}</th>`;
	html += '</tr><tr>';
	for (let i=0; i<date.getDay(); i++) html += '<td></td>';
	for (let day=1; day<=daysInMonth; day++) {
		const d = new Date(year, month, day);
		let mark = marks[d.toISOString().slice(0,10)];
		let circle;
		if (mark) {
			circle = `<span class="circle ${mark.color}">${day}</span>`;
		} else {
			circle = `<span style='color:#ffd700;font-weight:bold;'>${day}</span>`;
		}
		html += `<td>${circle}</td>`;
		if ((d.getDay()+1)%7===0) html += '</tr><tr>';
	}
	html += '</tr></table>';
	calendarDiv.innerHTML = html;
	// Set month label
	const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
	calendarMonthSpan.textContent = `${monthNames[month]} ${year}`;
}

function getStatusColor(status, type) {
	if (type==='room') {
		if (status==='reserved') return 'green';
		if (status==='occupied') return 'red';
		if (status==='maintenance') return 'yellow';
	} else {
		if (status==='reserved') return 'yellow';
		if (status==='checked_in') return 'green';
		if (status==='checked_out') return 'red';
		if (status==='extend_stay') return 'orange';
	}
	return '';
}

function fetchAndRenderCalendar(year, month) {
	if (!selectedId) {
		// Show empty calendar for current month
		renderCalendar(year, month, {});
		return;
	}
	fetch(`calendar_api.php?type=${selectedType}&id=${selectedId}`)
		.then(r=>r.json())
		.then(data => {
			let marks = {};
			data.forEach(b => {
				let start = new Date(b.check_in);
				let end = new Date(b.check_out);
				let color = getStatusColor(b.status, selectedType);
				for (let d = new Date(start); d <= end; d.setDate(d.getDate()+1)) {
					let key = d.toISOString().slice(0,10);
					marks[key] = {color};
				}
			});
			renderCalendar(year, month, marks);
		});
}

function searchEntity() {
	const type = document.getElementById('searchType').value;
	const query = document.getElementById('searchInput').value;
	const status = document.getElementById('statusFilter').value;
	fetch(`search_api.php?type=${type}&query=${encodeURIComponent(query)}&status=${encodeURIComponent(status)}`)
		.then(r=>r.json())
		.then(data => {
			const select = document.getElementById('entitySelect');
			select.innerHTML = '';
			if (data.length) {
				select.style.display = '';
				data.forEach(e => {
					let text = type==='room' ? `Room #${e.room_number}` : `${e.first_name} ${e.last_name}`;
					let val = type==='room' ? e.room_id : e.guest_id;
					let opt = document.createElement('option');
					opt.value = val;
					opt.textContent = text;
					select.appendChild(opt);
				});
				select.onchange = function() {
					selectedType = type;
					selectedId = this.value;
					fetchAndRenderCalendar(currentYear, currentMonth);
				};
				// auto-select first
				select.selectedIndex = 0;
				select.onchange();
			} else {
				select.style.display = 'none';
				calendarDiv.innerHTML = '<p>No results found.</p>';
			}
		});
}
// Calendar navigation
function changeMonth(delta) {
	currentMonth += delta;
	if (currentMonth < 0) {
		currentMonth = 11;
		currentYear--;
	} else if (currentMonth > 11) {
		currentMonth = 0;
		currentYear++;
	}
	fetchAndRenderCalendar(currentYear, currentMonth);
}

prevMonthBtn.addEventListener('click', function() { changeMonth(-1); });
nextMonthBtn.addEventListener('click', function() { changeMonth(1); });

// Initialize calendar to current month
const today = new Date();
currentYear = today.getFullYear();
currentMonth = today.getMonth();
fetchAndRenderCalendar(currentYear, currentMonth);
</script>
</body>
</html>
