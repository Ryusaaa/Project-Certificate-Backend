// Global variables
let elements = [];
let selectedElement = null;
let draggedElement = null;
let offsetX = 0;
let offsetY = 0;
let currentZoom = 1;
const _registeredFontFaces = {};

// Zoom functions
function zoomPreview(direction) {
    const container = document.getElementById("certificate-preview");
    if (direction === "in" && currentZoom < 2) {
        currentZoom += 0.1;
    } else if (direction === "out" && currentZoom > 0.5) {
        currentZoom -= 0.1;
    }
    container.style.transform = `scale(${currentZoom})`;
}

function resetZoom() {
    currentZoom = 1;
    const container = document.getElementById("certificate-preview");
    container.style.transform = "scale(1)";
}

// Notification function
function showNotification(message, type = "success") {
    const existingNotifications = document.querySelectorAll(".notification");
    existingNotifications.forEach((notification) => notification.remove());

    const notification = document.createElement("div");
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = "fadeOut 0.3s ease-out forwards";
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// File handling functions
function handleFileSelect(event) {
    const fileInput = event.target;
    const fileNameWrapper = document.querySelector(".file-name-wrapper");
    const fileNameSpan = document.querySelector(".file-name");
    const uploadButton = document.getElementById("uploadButton");
    const fileIcon = document.querySelector(".file-icon");
    const file = fileInput.files[0];

    if (!file) return;

    // Reset states
    fileNameSpan.textContent = "Belum ada file yang dipilih";
    fileIcon.textContent = "📄";
    fileNameWrapper.style.color = "#666";
    uploadButton.style.display = "none";
    fileInput.classList.remove("is-invalid");

    // Validate file
    if (!validateFile(file)) {
        fileInput.value = "";
        return;
    }

    // Preview valid file
    fileNameSpan.textContent = file.name;
    fileIcon.textContent = "🖼️";
    fileNameWrapper.style.color = "#2d3436";
    uploadButton.style.display = "inline-block";
    uploadButton.style.opacity = "1";
}

function validateFile(file) {
    const validTypes = ["image/jpeg", "image/png", "image/gif"];
    if (!validTypes.includes(file.type)) {
        showNotification("Format file harus JPG, PNG, atau GIF", "error");
        return false;
    }

    if (file.size > 2 * 1024 * 1024) {
        showNotification("Ukuran file maksimal 2MB", "error");
        return false;
    }

    return true;
}

// Image upload functions
async function handleUploadBackground() {
    const fileInput = document.getElementById("backgroundFile");
    const file = fileInput.files[0];
    const preview = document.getElementById("certificate-preview");
    const uploadButton = document.getElementById("uploadButton");

    if (!file) {
        showNotification("Pilih file terlebih dahulu", "error");
        return;
    }

    try {
        uploadButton.disabled = true;
        const formData = new FormData();
        formData.append("background_image", file);
        formData.append(
            "_token",
            document.querySelector('meta[name="csrf-token"]').content
        );

        const response = await fetch("/sertifikat-templates/upload-image", {
            method: "POST",
            body: formData,
        });

        if (!response.ok) {
            throw new Error("Upload gagal");
        }

        const result = await response.json();
        preview.style.backgroundImage = `url('${result.url}')`;
        preview.classList.add("has-bg");
        preview.dataset.backgroundImage = result.url;
        showNotification("Background berhasil diupload");
    } catch (error) {
        console.error("Upload error:", error);
        showNotification(
            error.message || "Gagal mengupload background",
            "error"
        );
        preview.style.backgroundImage = "";
        preview.classList.remove("has-bg");
        delete preview.dataset.backgroundImage;
    } finally {
        uploadButton.disabled = false;
    }
}

// Element management
function toggleOptions() {
    const type = document.getElementById("elementType").value;
    document.getElementById("textOptions").style.display =
        type === "text" ? "block" : "none";
    document.getElementById("imageOptions").style.display =
        type === "image" ? "block" : "none";
    document.getElementById("qrcodeOptions").style.display =
        type === "qrcode" ? "block" : "none";
}

function addElement() {
    const type = document.getElementById("elementType").value;
    const element = createNewElement(type);

    if (!element) return;

    // Add the new element to the end of the array to ensure highest z-index
    element.zIndex = elements.length + 1;
    elements.push(element);

    // Render all elements to maintain proper layering
    renderElements();

    // Show success notification
    showNotification("Elemen berhasil ditambahkan");

    // Clear form fields if needed
    if (type === "text") {
        document.getElementById("elementText").value = "";
    }
}

function createNewElement(type) {
    const element = {
        id: "element-" + Date.now(),
        type: type,
        x: Math.round(842 / 2 - 50),
        y: Math.round(595 / 2 - 20),
    };

    switch (type) {
        case "qrcode":
            return {
                ...element,
                width:
                    parseInt(document.getElementById("qrcodeSize").value) ||
                    100,
                height:
                    parseInt(document.getElementById("qrcodeSize").value) ||
                    100,
                placeholderType: "qrcode",
            };
        case "text":
            const text = document.getElementById("elementText").value;
            const placeholderType =
                document.getElementById("placeholderType").value;

            if (!text && placeholderType === "custom") {
                showNotification("Text tidak boleh kosong", "error");
                return null;
            }

            return {
                ...element,
                text: text,
                fontSize:
                    parseInt(document.getElementById("fontSize").value) || 16,
                fontFamily:
                    document.getElementById("fontFamily").value || "Arial",
                fontWeight:
                    document.getElementById("fontWeight").value || "400",
                fontStyle:
                    document.getElementById("fontStyle").value || "normal",
                textAlign: document.getElementById("textAlign").value || "left",
                placeholderType: placeholderType,
                color: "#000000",
            };
        default:
            return null;
    }
}

// Preview management
function updatePreview() {
    const preview = document.getElementById("certificate-preview");
    preview.innerHTML = "";

    elements.forEach((element) => {
        const div = document.createElement("div");
        div.className = "element";
        div.dataset.id = element.id;

        if (element.type === "qrcode") {
            div.className += " element-qrcode";
            div.innerHTML = `<img src="/storage/preview-sample.svg" style="width: ${element.width}px; height: ${element.height}px;">`;
        } else if (element.type === "text") {
            div.style.fontSize = element.fontSize + "px";
            div.style.fontFamily = element.fontFamily;
            div.style.fontWeight = element.fontWeight;
            div.style.fontStyle = element.fontStyle;
            div.style.textAlign = element.textAlign;
            div.textContent = element.text;
        }

        div.style.left = element.x + "px";
        div.style.top = element.y + "px";

        // Make element draggable
        div.draggable = true;
        div.addEventListener("mousedown", startDragging);

        preview.appendChild(div);
    });

    updateElementsList();
}

function startDragging(e) {
    e.preventDefault();

    const element = e.target.closest(".element");
    if (!element) return;

    draggedElement = element;
    selectedElement = element;

    const rect = element.getBoundingClientRect();
    const transform = new WebKitCSSMatrix(
        window.getComputedStyle(element).transform
    );

    offsetX = e.clientX - (rect.left + transform.m41);
    offsetY = e.clientY - (rect.top + transform.m42);

    // Update selection state
    document
        .querySelectorAll(".element")
        .forEach((el) => el.classList.remove("selected"));
    element.classList.add("selected");
}

function handleMouseMove(e) {
    if (!draggedElement) return;

    const preview = document.getElementById("certificate-preview");
    const rect = preview.getBoundingClientRect();

    const x = e.clientX - rect.left - offsetX;
    const y = e.clientY - rect.top - offsetY;

    draggedElement.style.left = x + "px";
    draggedElement.style.top = y + "px";

    const elementId = draggedElement.dataset.id;
    const element = elements.find((el) => el.id === elementId);
    if (element) {
        element.x = x;
        element.y = y;
    }
}

function handleMouseUp() {
    draggedElement = null;
}

function updateElementsList() {
    const list = document.getElementById("elementsList");
    list.innerHTML = "";

    elements.forEach((element, index) => {
        const div = document.createElement("div");
        div.className = "element-item";
        div.innerHTML = `
            <span>${
                element.type === "text" ? element.text : element.type
            }</span>
            <div class="element-actions">
                <button onclick="editElement(${index})" class="button-edit">Edit</button>
                <button onclick="removeElement(${index})" class="button-delete">Hapus</button>
            </div>
        `;
        list.appendChild(div);
    });
}

function removeElement(index) {
    if (confirm("Apakah Anda yakin ingin menghapus elemen ini?")) {
        elements.splice(index, 1);
        updatePreview();
    }
}

// Initialize everything
document.addEventListener("DOMContentLoaded", function () {
    // Set up event listeners
    document.addEventListener("mousemove", handleMouseMove);
    document.addEventListener("mouseup", handleMouseUp);

    // Set up initial element type
    toggleOptions();
});
