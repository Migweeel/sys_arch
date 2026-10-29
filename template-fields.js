document.addEventListener("click", event => {
  const addButton = event.target.closest("[data-add-fill-up-field]");
  if (addButton) {
    const editor = addButton.closest("[data-template-fill-up-editor]");
    const rows = editor?.querySelector("[data-template-fill-up-rows]");
    const firstRow = rows?.querySelector(".template-fill-up-row");
    if (!rows || !firstRow) {
      return;
    }

    const newRow = firstRow.cloneNode(true);
    const input = newRow.querySelector('input[name="fill_up_fields[]"]');
    if (!input) {
      return;
    }

    input.value = "";
    rows.appendChild(newRow);
    input.focus();
    return;
  }

  const removeButton = event.target.closest("[data-remove-fill-up-field]");
  if (!removeButton) {
    return;
  }

  const editor = removeButton.closest("[data-template-fill-up-editor]");
  const rows = editor?.querySelector("[data-template-fill-up-rows]");
  const row = removeButton.closest(".template-fill-up-row");
  if (!rows || !row) {
    return;
  }

  if (rows.children.length === 1) {
    const input = row.querySelector('input[name="fill_up_fields[]"]');
    if (input) input.value = "";
    return;
  }

  row.remove();
});