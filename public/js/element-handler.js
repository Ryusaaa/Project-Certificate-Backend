// Function to render all elements on the certificate
function renderElements() {
    const previewContainer = document.getElementById("certificate-preview");

    // Clear existing elements except preview message
    const existingElements = previewContainer.querySelectorAll(
        ".element:not(#preview-message)"
    );
    existingElements.forEach((el) => el.remove());

    // Render each element
    elements.forEach((element, index) => {
        const elementDiv = document.createElement("div");
        elementDiv.className = `element element-${element.type}`;
        elementDiv.id = element.id;
        elementDiv.dataset.type = element.type;

        // Set position with transform and z-index
        elementDiv.style.position = "absolute";
        elementDiv.style.transform = `translate(${element.x}px, ${element.y}px)`;
        elementDiv.style.zIndex = index + 1;

        // Set element specific styles and content
        if (element.type === "text") {
            const textContent = getDisplayText(element);
            elementDiv.innerHTML = textContent;

            // Apply text styles
            elementDiv.style.fontFamily = element.fontFamily || "Arial";
            elementDiv.style.fontSize = `${element.fontSize || 16}px`;
            elementDiv.style.fontWeight = element.fontWeight || "400";
            elementDiv.style.fontStyle = element.fontStyle;
            elementDiv.style.textAlign = element.textAlign;
            elementDiv.style.color = element.color || "#000000";

            // Store original text for editing
            elementDiv.dataset.originalText = element.text;
            elementDiv.dataset.placeholderType = element.placeholderType;
        } else if (element.type === "qrcode") {
            elementDiv.style.width = `${element.width}pt`;
            elementDiv.style.height = `${element.height}pt`;
            elementDiv.innerHTML = `<div class="qrcode-placeholder"></div>`;
        }

        // Add draggable functionality
        elementDiv.addEventListener("mousedown", startDragging);
        elementDiv.addEventListener("click", selectElement);

        previewContainer.appendChild(elementDiv);
    });

    // Update elements list in sidebar
    updateElementsList();
}

// Helper function to get display text for placeholders
function getDisplayText(element) {
    if (element.placeholderType === "custom") {
        return element.text;
    }

    // Get preview text from the preview inputs
    const previewTexts = {
        nama: document.getElementById("previewName")?.value || "Nama Peserta",
        nomor: document.getElementById("previewNumber")?.value || "CERT-001",
        tanggal:
            document.getElementById("previewDate")?.value || "19 Agustus 2025",
        instruktur:
            document.getElementById("previewInstructor")?.value ||
            "Nama Instruktur",
    };

    return previewTexts[element.placeholderType] || element.text;
}

// Function to update elements list in sidebar
function updateElementsList() {
    const listContainer = document.getElementById("elementsList");
    listContainer.innerHTML = "";

    elements.forEach((element, index) => {
        const elementItem = document.createElement("div");
        elementItem.className = "element-item";
        elementItem.innerHTML = `
            <div>
                <i class="fas ${
                    element.type === "text" ? "fa-font" : "fa-qrcode"
                }"></i>
                ${
                    element.type === "text"
                        ? element.text.substring(0, 20) +
                          (element.text.length > 20 ? "..." : "")
                        : "QR Code"
                }
            </div>
            <div>
                <button onclick="editElement('${
                    element.id
                }')" class="button" style="padding: 4px 8px; margin-right: 4px;">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteElement('${
                    element.id
                }')" class="button danger" style="padding: 4px 8px;">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        listContainer.appendChild(elementItem);
    });
}

// Function to start dragging an element
function startDragging(e) {
    if (e.target.classList.contains("element")) {
        draggedElement = e.target;
        const rect = draggedElement.getBoundingClientRect();
        offsetX = e.clientX - rect.left;
        offsetY = e.clientY - rect.top;
        draggedElement.classList.add("dragging");
    }
}

// Function to handle element dragging
function handleElementDrag(e) {
    if (!draggedElement) return;

    e.preventDefault();

    const previewContainer = document.getElementById("certificate-preview");
    const containerRect = previewContainer.getBoundingClientRect();
    const scale = previewContainer.offsetWidth / 842; // A4 width

    // Calculate new position considering container scale
    let x = (e.clientX - containerRect.left) / scale - offsetX;
    let y = (e.clientY - containerRect.top) / scale - offsetY;

    // Constrain to preview bounds (A4 dimensions)
    x = Math.max(0, Math.min(x, 842 - draggedElement.offsetWidth));
    y = Math.max(0, Math.min(y, 595 - draggedElement.offsetHeight));

    // Update element position with transform
    draggedElement.style.left = `${x}px`;
    draggedElement.style.top = `${y}px`;

    // Update element data
    const elementId = draggedElement.id;
    const element = elements.find((el) => el.id === elementId);
    if (element) {
        element.x = x;
        element.y = y;
    }
}

// Function to select an element
function selectElement(e) {
    const element = e.target.closest(".element");
    if (!element) return;

    // Deselect previously selected element
    document.querySelectorAll(".element.selected").forEach((el) => {
        el.classList.remove("selected");
    });

    // Select new element
    element.classList.add("selected");
    selectedElement = element;
}

// Function to edit an element
function editElement(elementId) {
    const element = elements.find((el) => el.id === elementId);
    if (!element) return;

    // Select the element in the preview
    const elementDiv = document.getElementById(elementId);
    if (elementDiv) {
        selectElement({ target: elementDiv });
    }

    // Populate form fields based on element type
    document.getElementById("elementType").value = element.type;
    toggleOptions();

    if (element.type === "text") {
        document.getElementById("placeholderType").value =
            element.placeholderType;
        document.getElementById("elementText").value = element.text;
        document.getElementById("fontSize").value = element.fontSize;
        document.getElementById("fontFamily").value = element.fontFamily;
        document.getElementById("fontWeight").value = element.fontWeight;
        document.getElementById("fontStyle").value = element.fontStyle;
        document.getElementById("textAlign").value = element.textAlign;
    } else if (element.type === "qrcode") {
        document.getElementById("qrcodeSize").value = element.width;
    }
}

// Function to delete an element
function deleteElement(elementId) {
    const index = elements.findIndex((el) => el.id === elementId);
    if (index !== -1) {
        elements.splice(index, 1);
        renderElements();
        showNotification("Elemen berhasil dihapus");
    }
}
