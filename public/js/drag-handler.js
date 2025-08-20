// Drag handling functions
function startDragging(e) {
    e.preventDefault();
    e.stopPropagation();

    const element = e.target.closest(".element");
    if (!element) return;

    isDragging = true;
    draggedElement = element;
    selectedElement = element;

    const previewContainer = document.getElementById("certificate-preview");
    const containerRect = previewContainer.getBoundingClientRect();
    const scale = getPreviewScale();

    // Get current element position
    const style = window.getComputedStyle(element);
    currentX = parseFloat(style.left) || 0;
    currentY = parseFloat(style.top) || 0;

    // Calculate initial mouse position relative to the container
    initialX = (e.clientX - containerRect.left) / scale;
    initialY = (e.clientY - containerRect.top) / scale;

    // Calculate offset from the element's top-left corner
    xOffset = initialX - currentX;
    yOffset = initialY - currentY;

    // Update selection state
    document
        .querySelectorAll(".element")
        .forEach((el) => el.classList.remove("selected"));
    element.classList.add("selected");

    // Set element's z-index to bring it to front
    element.style.zIndex = getHighestZIndex() + 1;

    // Set pointer capture
    element.setPointerCapture(e.pointerId);
}

function handleElementDrag(e) {
    if (!isDragging || !draggedElement) return;

    e.preventDefault();

    const previewContainer = document.getElementById("certificate-preview");
    const containerRect = previewContainer.getBoundingClientRect();
    const scale = getPreviewScale();

    // Calculate new position
    let newX = (e.clientX - containerRect.left) / scale - xOffset;
    let newY = (e.clientY - containerRect.top) / scale - yOffset;

    // Constrain to preview bounds (A4 dimensions)
    newX = Math.max(0, Math.min(newX, 842 - draggedElement.offsetWidth));
    newY = Math.max(0, Math.min(newY, 595 - draggedElement.offsetHeight));

    // Update element position
    draggedElement.style.left = `${newX}px`;
    draggedElement.style.top = `${newY}px`;

    // Update element data
    const elementId = draggedElement.id;
    const element = elements.find((el) => el.id === elementId);
    if (element) {
        element.x = newX;
        element.y = newY;
    }
}

function getHighestZIndex() {
    const elements = document.querySelectorAll(".element");
    let highest = 0;

    elements.forEach((el) => {
        const zIndex = parseInt(window.getComputedStyle(el).zIndex) || 0;
        highest = Math.max(highest, zIndex);
    });

    return highest;
}

// Initialize preview scale on load
document.addEventListener("DOMContentLoaded", function () {
    updatePreviewScale();
});
