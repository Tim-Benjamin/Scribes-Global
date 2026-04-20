<?php
$pageTitle = 'Booking Request - Scribes Global';
$pageDescription = 'Book Scribes Global for your event';
$pageCSS = 'connect';

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/session.php';

require_once __DIR__ . '/../../includes/header.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800;900&display=swap');

:root {
    --clay-bg-start: #dce8fd;
    --clay-bg-end: #bdd0f9;
    --clay-blue: #5b9cf6;
    --clay-blue-dk: #4a8ef5;
    --clay-dark: #1a2b5e;
    --clay-text: #1d3872;
    --clay-muted: #aab8d4;
    --clay-white: #ffffff;
    --clay-r-card: 28px;
    --clay-r-input: 16px;
    --clay-r-btn: 50px;
}

* {
    box-sizing: border-box;
}

.booking-page {
    min-height: 100vh;
    background: linear-gradient(145deg, var(--clay-bg-start) 0%, #c9dbfb00 50%, var(--clay-bg-end) 100%);
    padding: 5rem 0 4rem;
    position: relative;
    overflow: hidden;
    font-family: 'Nunito', sans-serif;
}

/* ── Background blobs ── */
.booking-page::before,
.booking-page::after {
    content: '';
    position: fixed;
    border-radius: 50%;
    filter: blur(90px);
    opacity: 0.5;
    pointer-events: none;
    z-index: 0;
}

.booking-page::before {
    width: 500px;
    height: 500px;
    background: #a8c4fb;
    top: -180px;
    left: -180px;
}

.booking-page::after {
    width: 420px;
    height: 420px;
    background: #b5d0fc;
    bottom: -160px;
    right: -160px;
}

.booking-container {
    max-width: 980px;
    margin: 0 auto;
    padding: 0 2rem;
    position: relative;
    z-index: 1;
}

/* ── Page Title ── */
.booking-title {
    font-size: 2.6rem;
    font-weight: 900;
    text-align: center;
    margin-bottom: 0.5rem;
    color: var(--clay-dark);
    letter-spacing: -0.5px;
}

.booking-subtitle {
    text-align: center;
    color: var(--clay-muted);
    font-weight: 700;
    font-size: 0.95rem;
    margin-bottom: 2.5rem;
}

/* ── Contact Pills ── */
.contact-info {
    display: flex;
    justify-content: center;
    gap: 1.25rem;
    margin-bottom: 3rem;
    flex-wrap: wrap;
}

.contact-pill {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: rgba(255, 255, 255, 0.78);
    border: 1.5px solid rgba(255, 255, 255, 0.9);
    border-radius: 50px;
    padding: 0.6rem 1.4rem 0.6rem 0.75rem;
    box-shadow:
        -3px -3px 10px rgba(255, 255, 255, 0.85),
        4px 4px 14px rgba(130, 160, 220, 0.28);
}

.contact-pill-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 34px;
    height: 34px;
    border-radius: 50%;
    background: linear-gradient(145deg, #6faaf8, #5b9cf6);
    color: #fff;
    font-size: 0.8rem;
    box-shadow: 2px 2px 6px rgba(91, 156, 246, 0.4);
    flex-shrink: 0;
}

.contact-pill-text strong {
    display: block;
    font-size: 0.72rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--clay-blue);
}

.contact-pill-text span {
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--clay-dark);
}

/* ── Clay Card ── */
.booking-card {
    background: rgba(255, 255, 255, 0.82);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
    border-radius: var(--clay-r-card);
    padding: 2.8rem 2.6rem;
    border: 1.5px solid rgba(255, 255, 255, 0.8);
    box-shadow:
        -6px -6px 16px rgba(255, 255, 255, 0.85),
        8px 8px 24px rgba(130, 160, 220, 0.35),
        0 20px 60px rgba(100, 140, 220, 0.18);
}

/* ── Section Label ── */
.form-section-label {
    font-size: 0.72rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--clay-blue);
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid rgba(91, 156, 246, 0.15);
}

/* ── Form Grid ── */
.form-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
    margin-bottom: 1.25rem;
}

