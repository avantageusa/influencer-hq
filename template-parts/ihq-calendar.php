<?php
/**
 * IHQ Challenge Calendar Component
 *
 * Usage via function (recommended):
 *   ihq_calendar( [ '2026-4' => [2,3,15], '2026-5' => [10,20] ] );
 *
 * Usage via get_template_part (WP 5.5+):
 *   get_template_part( 'template-parts/ihq-calendar', null, [
 *       'occupied' => [ '2026-4' => [2,3,15] ],
 *   ]);
 *
 * occupied array format:
 *   [ 'YYYY-M' => [ day, day, ... ], ... ]
 *   Month is 1-based with no leading zero. e.g. '2026-4' = April 2026.
 */

$occupied     = $args['occupied'] ?? [];
$occupied_json = wp_json_encode( $occupied );

// Unique ID so multiple instances on the same page don't clash
static $ihq_cal_instance = 0;
$ihq_cal_instance++;
$uid = 'ihqCal' . $ihq_cal_instance;
?>

<style>
    /* ── IHQ Calendar Component ──────────────────────────── */
    .cal-card {
        background: #000;
        border: 1px solid #b8972f;
        border-radius: 8px;
        overflow: hidden;
    }

    .cal-card-header {
        padding: 16px 20px 0;
    }

    .cal-card-label {
        font-size: .82rem;
        color: rgba(255,255,255,.55);
        letter-spacing: .04em;
        margin-bottom: 10px;
    }

    .cal-tabs {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding-bottom: 10px;
        border-bottom: 1px solid rgba(255,255,255,.1);
    }

    .cal-tabs-left {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: .95rem;
        color: rgba(255,255,255,.55);
    }

    .cal-tab-link {
        background: none;
        border: none;
        cursor: pointer;
        font-size: .95rem;
        padding: 2px 4px;
        color: rgba(255,255,255,.55);
        text-decoration: none;
        transition: color .2s;
    }
    .cal-tab-link:hover { color: #fff; }
    .cal-tab-link.active {
        color: #fff;
        text-decoration: underline;
        text-underline-offset: 3px;
    }

    .cal-tab-sep {
        color: rgba(255,255,255,.3);
        font-size: .85rem;
    }

    .cal-filter-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        color: rgba(255,255,255,.7);
        display: flex;
        align-items: center;
        transition: color .2s;
    }
    .cal-filter-btn:hover { color: #fff; }
    .cal-filter-btn svg {
        width: 20px;
        height: 20px;
        fill: none;
        stroke: currentColor;
        stroke-width: 1.8;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .cal-month-nav {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        padding: 10px 20px 4px;
    }
    .cal-month-nav button {
        background: none;
        border: none;
        color: rgba(255,255,255,.6);
        font-size: 1.2rem;
        cursor: pointer;
        padding: 2px 8px;
        transition: color .2s;
    }
    .cal-month-nav button:hover { color: #fff; }

    .cal-month-label {
        font-size: .95rem;
        font-weight: 600;
        letter-spacing: .08em;
        color: #fff;
        min-width: 160px;
        text-align: center;
    }

    .cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
    }

    .cal-dow {
        text-align: center;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .08em;
        color: #fff;
        padding: 10px 0 8px;
        border-top: 1px solid rgba(255,255,255,.12);
        border-right: 1px solid rgba(255,255,255,.08);
        background: rgba(255,255,255,.04);
    }
    .cal-dow:last-child { border-right: none; }

    .cal-day {
        min-height: 72px;
        border-top: 1px solid rgba(255,255,255,.08);
        border-right: 1px solid rgba(255,255,255,.08);
        padding: 6px 8px 0;
        position: relative;
        box-sizing: border-box;
    }
    .cal-day:nth-child(7n) { border-right: none; }

    .cal-day-num {
        font-size: .88rem;
        color: rgba(255,255,255,.75);
        line-height: 1;
    }

    .cal-day.today .cal-day-num {
        color: #fff;
        font-weight: 700;
    }

    .cal-day.empty {
        background: transparent;
        pointer-events: none;
    }
    .cal-day.empty .cal-day-num { visibility: hidden; }

    /* free — no indicator */
    .cal-day.free {}

    /* occupied — gold bar at bottom */
    .cal-day.occupied::after {
        content: '';
        display: block;
        position: absolute;
        bottom: 6px;
        left: 6px;
        right: 6px;
        height: 5px;
        background: #b8972f;
        border-radius: 2px;
    }

    .cal-card-footer {
        height: 6px;
        background: #b8972f;
    }

    .cal-list-view {
        display: none;
        padding: 16px 20px;
    }

    .cal-list-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 0;
        border-bottom: 1px solid rgba(255,255,255,.08);
        font-size: .88rem;
        color: rgba(255,255,255,.8);
    }
    .cal-list-item:last-child { border-bottom: none; }

    .cal-list-dot {
        width: 10px;
        height: 10px;
        background: #b8972f;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .cal-list-date {
        font-weight: 600;
        color: #fff;
        min-width: 80px;
    }
</style>

<div class="cal-card" id="<?php echo esc_attr( $uid ); ?>">

    <div class="cal-card-header">
        <p class="cal-card-label">Upcoming Private Challenges</p>
        <div class="cal-tabs">
            <div class="cal-tabs-left">
                <button class="cal-tab-link" id="<?php echo esc_attr( $uid ); ?>TabList" onclick="ihqCalSwitchTab('<?php echo esc_js( $uid ); ?>','list')">List</button>
                <span class="cal-tab-sep">|</span>
                <button class="cal-tab-link active" id="<?php echo esc_attr( $uid ); ?>TabCalendar" onclick="ihqCalSwitchTab('<?php echo esc_js( $uid ); ?>','calendar')">Calendar</button>
            </div>
            <button class="cal-filter-btn" title="Filter">
                <svg viewBox="0 0 24 24"><path d="M3 6h18M7 12h10M11 18h2"/></svg>
            </button>
        </div>
    </div>

    <div class="cal-month-nav" id="<?php echo esc_attr( $uid ); ?>Nav">
        <button onclick="ihqCalChangeMonth('<?php echo esc_js( $uid ); ?>',-1)">&#8249;</button>
        <span class="cal-month-label" id="<?php echo esc_attr( $uid ); ?>Label"></span>
        <button onclick="ihqCalChangeMonth('<?php echo esc_js( $uid ); ?>',1)">&#8250;</button>
    </div>

    <div class="cal-grid" id="<?php echo esc_attr( $uid ); ?>Grid"></div>
    <div class="cal-list-view" id="<?php echo esc_attr( $uid ); ?>ListView"></div>

    <div class="cal-card-footer"></div>
</div>

<script>
(function () {
    var occupiedDays = <?php echo $occupied_json; ?>;
    var uid = <?php echo wp_json_encode( $uid ); ?>;

    var MONTHS = ['January','February','March','April','May','June',
                  'July','August','September','October','November','December'];
    var DAYS   = ['SUN','MON','TUE','WED','THU','FRI','SAT'];

    var today    = new Date();
    var curYear  = today.getFullYear();
    var curMonth = today.getMonth(); // 0-based

    function getOccupied(year, month) {
        var key = year + '-' + (month + 1);
        return (occupiedDays[key] || []);
    }

    function renderCalendar(year, month) {
        var grid  = document.getElementById(uid + 'Grid');
        var label = document.getElementById(uid + 'Label');
        label.textContent = MONTHS[month] + ' ' + year;

        var firstDay    = new Date(year, month, 1).getDay();
        var daysInMonth = new Date(year, month + 1, 0).getDate();
        var occupied    = getOccupied(year, month);
        var html = '';

        DAYS.forEach(function (d) {
            html += '<div class="cal-dow">' + d + '</div>';
        });

        for (var i = 0; i < firstDay; i++) {
            html += '<div class="cal-day empty"><span class="cal-day-num"></span></div>';
        }

        for (var d = 1; d <= daysInMonth; d++) {
            var isToday    = (d === today.getDate() && month === today.getMonth() && year === today.getFullYear());
            var isOccupied = occupied.indexOf(d) !== -1;
            var cls = 'cal-day ' + (isOccupied ? 'occupied' : 'free') + (isToday ? ' today' : '');
            html += '<div class="' + cls + '"><span class="cal-day-num">' + d + '</span></div>';
        }

        var remainder = (firstDay + daysInMonth) % 7;
        if (remainder !== 0) {
            for (var t = remainder; t < 7; t++) {
                html += '<div class="cal-day empty"><span class="cal-day-num"></span></div>';
            }
        }

        grid.innerHTML = html;
    }

    function renderList(year, month) {
        var listView = document.getElementById(uid + 'ListView');
        var occupied = getOccupied(year, month);
        if (!occupied.length) {
            listView.innerHTML = '<p style="color:rgba(255,255,255,.5);font-size:.85rem;padding:14px 0;">No challenges scheduled this month.</p>';
            return;
        }
        var html = '';
        occupied.slice().sort(function (a, b) { return a - b; }).forEach(function (d) {
            var date  = new Date(year, month, d);
            var lbl   = DAYS[date.getDay()] + ', ' + MONTHS[month] + ' ' + d;
            html += '<div class="cal-list-item">'
                  + '<span class="cal-list-dot"></span>'
                  + '<span class="cal-list-date">' + lbl + '</span>'
                  + '<span>Private Challenge</span>'
                  + '</div>';
        });
        listView.innerHTML = html;
    }

    function render() {
        renderCalendar(curYear, curMonth);
        renderList(curYear, curMonth);
    }

    // Exposed globally so inline onclick handlers can reach them
    window.ihqCalChangeMonth = window.ihqCalChangeMonth || function (id, delta) {
        if (id !== uid) return;
        curMonth += delta;
        if (curMonth > 11) { curMonth = 0;  curYear++; }
        if (curMonth < 0)  { curMonth = 11; curYear--; }
        render();
    };

    // Allow multiple instances
    var _origChangeMonth = window.ihqCalChangeMonth;
    window.ihqCalChangeMonth = function (id, delta) {
        if (id === uid) {
            curMonth += delta;
            if (curMonth > 11) { curMonth = 0;  curYear++; }
            if (curMonth < 0)  { curMonth = 11; curYear--; }
            render();
        } else if (_origChangeMonth && _origChangeMonth !== window.ihqCalChangeMonth) {
            _origChangeMonth(id, delta);
        }
    };

    window.ihqCalSwitchTab = window.ihqCalSwitchTab || function () {};
    var _origSwitchTab = window.ihqCalSwitchTab;
    window.ihqCalSwitchTab = function (id, tab) {
        if (id === uid) {
            var grid     = document.getElementById(uid + 'Grid');
            var listView = document.getElementById(uid + 'ListView');
            var nav      = document.getElementById(uid + 'Nav');
            var tabList  = document.getElementById(uid + 'TabList');
            var tabCal   = document.getElementById(uid + 'TabCalendar');

            if (tab === 'list') {
                grid.style.display     = 'none';
                nav.style.display      = 'none';
                listView.style.display = 'block';
                tabList.classList.add('active');
                tabCal.classList.remove('active');
            } else {
                grid.style.display     = '';
                nav.style.display      = '';
                listView.style.display = 'none';
                tabCal.classList.add('active');
                tabList.classList.remove('active');
            }
        } else if (_origSwitchTab && _origSwitchTab !== window.ihqCalSwitchTab) {
            _origSwitchTab(id, tab);
        }
    };

    render();
})();
</script>
