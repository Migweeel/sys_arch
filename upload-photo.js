(() => {
    const trigger = document.getElementById("avatarTrigger");
    const input = document.getElementById("avatarInput");
    const dialog = document.getElementById("avatarCropDialog");
    const canvas = document.getElementById("avatarCropCanvas");
    const zoomInput = document.getElementById("avatarCropZoom");
    const cancelButton = document.getElementById("avatarCropCancel");
    const cancelAction = document.getElementById("avatarCropCancelButton");
    const saveButton = document.getElementById("avatarCropSave");
    const csrfToken = document.getElementById("avatarCsrfToken");
    if (!trigger || !input || !dialog || !canvas) return;

    const context = canvas.getContext("2d");
    let image;
    let offsetX = 0;
    let offsetY = 0;
    let dragging = false;
    let dragStartX = 0;
    let dragStartY = 0;

    const draw = () => {
        if (!image) return;
        const size = Math.min(image.width, image.height);
        const scale = (canvas.width / size) * Number(zoomInput.value);
        const width = image.width * scale;
        const height = image.height * scale;
        const x = (canvas.width - width) / 2 + offsetX;
        const y = (canvas.height - height) / 2 + offsetY;
        context.clearRect(0, 0, canvas.width, canvas.height);
        context.fillStyle = "#111827";
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(image, x, y, width, height);
    };

    const closeDialog = () => {
        dialog.hidden = true;
        input.value = "";
        image = null;
    };

    trigger.addEventListener("click", () => input.click());
    zoomInput.addEventListener("input", draw);
    cancelButton.addEventListener("click", closeDialog);
    cancelAction.addEventListener("click", closeDialog);

    canvas.addEventListener("pointerdown", (event) => {
        dragging = true;
        dragStartX = event.clientX - offsetX;
        dragStartY = event.clientY - offsetY;
        canvas.setPointerCapture(event.pointerId);
    });
    canvas.addEventListener("pointermove", (event) => {
        if (!dragging) return;
        offsetX = event.clientX - dragStartX;
        offsetY = event.clientY - dragStartY;
        draw();
    });
    canvas.addEventListener("pointerup", () => {
        dragging = false;
    });

    input.addEventListener("change", () => {
        const file = input.files[0];
        if (!file) return;
        if (!file.type.startsWith("image/")) {
            alert("Please choose an image file.");
            input.value = "";
            return;
        }
        if (file.size > 25 * 1024 * 1024) {
            alert("Maximum file size: 25MB");
            input.value = "";
            return;
        }
        const reader = new FileReader();
        reader.onload = () => {
            image = new Image();
            image.onload = () => {
                offsetX = 0;
                offsetY = 0;
                zoomInput.value = "1";
                dialog.hidden = false;
                draw();
            };
            image.src = reader.result;
        };
        reader.readAsDataURL(file);
    });

    saveButton.addEventListener("click", () => {
        saveButton.disabled = true;
        canvas.toBlob((blob) => {
            if (!blob) {
                alert("Could not prepare the cropped image.");
                saveButton.disabled = false;
                return;
            }
            const formData = new FormData();
            formData.append("avatar", blob, "avatar.jpg");
            if (csrfToken) formData.append("csrf_token", csrfToken.value);
            fetch("../actions/uploadAvatar.php", {
                method: "POST",
                body: formData,
                credentials: "same-origin"
            })
                .then((response) => response.json())
                .then((data) => {
                    if (!data.success) throw new Error(data.error || "Upload failed.");
                    const preview = document.getElementById("avatarPreview");
                    const url = data.avatarPath + (data.avatarPath.includes("?") ? "&" : "?") + "v=" + Date.now();
                    if (preview) {
                        preview.src = url;
                        preview.style.display = "block";
                    } else {
                        const img = document.createElement("img");
                        img.id = "avatarPreview";
                        img.src = url;
                        img.alt = "Profile avatar";
                        trigger.prepend(img);
                    }
                    const icon = trigger.querySelector(".avatar-upload-icon");
                    if (icon) icon.style.display = "none";
                    trigger.classList.add("has-avatar");
                    closeDialog();
                })
                .catch((error) => alert(error.message))
                .finally(() => {
                    saveButton.disabled = false;
                });
        }, "image/jpeg", 0.9);
    });
})();