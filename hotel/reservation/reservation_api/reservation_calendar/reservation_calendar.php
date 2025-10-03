<?php include '../../db_connect.php'; ?>
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
/* Prev/Next Buttons */
#prevMonth, #nextMonth {
    background: #222;
    color: #ffd700;
    border: 1px solid #ffd700;
    width: 30px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}
#prevMonth:hover, #nextMonth:hover {
    background: #ffd700;
    color: #222;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.4);
}
#prevMonth:active, #nextMonth:active {
    transform: translateY(0);
    box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}

/* Calendar Table */
.calendar-table {
    border-collapse: collapse;
    width: 100%;
    margin-top: 20px;
}
.calendar-table th, .calendar-table td {
    border: 1px solid #ccc;
    width: 14.2%;
    height: 120px;
    text-align: center;
    vertical-align: top;
    position: relative;
    padding: 5px;
}
.day-number {
    font-weight: bold;
    margin-bottom: 5px;
}
.status-circle {
    display: inline-block;
    width: 20px;
    height: 20px;
    margin: 2px;
    border-radius: 50%;
    cursor: pointer;
}
.status-circle.green { background: #4caf50; }
.status-circle.red { background: #e53935; }
.status-circle.orange { background: orange; }

/* Legend */
.legend {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 15px;
}
.legend div {
    display: flex;
    align-items: center;
    gap: 5px;
}
.legend span {
    width: 15px;
    height: 15px;
    display: inline-block;
    border-radius: 3px;
}
.legend .green { background: #4caf50; }
.legend .red { background: #e53935; }
.legend .orange { background: orange; }

/* Modal */
#detailModal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.85);
}
#detailModal .modal-content {
    background: #222;
    color: #fff;
    margin: 10% auto;
    padding: 20px;
    border-radius: 10px;
    width: 90%;
    max-width: 500px;
    position: relative;
    max-height: 70%;
    overflow-y: auto;
}
#detailModal .close-btn {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}
#detailModal #modalDetails {
    white-space: pre-line;
    margin-top: 10px;
}
</style>
</head>
<body>
<div class="container">
	<h1>Reservation Calendar</h1>

	<div id="calendar-controls" class="calendar-controls">
		<button id="prevMonth">Prev</button>
		<span id="calendarMonth" style="font-weight:bold;font-size:1.2em;"></span>
		<button id="nextMonth">Next</button>
	</div>

	<div id="calendar"></div>

	<div class="legend">
		<div><span class="green"></span> Reserved</div>
		<div><span class="red"></span> Occupied</div>
		<div><span class="orange"></span> Extended Stay</div>
	</div>
</div>

<a href="../../reservation.php" class="back-button" title="Back to Dashboard">
	<img src="../../reservation_img/back_icon.png" alt="Back">
</a>

<!-- Modal -->
<div id="detailModal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <div id="modalDetails"></div>
    </div>
</div>

<script>
const calendarDiv = document.getElementById('calendar');
const calendarMonthSpan = document.getElementById('calendarMonth');
const prevMonthBtn = document.getElementById('prevMonth');
const nextMonthBtn = document.getElementById('nextMonth');
const modal = document.getElementById('detailModal');
const modalDetails = document.getElementById('modalDetails');
const closeBtn = document.querySelector('.close-btn');
let currentYear, currentMonth;

// Status color helper
function getStatusColor(status){
    if(status==='reserved') return 'green';
    if(status==='occupied' || status==='checked_in') return 'red';
    if(status==='extend_stay') return 'orange';
    return '';
}

// Render calendar
function renderCalendar(year, month, bookings){
    const date = new Date(year, month, 1);
    const daysInMonth = new Date(year, month+1, 0).getDate();
    let html = '<table class="calendar-table"><tr>';
    const days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    for(let d of days) html += `<th>${d}</th>`;
    html += '</tr><tr>';

    for(let i=0;i<date.getDay();i++) html+='<td></td>';

    for(let day=1;day<=daysInMonth;day++){
        const d = new Date(year, month, day);
        const key = d.toISOString().slice(0,10);
        const cellBookings = bookings.filter(b => key>=b.start && key<=b.end);

        let circlesHtml = '';
        if(cellBookings.length){
            const grouped = {};
            cellBookings.forEach(b=>{
                if(b.status==='checked_in') b.status='occupied';
                if(['reserved','occupied','extend_stay'].includes(b.status)){
                    if(!grouped[b.status]) grouped[b.status]=[];
                    grouped[b.status].push(b);
                }
            });
            for(const status in grouped){
                circlesHtml += `<span class="status-circle ${getStatusColor(status)}" data-tooltip='${JSON.stringify(grouped[status])}'></span>`;
            }
        }

        html += `<td><div class="day-number">${day}</div>${circlesHtml}</td>`;
        if((d.getDay()+1)%7===0) html += '</tr><tr>';
    }

    html += '</tr></table>';
    calendarDiv.innerHTML = html;

    const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
    calendarMonthSpan.textContent = `${monthNames[month]} ${year}`;

    // Circle click modal
    document.querySelectorAll('.status-circle').forEach(circle=>{
        circle.onclick = ()=>{
            const data = JSON.parse(circle.getAttribute('data-tooltip'));
            let tooltipHtml = '';
            data.forEach(b=>{
                tooltipHtml += `Room #${b.room_id}<br>Guest: ${b.guest_name}<br>Status: ${b.status}<br>Check-in: ${b.check_in}<br>Check-out: ${b.check_out}<hr>`;
            });
            modalDetails.innerHTML = tooltipHtml;
            modal.style.display = 'block';
        }
    });
}

// Fetch bookings from API
function fetchBookings(year, month){
    fetch(`calendar_api.php?year=${year}&month=${month}`)
    .then(r=>r.json())
    .then(data=>renderCalendar(year, month, data));
}

// Navigation
function changeMonth(delta){
    currentMonth += delta;
    if(currentMonth<0){currentMonth=11;currentYear--;}
    if(currentMonth>11){currentMonth=0;currentYear++;}
    fetchBookings(currentYear, currentMonth);
}

// Initialize
const today = new Date();
currentYear = today.getFullYear();
currentMonth = today.getMonth();
fetchBookings(currentYear, currentMonth);

prevMonthBtn.onclick = ()=>changeMonth(-1);
nextMonthBtn.onclick = ()=>changeMonth(1);

// Modal close
closeBtn.onclick = ()=>{ modal.style.display='none'; };
window.onclick = e=>{ if(e.target==modal) modal.style.display='none'; };
</script>

</body>
</html>
