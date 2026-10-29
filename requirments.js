document.addEventListener("DOMContentLoaded", () => {
  const docType = document.getElementById("doc-type");
  const container = document.getElementById("requirementsContainer");
  const fillUpContainer = document.getElementById("fillUpContainer");
  const requestModeInputs = [...document.querySelectorAll('input[name="request_mode"]')];
  const submitBtn = document.querySelector(".submit-btn");
  const isVerified = submitBtn?.dataset.verified === "1";
  const MAX_SIZE = 25 * 1024 * 1024;

  if (!docType || !container || !fillUpContainer || !requestModeInputs.length || !submitBtn) {
    return;
  }

  const escapeHtml = value => String(value)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");

  const requirementKey = value => {
    const key = String(value).toLowerCase().replace(/[^a-z0-9]+/g, "_").replace(/^_+|_+$/g, "");
    return key || "requirement";
  };

  const parseRequirements = option => {
    if (!option) {
      return [];
    }

    try {
      const parsed = JSON.parse(option.dataset.requirements || "[]");
      if (Array.isArray(parsed)) {
        return parsed.map(item => String(item).trim()).filter(Boolean);
      }
    } catch (error) {
      return [];
    }

    return [];
  };

  const parseFillUpFields = option => {
    if (!option) {
      return [];
    }

    try {
      const parsed = JSON.parse(option.dataset.fillUpFields || "[]");
      if (Array.isArray(parsed)) {
        return parsed.map(item => String(item).trim()).filter(Boolean);
      }
    } catch (error) {
      return [];
    }

    return [];
  };

  const parseProfileValues = option => {
    if (!option) {
      return [];
    }

    try {
      const parsed = JSON.parse(option.dataset.profileFillUpValues || "[]");
      return Array.isArray(parsed) ? parsed.map(value => String(value || "")) : [];
    } catch (error) {
      return [];
    }
  };

  const buildRequirementsHtml = requirements => {
    const items = requirements.length > 0 ? requirements : ["Government ID"];
    const usedKeys = new Map();

    return `
      <div class="requirements-section">
        <h3>REQUIREMENTS</h3>
        ${items.map((label, index) => {
          const baseKey = requirementKey(label);
          const count = (usedKeys.get(baseKey) || 0) + 1;
          usedKeys.set(baseKey, count);
          const key = count > 1 ? `${baseKey}_${count}` : baseKey;
          const inputId = `requirements_${key}`;

          return `
            <div class="requirement-item">
              <span class="requirement-number">${index + 1}.</span>
              <span class="req-label">${escapeHtml(label)}</span>
              <label class="action-btn" for="${escapeHtml(inputId)}">
                <span class="upload-text">Upload</span>
                <input id="${escapeHtml(inputId)}" class="upload-input" type="file" name="requirements[${escapeHtml(key)}]" accept="image/*,.pdf,.doc,.docx" required>
              </label>
            </div>
          `;
        }).join("")}
      </div>
    `;
  };

  const buildFillUpHtml = (fillUpFields, profileValues, onBehalf) => {
    if (!fillUpFields.length) {
      return "";
    }

    return `
        <div class="request-fill-up-section">
          <h3>FILL UP</h3>
          ${fillUpFields.map((label, index) => {
            const normalizedLabel = label.toLowerCase();
            const normalizedKey = normalizedLabel.replace(/[^a-z0-9]+/g, "");
            const inputId = `fill_up_${index}`;
            const fieldName = `fill_up[${index}]`;
            const profileValue = String(profileValues[index] || "");
            const isAddressField = normalizedKey.includes("address");
            const isDateField = ["birthday", "birthdate", "dateofbirth"].includes(normalizedKey);
            const inputType = isDateField ? "date" : "text";

            if (!onBehalf && profileValue.trim()) {
              const prefilledControl = isAddressField
                ? `<textarea id="${inputId}" name="${fieldName}" rows="3" readonly>${escapeHtml(profileValue)}</textarea>`
                : `<input id="${inputId}" type="${inputType}" name="${fieldName}" value="${escapeHtml(profileValue)}" readonly>`;

              return `
                <label class="request-fill-up-field request-fill-up-prefilled" for="${inputId}">
                  <span>${escapeHtml(label)}</span>
                  ${prefilledControl}
                  <small>Filled from your profile</small>
                </label>
              `;
            }

            const control = isAddressField
              ? `<textarea id="${inputId}" name="${fieldName}" rows="3" required></textarea>`
              : `<input id="${inputId}" type="${inputType}" name="${fieldName}" autocomplete="off" required>`;

            return `
              <label class="request-fill-up-field" for="${inputId}">
                <span>${escapeHtml(label)}</span>
                ${control}
              </label>
            `;
          }).join("")}
        </div>
    `;
  };

  const getSelectedOption = () => docType.options[docType.selectedIndex];
  const isOnBehalf = () => requestModeInputs.some(input => input.checked && input.value === "on_behalf");

  const renderFillUpFields = () => {
    const option = getSelectedOption();
    fillUpContainer.innerHTML = buildFillUpHtml(
      parseFillUpFields(option),
      parseProfileValues(option),
      isOnBehalf()
    );

    if (!isVerified) {
      fillUpContainer.querySelectorAll("input, textarea").forEach(input => {
        input.disabled = true;
      });
    }
  };

  const updateSubmitButton = () => {
    const files = [...container.querySelectorAll('input[type="file"]')];
    const fillUpInputs = [...fillUpContainer.querySelectorAll('[name^="fill_up["]')];
    const purpose = document.getElementById("request-purpose");
    const filesComplete = files.every(input => input.files.length > 0);
    const fillUpComplete = fillUpInputs.every(input => input.value.trim() !== "");
    submitBtn.disabled = !isVerified || !docType.value || !filesComplete || !fillUpComplete || !purpose || !purpose.value.trim();
  };

  docType.addEventListener("change", () => {
    const selectedOption = getSelectedOption();
    const requirements = parseRequirements(selectedOption);
    container.innerHTML = buildRequirementsHtml(requirements);
    renderFillUpFields();

    const inputs = container.querySelectorAll("input[type=file]");

    if (!isVerified) {
      container.querySelectorAll("input, textarea").forEach(input => {
        input.disabled = true;
      });
      updateSubmitButton();
      return;
    }

    inputs.forEach(input => {
      input.addEventListener("change", () => {
        if (!input.files || !input.files[0]) {
          const textSpan = input.parentElement.querySelector(".upload-text");
          if (textSpan) textSpan.textContent = "Upload";
          updateSubmitButton();
          return;
        }

        if (input.files[0].size > MAX_SIZE) {
          alert("File must not exceed 25MB.");
          input.value = "";
          const textSpan = input.parentElement.querySelector(".upload-text");
          if (textSpan) textSpan.textContent = "Upload";
          updateSubmitButton();
          return;
        }

        const textSpan = input.parentElement.querySelector(".upload-text");
        if (textSpan) {
          textSpan.textContent = input.files[0].name;
        }

        updateSubmitButton();
      });
    });

    fillUpContainer.addEventListener("input", updateSubmitButton);

    container.querySelectorAll(".action-btn").forEach(label => {
      const input = label.querySelector("input[type=file]");
      if (!input) return;
      label.addEventListener("click", event => {
        if (event.target !== input) {
          input.click();
        }
      });
    });

    updateSubmitButton();
  });

  requestModeInputs.forEach(input => input.addEventListener("change", () => {
    renderFillUpFields();
    updateSubmitButton();
  }));

  const purposeInput = document.getElementById("request-purpose");
  if (purposeInput) {
    purposeInput.addEventListener("input", updateSubmitButton);
  }
});
