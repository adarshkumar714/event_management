<?php
require_once __DIR__ . '/config.php';

app_head('EventHub | Event Booking System');
app_shell_start('home');
?>
<section class="surface-card hero-banner">
    <div class="hero-grid">
        <div class="hero-copy">
            <span class="eyebrow">Responsive event platform</span>
            <h1>Plan, manage, and book events through one elegant event management website.</h1>
            <p>EventHub is designed to give attendees a smooth booking journey and give organizers a clean administrative workspace. The interface uses a full-screen layout, modern spacing, and strong visual hierarchy so event data stays easy to understand on every device.</p>

            <div class="hero-stats">
                <div class="stat-chip">
                    <strong>2 Panels</strong>
                    <span>Dedicated experiences for attendees and admins</span>
                </div>
                <div class="stat-chip">
                    <strong>Live Tracking</strong>
                    <span>See capacities, bookings, and ticket status clearly</span>
                </div>
                <div class="stat-chip">
                    <strong>Full Screen</strong>
                    <span>Wide, comfortable layouts for better visibility</span>
                </div>
            </div>
        </div>

        <div class="graphic-panel">
            <p class="sidebar-group-title text-white-50">Event highlights</p>
            <div class="mini-graphic mb-3">
                <svg viewBox="0 0 360 280" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <rect x="28" y="48" width="304" height="184" rx="30" fill="rgba(255,255,255,0.14)"/>
                    <rect x="52" y="76" width="106" height="128" rx="20" fill="#F4EFE6"/>
                    <rect x="178" y="76" width="126" height="22" rx="11" fill="#F9B74A"/>
                    <rect x="178" y="112" width="102" height="14" rx="7" fill="rgba(255,255,255,0.78)"/>
                    <rect x="178" y="138" width="88" height="14" rx="7" fill="rgba(255,255,255,0.58)"/>
                    <rect x="178" y="170" width="86" height="34" rx="17" fill="#0F766E"/>
                    <path d="M94 132c14-34 39-51 76-51 17 0 32 4 44 12-16 2-28 9-37 20 18 6 29 16 35 29-15-6-30-7-43-3-12 4-22 12-30 25-7-10-16-17-28-20 8-3 15-8 20-12-10-5-22-5-37 0z" fill="#10213D"/>
                </svg>
            </div>
            <ul class="list-clean">
                <li><span>1</span><div>Users can browse event details, venue, date, price, and current availability in a structured layout.</div></li>
                <li><span>2</span><div>Admins can create new events and review booking activity from a single control center.</div></li>
                <li><span>3</span><div>The interface uses strong spacing, modern cards, and visual cues to reduce friction while navigating.</div></li>
            </ul>
        </div>
    </div>
</section>

<section id="about" class="surface-card strong p-4 p-lg-5">
    <div class="section-heading">
        <div>
            <span class="eyebrow">About EventHub</span>
            <h2 class="mt-3">A simple event management system for organizers and attendees.</h2>
        </div>
        <p class="text-secondary mb-0">Built to keep event information easy to publish, easy to discover, and easy to manage.</p>
    </div>

    <div class="about-grid mt-4">
        <article class="feature-card">
            <h3 class="h5">User Booking Experience</h3>
            <p class="mb-0">Attendees can sign up, browse upcoming events, reserve one seat per event, and review their booking history anytime.</p>
        </article>
        <article class="feature-card">
            <h3 class="h5">Admin Event Control</h3>
            <p class="mb-0">Administrators can create events, monitor attendee counts, watch revenue totals, and keep each event catalog organized.</p>
        </article>
        <article class="feature-card">
            <h3 class="h5">Clear Data Presentation</h3>
            <p class="mb-0">Event listings, booking details, and capacity numbers are presented in cleaner cards and tables for faster scanning.</p>
        </article>
        <article class="feature-card">
            <h3 class="h5">Responsive UI</h3>
            <p class="mb-0">The full-width page structure adapts to smaller screens while preserving the quick-access menu and core workflows.</p>
        </article>
    </div>
</section>

<section class="split-layout">
    <div class="surface-card p-4 p-lg-5">
        <div class="section-heading">
            <div>
                <span class="eyebrow">Quick access</span>
                <h2 class="mt-3">Choose a panel to continue.</h2>
            </div>
        </div>
        <div class="event-grid">
            <a href="/event_project/auth.php?role=user" class="event-card-link">
                <article class="event-card">
                    <div class="event-topline">
                        <h3 class="h4">User Panel</h3>
                        <span class="meta-pill">Bookings</span>
                    </div>
                    <p>For attendees who want to browse event listings, reserve seats, and review booking history after entering the panel.</p>
                    <div class="meta-stack">
                        <div class="mini-meta"><strong>Access flow</strong><span class="text-secondary">Open panel, then sign in or create account</span></div>
                        <div class="mini-meta"><strong>Inside panel</strong><span class="text-secondary">Events, bookings, and logout</span></div>
                    </div>
                </article>
            </a>

            <a href="/event_project/auth.php?role=admin" class="event-card-link">
                <article class="event-card">
                    <div class="event-topline">
                        <h3 class="h4">Admin Panel</h3>
                        <span class="meta-pill">Management</span>
                    </div>
                    <p>For administrators who want to manage their own events, review booking totals, and use dashboard actions after entering the panel.</p>
                    <div class="meta-stack">
                        <div class="mini-meta"><strong>Access flow</strong><span class="text-secondary">Open panel, then sign in or create account</span></div>
                        <div class="mini-meta"><strong>Inside panel</strong><span class="text-secondary">Dashboard, event tools, and logout</span></div>
                    </div>
                </article>
            </a>
        </div>
    </div>

    <div class="surface-card p-4 p-lg-5">
        <span class="eyebrow">System overview</span>
        <h2 class="mt-3 h3">A cleaner structure for event discovery and event control.</h2>
        <ul class="list-clean mt-4">
            <li><span>A</span><div>The front page focuses only on platform information, branding, and visual explanation instead of operational actions.</div></li>
            <li><span>B</span><div>The user journey becomes clearer: choose a panel first, then sign in or create an account inside that panel.</div></li>
            <li><span>C</span><div>Dashboard actions such as logout and event management stay inside the appropriate authenticated workspace.</div></li>
        </ul>
    </div>
</section>
<?php
app_shell_end();
?>
