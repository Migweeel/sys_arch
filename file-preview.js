(function () {
  "use strict";

  const imageExtensions = new Set(["jpg", "jpeg", "png", "gif", "webp", "svg"]);
  const documentExtensions = new Set(["pdf", "doc", "docx", "xls", "xlsx", "ppt", "pptx", "txt", "rtf", "csv"]);
  const previewableExtensions = new Set([...imageExtensions, ...documentExtensions]);
  let triggerElement = null;

  const backdrop = document.createElement("div");
  backdrop.className = "system-file-preview-backdrop";
  backdrop.hidden = true;
  backdrop.setAttribute("aria-hidden", "true");
  backdrop.innerHTML = `
    <section class="system-file-preview-panel" role="dialog" aria-modal="true" aria-labelledby="systemFilePreviewTitle">
      <header class="system-file-preview-header">
        <div class="system-file-preview-heading">
          <h2 id="systemFilePreviewTitle">File preview</h2>
          <p data-system-file-preview-name></p>
        </div>
        <button type="button" class="system-file-preview-close" data-system-file-preview-close aria-label="Close file preview">
          <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
      </header>
      <div class="system-file-preview-content" data-system-file-preview-content></div>
    </section>
  `;
  document.body.appendChild(backdrop);

  const content = backdrop.querySelector("[data-system-file-preview-content]");
  const fileNameHeading = backdrop.querySelector("[data-system-file-preview-name]");
  const closeButton = backdrop.querySelector("[data-system-file-preview-close]");

  const closePreview = function () {
    backdrop.classList.remove("is-open");
    backdrop.setAttribute("aria-hidden", "true");
    backdrop.hidden = true;
    content.replaceChildren();
    document.body.classList.remove("system-file-preview-open");
    if (triggerElement) triggerElement.focus();
    triggerElement = null;
  };

  const openPreview = function (link, fileUrl, extension) {
    const fileName = decodeURIComponent(fileUrl.pathname.split("/").pop() || "Uploaded file");
    fileNameHeading.textContent = fileName;
    triggerElement = link;

    let viewer;
    if (imageExtensions.has(extension)) {
      viewer = document.createElement("img");
      viewer.className = "system-file-preview-image";
      viewer.alt = fileName;
    } else if (extension === "pdf") {
      viewer = document.createElement("iframe");
      viewer.className = "system-file-preview-pdf";
      viewer.title = fileName;
    } else {
      viewer = document.createElement("div");
      viewer.className = "system-file-preview-download-state";
      const message = document.createElement("p");
      message.textContent = "This document format cannot be previewed in the browser.";
      const download = document.createElement("a");
      download.href = fileUrl.href;
      download.download = fileName;
      download.className = "system-file-preview-download";
      download.textContent = "Download file";
      viewer.append(message, download);
    }

    if (imageExtensions.has(extension) || extension === "pdf") {
      viewer.src = fileUrl.href;
    }
    content.replaceChildren(viewer);
    backdrop.hidden = false;
    backdrop.setAttribute("aria-hidden", "false");
    backdrop.classList.add("is-open");
    document.body.classList.add("system-file-preview-open");
    closeButton.focus();
  };

  document.addEventListener("click", function (event) {
    const link = event.target.closest('a[target="_blank"]');
    if (!link || link.hasAttribute("download")) return;

    let fileUrl;
    try {
      fileUrl = new URL(link.href, window.location.href);
    } catch (error) {
      return;
    }

    if (fileUrl.origin !== window.location.origin) return;

    const extension = fileUrl.pathname.split(".").pop().toLowerCase();
    if (!previewableExtensions.has(extension)) return;

    event.preventDefault();
    openPreview(link, fileUrl, extension);
  });

  closeButton.addEventListener("click", closePreview);
  backdrop.addEventListener("click", function (event) {
    if (event.target === backdrop) closePreview();
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && !backdrop.hidden) {
      event.preventDefault();
      event.stopImmediatePropagation();
      closePreview();
    }
  }, true);
}());