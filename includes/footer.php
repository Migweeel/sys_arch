


<footer class="hotline-footer">
    <div class="footer-content">
        <p class="city-gov">City Government of Quezon City</p>
        <p class="update-info">This directory is regularly updated. For the most current information, please visit 
            the official Quezon City government website.
        </p>
        <p class="last-updated">Last Updated: January 2026</p>
    </div>

    <nav class="site-footer-legal" aria-label="Legal and accessibility">
        <h2>Legal</h2>
        <ul>
            <li><a href="<?= htmlspecialchars($base) ?>privacy-policy/">Privacy Policy</a></li>
            <li><a href="<?= htmlspecialchars($base) ?>terms/">Terms and Conditions</a></li>
            <li><a href="<?= htmlspecialchars($base) ?>disclaimer/">Disclaimer</a></li>
            <li><a href="<?= htmlspecialchars($base) ?>copyright/">Copyright</a></li>
            <li><a href="<?= htmlspecialchars($base) ?>accessibility/">Accessibility</a></li>
        </ul>
        <button type="button" class="cookie-settings-link" data-open-cookie-settings>
            Cookie Settings
        </button>
    </nav>
</footer>

<section class="cookie-banner" id="cookie-banner" aria-label="Cookie preferences" hidden>
    <div class="cookie-banner-content">
        <div>
            <h2>Cookie choices</h2>
            <p>
                This site uses essential cookies for core functions. Optional analytics,
                advertising, and functional cookies are off unless you choose to enable them.
                <a href="<?= htmlspecialchars($base) ?>privacy-policy/">Read the Privacy Policy</a>.
            </p>
        </div>
        <div class="cookie-banner-actions">
            <button type="button" class="cookie-button cookie-button-primary" data-consent-accept>
                Accept All
            </button>
            <button type="button" class="cookie-button" data-consent-reject>
                Reject Non-Essential
            </button>
            <button type="button" class="cookie-button" data-open-cookie-settings>
                Customize Preferences
            </button>
        </div>
    </div>
    <p class="cookie-status" id="cookie-status" role="status" aria-live="polite"></p>
</section>

<dialog class="cookie-dialog" id="cookie-dialog" aria-labelledby="cookie-dialog-title" aria-describedby="cookie-dialog-description">
    <form method="dialog" class="cookie-dialog-form">
        <div class="cookie-dialog-heading">
            <h2 id="cookie-dialog-title">Cookie preferences</h2>
            <button type="submit" class="cookie-dialog-close" aria-label="Close cookie preferences">×</button>
        </div>
        <p id="cookie-dialog-description">
            Choose which optional cookie categories you allow. You can change or withdraw your choice at any time.
        </p>
        <fieldset class="cookie-preference-list">
            <legend>Your preferences</legend>
            <label class="cookie-preference">
                <input type="checkbox" checked disabled>
                <span><strong>Strictly necessary</strong><small>Required for sessions, security, core site features, and remembering your cookie choice.</small></span>
                <span class="cookie-always-on">Always on</span>
            </label>
            <label class="cookie-preference">
                <input type="checkbox" name="analytics" value="1">
                <span><strong>Analytics</strong><small>Helps measure site use. No analytics service is currently configured.</small></span>
            </label>
            <label class="cookie-preference">
                <input type="checkbox" name="advertising" value="1">
                <span><strong>Advertising and marketing</strong><small>Supports advertising or marketing measurement. No such service is currently configured.</small></span>
            </label>
            <label class="cookie-preference">
                <input type="checkbox" name="functional" value="1">
                <span><strong>Functional</strong><small>Allows the optional “Remember username and role” login feature. No other functional integration is configured.</small></span>
            </label>
        </fieldset>
        <p class="cookie-status" data-dialog-cookie-status role="status" aria-live="polite"></p>
        <div class="cookie-dialog-actions">
            <button type="button" class="cookie-button cookie-button-primary" data-consent-save>
                Save Preferences
            </button>
            <button type="button" class="cookie-button" data-consent-reject>
                Reject Non-Essential
            </button>
        </div>
    </form>
</dialog>

<script src="<?= htmlspecialchars($base) ?>cookie-consent.js" defer></script>