.form-grid.two-col {
    grid-template-columns: repeat(2, 1fr);
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-size: 0.78rem;
    font-weight: 800;
    color: var(--clay-text);
    margin-bottom: 0.45rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

/* ── Clay Inputs ── */
.form-group input,
.form-group textarea {
    padding: 0.85rem 1.1rem;
    border-radius: var(--clay-r-input);
    border: 1.5px solid rgba(255, 255, 255, 0.9);
    background: rgba(255, 255, 255, 0.72);
    font-family: 'Nunito', sans-serif;
    font-size: 0.92rem;
    font-weight: 600;
    color: var(--clay-dark);
    outline: none;
    transition: all 0.25s ease;
    box-shadow:
        inset 2px 2px 6px rgba(160, 190, 240, 0.22),
        inset -2px -2px 6px rgba(255, 255, 255, 0.9),
        2px 2px 8px rgba(140, 170, 230, 0.12);
    width: 100%;
}

.form-group input::placeholder,
.form-group textarea::placeholder {
    color: var(--clay-muted);
    font-weight: 600;
}

.form-group input:focus,
.form-group textarea:focus {
    border-color: rgba(91, 156, 246, 0.5);
    background: rgba(255, 255, 255, 0.9);
    box-shadow:
        inset 2px 2px 8px rgba(91, 156, 246, 0.1),
        inset -2px -2px 6px rgba(255, 255, 255, 0.95),
        0 0 0 3px rgba(91, 156, 246, 0.1),
        3px 3px 12px rgba(91, 156, 246, 0.18);
}

/* Date input fix */
.form-group input[type="date"] {
    color: var(--clay-dark);
}

.form-group input[type="date"]::-webkit-calendar-picker-indicator {
    opacity: 0.5;
    cursor: pointer;
}

.form-group textarea {
    resize: vertical;
    min-height: 140px;
    border-radius: var(--clay-r-input);
    line-height: 1.6;
}

/* ── Submit Button ── */
.submit-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.6rem;
    background: linear-gradient(160deg, #6faaf8 0%, #5b9cf6 40%, #4a8ef5 100%);
    color: #fff;
    border: none;
    border-radius: var(--clay-r-btn);
    padding: 1rem 3.5rem;
    font-family: 'Nunito', sans-serif;
    font-size: 1rem;
    font-weight: 800;
    letter-spacing: 0.02em;
    cursor: pointer;
    transition: all 0.25s ease;
    margin: 2rem auto 0;
    box-shadow:
        0 6px 20px rgba(91, 156, 246, 0.45),
        0 2px 6px rgba(91, 156, 246, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
}

.submit-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow:
        0 10px 28px rgba(91, 156, 246, 0.5),
        0 4px 10px rgba(91, 156, 246, 0.35),
        inset 0 1px 0 rgba(255, 255, 255, 0.35);
}

.submit-btn:active:not(:disabled) {
    transform: translateY(1px);
    box-shadow:
        0 3px 10px rgba(91, 156, 246, 0.3),
        inset 0 2px 4px rgba(0, 0, 0, 0.08);
}

.submit-btn:disabled {
    opacity: 0.65;
    cursor: not-allowed;
}

/* ── Responsive ── */
@media (max-width: 900px) {
    .form-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .booking-title {
        font-size: 1.9rem;
    }

    .booking-card {
        padding: 1.8rem 1.3rem;
    }

    .form-grid,
    .form-grid.two-col {
        grid-template-columns: 1fr;
    }

    .submit-btn {
        width: 100%;
        padding: 1rem 2rem;
    }
}
</style>

<!-- <div id="three-canvas-container"
    style="position: fixed; top: 0; left: 0; width: 100%; height: 100vh; z-index: -1; pointer-events: none;"></div>
<div class="booking-page"> -->

    <div class="booking-container">

        <h1 class="booking-title">Booking Request</h1>
        <p class="booking-subtitle">Fill out the form below and we'll get back to you shortly</p>

        <div class="contact-info">
            <div class="contact-pill">
                <span class="contact-pill-icon"><i class="fas fa-phone"></i></span>
                <div class="contact-pill-text">
                    <strong>Phone</strong>
                    <span>0546296188 / 020 931 5447</span>
                </div>
            </div>
            <div class="contact-pill">
                <span class="contact-pill-icon"><i class="fas fa-envelope"></i></span>
                <div class="contact-pill-text">
                    <strong>Email</strong>
                    <span>info@scribesglobal.com</span>
                </div>
            </div>
        </div>

        <div class="booking-card">
            <form class="booking-form" id="bookingForm">

                <!-- Personal Info -->
                <p class="form-section-label"><i class="fas fa-user" style="margin-right:0.4rem;"></i>Personal
                    Information</p>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" placeholder="Benjamin" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" placeholder="Timothy">
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" placeholder="tim@gmail.com" required>
                    </div>
                </div>

                <!-- Event Info -->
                <p class="form-section-label"><i class="fas fa-calendar-star" style="margin-right:0.4rem;"></i>Event
                    Details</p>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="phone">Phone *</label>
                        <input type="tel" id="phone" name="phone" placeholder="+233 000 000 000" required>
                    </div>
                    <div class="form-group">
                        <label for="organization">Organization *</label>
                        <input type="text" id="organization" name="organization" placeholder="Your Organization"
                            required>
                    </div>
                    <div class="form-group">
                        <label for="event_name">Event Name *</label>
                        <input type="text" id="event_name" name="event_name" placeholder="e.g. SOS26" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="location">Event Location *</label>
                        <input type="text" id="location" name="location" placeholder="City, Venue" required>
                    </div>
                    <div class="form-group">
                        <label for="date">Date *</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                    <div class="form-group">
                        <label for="audience">Audience Description</label>
                        <input type="text" id="audience" name="audience" placeholder="e.g. 200 professionals">
                    </div>
                </div>

                <!-- Message -->
                <p class="form-section-label"><i class="fas fa-comment-alt" style="margin-right:0.4rem;"></i>Additional
                    Information</p>
                <div class="form-group" style="margin-bottom:0;">
                    <label for="message">Message</label>
                    <textarea id="message" name="message"
                        placeholder="Tell us more about your event, specific requirements, or any questions you have..."></textarea>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i> Submit Request
                </button>

            </form>
        </div>

    </div>
</div>

<script>
document.getElementById('bookingForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = this.querySelector('.submit-btn');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';

    const formData = new FormData(this);

    try {
        const response = await fetch('<?= SITE_URL ?>/api/booking.php?action=submit_booking', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            alert('Booking request submitted successfully! We will contact you soon.');
            this.reset();
        } else {
            alert(result.message || 'Failed to submit booking request. Please try again.');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
    }
});

// Set minimum date to today
document.getElementById('date').min = new Date().toISOString().split('T')[0];
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>