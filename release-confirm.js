(function () {
    "use strict";

    const backdrop = document.createElement("div");
    backdrop.className = "release-confirm-backdrop";
    backdrop.hidden = true;
    backdrop.innerHTML = `
        <section class="release-confirm-dialog" role="alertdialog" aria-modal="true" aria-labelledby="releaseConfirmTitle" aria-describedby="releaseConfirmDescription">
            <div class="release-confirm-icon" aria-hidden="true"><i class="bi bi-send-check"></i></div>
            <h2 id="releaseConfirmTitle">Release this request?</h2>
            <p id="releaseConfirmDescription">Confirm that this document has been given to the resident. This action updates the request status.</p>
            <div class="release-confirm-actions">
                <button type="button" class="release-confirm-cancel" data-release-cancel>Cancel</button>
                <button type="button" class="release-confirm-submit" data-release-confirm>Confirm Release</button>
            </div>
        </section>
    `;
    document.body.appendChild(backdrop);

    const dialog = backdrop.querySelector(".release-confirm-dialog");
    const cancelButton = backdrop.querySelector("[data-release-cancel]");
    const confirmButton = backdrop.querySelector("[data-release-confirm]");
    let pendingForm = null;
    let pendingSubmitter = null;
    let previousFocus = null;

    const closeDialog = function (restoreFocus) {
        backdrop.classList.remove("is-open");
        backdrop.hidden = true;
        document.body.classList.remove("release-confirm-open");
        pendingForm = null;
        pendingSubmitter = null;
        if (restoreFocus && previousFocus && previousFocus.isConnected) {
            previousFocus.focus();
        }
        previousFocus = null;
    };

    document.addEventListener("submit", function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches("form[data-confirm-release]")) {
            return;
        }

        if (form.dataset.releaseConfirmed === "1") {
            delete form.dataset.releaseConfirmed;
            return;
        }

        event.preventDefault();
        pendingForm = form;
        pendingSubmitter = event.submitter || form.querySelector('button[name="status"][value="released"]');
        previousFocus = pendingSubmitter || document.activeElement;
        backdrop.hidden = false;
        backdrop.classList.add("is-open");
        document.body.classList.add("release-confirm-open");
        confirmButton.focus();
    });

    cancelButton.addEventListener("click", function () {
        closeDialog(true);
    });

    confirmButton.addEventListener("click", function () {
        const form = pendingForm;
        const submitter = pendingSubmitter;
        if (!form || !form.isConnected) {
            closeDialog(true);
            return;
        }

        backdrop.classList.remove("is-open");
        backdrop.hidden = true;
        document.body.classList.remove("release-confirm-open");
        pendingForm = null;
        pendingSubmitter = null;
        previousFocus = null;
        form.dataset.releaseConfirmed = "1";
        if (typeof form.requestSubmit === "function") {
            form.requestSubmit(submitter || undefined);
        } else {
            form.submit();
        }
    });

    backdrop.addEventListener("click", function (event) {
        if (event.target === backdrop) closeDialog(true);
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape" && !backdrop.hidden) {
            event.preventDefault();
            closeDialog(true);
        }
    });
}());