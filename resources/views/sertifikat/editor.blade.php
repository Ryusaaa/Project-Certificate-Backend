<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editor Sertifikat</title>
    <style>
        :root {
            --primary-color: #3498db;
            --danger-color: #e74c3c;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --bg-color: #f5f6fa;
            --border-color: #dcdde1;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--bg-color);
            color: #333;
            line-height: 1.6;
        }

        .container {
            display: flex;
            padding: 20px;
            gap: 20px;
            max-width: 1600px;
            margin: 0 auto;
            height: 100vh;
        }

        .sidebar {
            width: 320px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            height: calc(100vh - 40px);
            overflow-y: auto;
        }

        .preview-area {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            height: calc(100vh - 40px);
            overflow: auto;
            display: flex;
            flex-direction: column;
        }

        .preview-container {
            flex: 1;
            background-color: #fff;
            position: relative;
            width: 842px;
            height: 595px;
            margin: 0 auto;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            background-image: linear-gradient(45deg, #f1f1f1 25%, transparent 25%),
                            linear-gradient(-45deg, #f1f1f1 25%, transparent 25%),
                            linear-gradient(45deg, transparent 75%, #f1f1f1 75%),
                            linear-gradient(-45deg, transparent 75%, #f1f1f1 75%);
            background-size: 20px 20px;
            background-position: 0 0, 0 10px, 10px -10px, -10px 0px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .preview-container.has-bg {
            background-size: contain;
            background-position: center;
            background-repeat: no-repeat;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2d3436;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .file-input-wrapper {
            transition: all 0.3s ease;
        }

        .file-input-wrapper:hover {
            border-color: var(--primary-color) !important;
            background: #fff !important;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .file-input-container {
            position: relative;
            overflow: hidden;
            display: inline-block;
            cursor: pointer;
        }

        .file-input-container input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .file-input-button {
            display: inline-block;
            padding: 8px 16px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .file-input-button:hover {
            background-color: #2980b9;
        }

        .file-name {
            margin-left: 10px;
            font-size: 14px;
            color: #666;
        }

        .element {
            position: absolute;
            cursor: move;
            padding: 4px;
            border: 1px solid transparent;
            min-width: 100px;
            white-space: nowrap;
        }
        
        .element[data-text-align="center"] {
            transform: translateX(-50%);
            text-align: center;
        }
        
        .element[data-text-align="right"] {
            transform: translateX(-100%);
            text-align: right;
        }

        .element:hover {
            border: 1px dashed #3498db;
        }

        .element.selected {
            border: 1px solid #3498db;
        }

        .placeholder-label {
            position: absolute;
            top: -20px;
            left: 0;
            font-size: 12px;
            background: rgba(52, 152, 219, 0.9);
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
        }

        .element-list {
            margin-top: 20px;
            border-top: 1px solid var(--border-color);
            padding-top: 20px;
        }

        .element-list h3 {
            margin-bottom: 10px;
            color: #2d3436;
        }

        .element-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-bottom: 8px;
        }

        .element-item span {
            font-size: 14px;
            color: #2d3436;
        }

        .element-item button {
            padding: 4px 8px;
            margin-left: 8px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
            background-color: var(--primary-color);
            color: white;
        }

        .element-item .button-delete {
            background-color: var(--danger-color);
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        button {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            background-color: var(--primary-color);
            color: white;
            transition: background-color 0.3s;
        }

        button:hover {
            opacity: 0.9;
        }

        button.save-btn {
            background-color: var(--success-color);
        }

        button.preview-btn {
            background-color: var(--warning-color);
        }

        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 24px;
            border-radius: 5px;
            background-color: var(--success-color);
            color: white;
            z-index: 1000;
            animation: fadeIn 0.3s ease-out;
        }

        .notification.error {
            background-color: var(--danger-color);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-20px); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
            margin: 20px 0 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--primary-color);
            color: #2d3436;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="form-group">
                <label for="templateName">Nama Template</label>
                <input type="text" id="templateName" placeholder="Masukkan nama template">
            </div>

            <div class="form-group">
                <label for="backgroundFile">Background Sertifikat</label>
                <div class="file-input-wrapper" style="border: 2px dashed var(--border-color); padding: 20px; border-radius: 8px; text-align: center; background: #f8f9fa; transition: all 0.3s ease;">
                    <div class="file-input-container" style="margin-bottom: 10px; position: relative;">
                        <label for="backgroundFile" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; padding: 10px 20px; background-color: var(--primary-color); color: white; border-radius: 5px;">
                            <span style="font-size: 24px;">📁</span>
                            <span>Pilih File</span>
                        </label>
                        <input type="file" 
                               id="backgroundFile" 
                               name="background_image"
                               accept="image/jpeg,image/png,image/gif" 
                               onchange="handleFileSelect(event)" 
                               style="display: none;">
                    </div>
                    <div class="file-info">
                        <div class="file-name-wrapper" style="display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">
                            <span class="file-icon" style="margin-right: 8px; color: var(--primary-color);">📄</span>
                            <span class="file-name" style="font-size: 14px;">Belum ada file yang dipilih</span>
                        </div>
                        <button onclick="handleUploadBackground()" id="uploadButton" style="display: none; margin: 10px auto;" class="save-btn">
                            <i class="fas fa-upload" style="margin-right: 8px;"></i>Upload Background
                        </button>
                        <div class="upload-status" style="display: flex; align-items: center; justify-content: center; margin-top: 8px;">
                            <div id="uploadProgress" style="display: none; width: 20px; height: 20px; border: 2px solid var(--primary-color); border-top-color: transparent; border-radius: 50%; margin-right: 8px; animation: spin 1s linear infinite;"></div>
                            <span id="uploadStatus" style="font-size: 14px; color: #666;"></span>
                        </div>
                    </div>
                    <div class="file-requirements" style="margin-top: 12px; padding: 8px; background: rgba(52, 152, 219, 0.1); border-radius: 4px;">
                        <div style="font-size: 12px; color: #666; margin-bottom: 4px;">📌 Persyaratan File:</div>
                        <div style="font-size: 12px; color: #666;">• Format: JPG, PNG, atau GIF</div>
                        <div style="font-size: 12px; color: #666;">• Ukuran maksimal: 2MB</div>
                    </div>
                </div>
            </div>

            <div class="section-title">Tambah Elemen</div>

            <div class="form-group">
                <label for="elementType">Tipe Elemen</label>
                <select id="elementType" onchange="toggleOptions()">
                    <option value="text">Teks</option>
                    <option value="image">Gambar</option>
                </select>
            </div>

            <div id="textOptions">
                <div class="form-group">
                    <label for="placeholderType">Tipe Placeholder</label>
                    <select id="placeholderType" onchange="updatePlaceholderText()">
                        <option value="custom">Teks Kustom</option>
                        <option value="name">Nama Peserta</option>
                        <option value="number">Nomor Sertifikat</option>
                        <option value="date">Tanggal</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="elementText">Teks</label>
                    <input type="text" id="elementText" placeholder="Masukkan teks">
                </div>

                <div class="form-group">
                    <label for="fontSize">Ukuran Font (px)</label>
                    <input type="number" id="fontSize" value="16" min="8" max="72">
                </div>

                <div class="form-group">
                    <label for="fontFamily">Font</label>
                    <select id="fontFamily">
                        <option value="Arial">Arial</option>
                        <option value="Times New Roman">Times New Roman</option>
                        <option value="Calibri">Calibri</option>
                        <option value="Helvetica">Helvetica</option>
                        <option value="Verdana">Verdana</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="textAlign">Perataan Teks</label>
                    <select id="textAlign">
                        <option value="left">Kiri</option>
                        <option value="center">Tengah</option>
                        <option value="right">Kanan</option>
                    </select>
                </div>
            </div>

            <div id="imageOptions" style="display: none;">
                <div class="form-group">
                    <label for="imageUrl">URL Gambar</label>
                    <input type="text" id="imageUrl" placeholder="Masukkan URL gambar">
                </div>

                <div class="form-group">
                    <label for="imageWidth">Lebar (px)</label>
                    <input type="number" id="imageWidth" value="100" min="10">
                </div>

                <div class="form-group">
                    <label for="imageHeight">Tinggi (px)</label>
                    <input type="number" id="imageHeight" value="100" min="10">
                </div>
            </div>

            <button onclick="addElement()" class="save-btn">Tambah Elemen</button>

            <div class="element-list">
                <h3>Daftar Elemen</h3>
                <div id="elementsList"></div>
            </div>
        </div>

        <div class="preview-area">
            <div class="preview-container" id="certificate-preview">
                <div id="preview-message" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; background: rgba(255, 255, 255, 0.9); padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
                    <div style="font-size: 48px; margin-bottom: 10px;">📄</div>
                    <div style="color: #666; font-size: 16px; font-weight: 500;">Upload background sertifikat terlebih dahulu</div>
                    <div style="color: #999; font-size: 14px; margin-top: 5px;">Format yang didukung: JPG, PNG, GIF</div>
                </div>
            </div>
            <div class="button-group">
                <button onclick="saveTemplate()" class="save-btn">Simpan Template</button>
                <button onclick="generatePDF()" class="preview-btn">Preview PDF</button>
            </div>
        </div>
    </div>

    <script>
        let elements = [];
        let selectedElement = null;
        let draggedElement = null;
        let offsetX = 0;
        let offsetY = 0;

        function showNotification(message, type = 'success') {
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notification => notification.remove());

            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'fadeOut 0.3s ease-out forwards';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        function handleFileSelect(event) {
            const fileInput = event.target;
            const fileNameWrapper = document.querySelector('.file-name-wrapper');
            const fileNameSpan = document.querySelector('.file-name');
            const uploadButton = document.getElementById('uploadButton');
            const fileIcon = document.querySelector('.file-icon');
            const file = fileInput.files[0];
            const uploadStatus = document.getElementById('uploadStatus');
            const uploadProgress = document.getElementById('uploadProgress');

            // Reset all states first
            fileNameSpan.textContent = 'Belum ada file yang dipilih';
            fileIcon.textContent = '📄';
            fileNameWrapper.style.color = '#666';
            uploadButton.style.display = 'none';
            uploadStatus.textContent = '';
            uploadProgress.style.display = 'none';
            fileInput.classList.remove('is-invalid');

            if (!file) {
                return;
            }

            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                showNotification('Format file harus JPG, PNG, atau GIF', 'error');
                fileInput.value = '';
                fileInput.classList.add('is-invalid');
                return;
            }

            // Validate file size (max 2MB)
            if (file.size > 2 * 1024 * 1024) {
                showNotification('Ukuran file maksimal 2MB', 'error');
                fileInput.value = '';
                fileInput.classList.add('is-invalid');
                return;
            }

            // Create and validate image object
            const img = new Image();
            img.onload = function() {
                URL.revokeObjectURL(img.src);
                
                // Update UI for valid image
                fileNameSpan.textContent = file.name;
                fileIcon.textContent = '🖼️';
                fileNameWrapper.style.color = '#2d3436';
                uploadButton.style.display = 'inline-block';
                uploadButton.style.opacity = '1';
            };

            img.onerror = function() {
                URL.revokeObjectURL(img.src);
                showNotification('File bukan gambar yang valid', 'error');
                fileInput.value = '';
                fileInput.classList.add('is-invalid');
            };

            img.src = URL.createObjectURL(file);
        }

        async function handleUploadBackground() {
            try {
                const fileInput = document.getElementById('backgroundFile');
                const file = fileInput.files[0];
                const preview = document.getElementById('certificate-preview');
                const uploadStatus = document.getElementById('uploadStatus');
                const uploadProgress = document.getElementById('uploadProgress');
                const uploadButton = document.getElementById('uploadButton');

                if (!file) {
                    showNotification('Pilih file terlebih dahulu', 'error');
                    return;
                }

                // Disable the upload button and show progress
                uploadButton.disabled = true;
                uploadStatus.textContent = 'Mengupload...';
                uploadProgress.style.display = 'block';

                // Create FormData and append file
                const formData = new FormData();
                formData.append('background_image', file);
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                // Upload the file
                const response = await fetch('/sertifikat-templates/upload-image', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    const result = await response.json();
                    throw new Error(result.message || 'Upload gagal');
                }

                const result = await response.json();

                // Create a new image object to verify the uploaded image
                const img = new Image();
                img.onload = function() {
                    // Update preview with the new background
                    preview.style.cssText = `
                        background-image: url("${result.url}") !important;
                        background-size: contain !important;
                        background-position: center !important;
                        background-repeat: no-repeat !important;
                    `;
                    preview.dataset.backgroundImage = result.url;
                    preview.classList.add('has-bg');

                    // Remove preview message if it exists
                    const message = document.getElementById('preview-message');
                    if (message) {
                        message.remove();
                    }

                    // Reset UI states
                    uploadStatus.textContent = 'Upload selesai';
                    uploadProgress.style.display = 'none';
                    uploadButton.style.display = 'none';
                    uploadButton.disabled = false;
                    fileInput.value = '';
                    document.querySelector('.file-name').textContent = 'Belum ada file yang dipilih';
                    document.querySelector('.file-icon').textContent = '📄';

                    showNotification('Background berhasil diupload');
                };

                img.onerror = function() {
                    throw new Error('Gambar yang diupload tidak valid');
                };

                img.src = result.url;

            } catch (error) {
                console.error('Upload error:', error);
                uploadStatus.textContent = 'Upload gagal';
                uploadProgress.style.display = 'none';
                uploadButton.disabled = false;
                showNotification(error.message || 'Gagal mengupload background', 'error');
                
                // Reset preview if upload failed
                preview.style.backgroundImage = '';
                preview.classList.remove('has-bg');
                delete preview.dataset.backgroundImage;
            }
        }

        function toggleOptions() {
            const type = document.getElementById('elementType').value;
            document.getElementById('textOptions').style.display = type === 'text' ? 'block' : 'none';
            document.getElementById('imageOptions').style.display = type === 'image' ? 'block' : 'none';
        }

        function updatePlaceholderText() {
            const type = document.getElementById('placeholderType').value;
            const input = document.getElementById('elementText');
            
            if (type === 'custom') {
                input.value = '';
                input.removeAttribute('readonly');
            } else {
                const placeholders = {
                    name: '{NAMA}',
                    number: '{NOMOR}',
                    date: '{TANGGAL}'
                };
                input.value = placeholders[type] || '';
                input.setAttribute('readonly', 'readonly');
            }
        }

        function addElement() {
            const type = document.getElementById('elementType').value;
            const element = {
                id: 'element-' + Date.now(),
                type: type,
                x: Math.round(842 / 2 - 50),
                y: Math.round(595 / 2 - 20)
            };

            if (type === 'text') {
                const placeholderType = document.getElementById('placeholderType').value;
                const text = document.getElementById('elementText').value;
                
                if (!text && placeholderType === 'custom') {
                    showNotification('Harap masukkan teks untuk elemen', 'error');
                    return;
                }

                element.text = text;
                element.fontSize = parseInt(document.getElementById('fontSize').value) || 16;
                element.fontFamily = document.getElementById('fontFamily').value;
                element.textAlign = document.getElementById('textAlign').value;
                element.placeholderType = placeholderType;
            } else {
                const imageUrl = document.getElementById('imageUrl').value;
                if (!imageUrl) {
                    showNotification('Harap masukkan URL gambar', 'error');
                    return;
                }

                element.imageUrl = imageUrl;
                element.width = parseInt(document.getElementById('imageWidth').value) || 100;
                element.height = parseInt(document.getElementById('imageHeight').value) || 100;
            }

            elements.push(element);
            updatePreview();
            document.getElementById('elementText').value = '';
            document.getElementById('imageUrl').value = '';
            showNotification('Elemen berhasil ditambahkan');
        }

        function updatePreview() {
            const preview = document.getElementById('certificate-preview');
            const hasBackground = preview.classList.contains('has-bg');
            preview.innerHTML = '';

            if (!hasBackground) {
                const message = document.createElement('div');
                message.id = 'preview-message';
                message.style.cssText = 'position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #666;';
                message.textContent = 'Upload background sertifikat terlebih dahulu';
                preview.appendChild(message);
            }

            // Create a container for elements that maintains aspect ratio
            const elementContainer = document.createElement('div');
            elementContainer.style.cssText = `
                position: absolute;
                width: 842px;
                height: 595px;
                transform-origin: center;
                transform: scale(${preview.offsetWidth / 842});
            `;
            preview.appendChild(elementContainer);

            elements.forEach(element => {
                const div = document.createElement('div');
                div.className = 'element';
                div.dataset.id = element.id;
                div.style.position = 'absolute';
                div.style.left = element.x + 'px';
                div.style.top = element.y + 'px';

                if (element.type === 'text') {
                    div.style.fontSize = element.fontSize + 'px';
                    div.style.fontFamily = element.fontFamily;
                    div.dataset.textAlign = element.textAlign;
                    div.textContent = element.text;

                    if (element.placeholderType !== 'custom') {
                        const label = document.createElement('div');
                        label.className = 'placeholder-label';
                        label.textContent = element.placeholderType.toUpperCase();
                        div.appendChild(label);
                    }
                } else {
                    const img = document.createElement('img');
                    img.src = element.imageUrl;
                    img.style.width = element.width + 'px';
                    img.style.height = element.height + 'px';
                    div.appendChild(img);
                }

                div.addEventListener('mousedown', startDragging);
                elementContainer.appendChild(div);
            });

            updateElementsList();
        }

        function startDragging(e) {
            e.preventDefault();
            draggedElement = e.target.closest('.element');
            const rect = draggedElement.getBoundingClientRect();
            offsetX = e.clientX - rect.left;
            offsetY = e.clientY - rect.top;
            
            document.querySelectorAll('.element').forEach(el => el.classList.remove('selected'));
            draggedElement.classList.add('selected');
        }

        document.addEventListener('mousemove', function(e) {
            if (!draggedElement) return;

            const preview = document.getElementById('certificate-preview');
            const rect = preview.getBoundingClientRect();
            const textAlign = draggedElement.dataset.textAlign;

            let x = (e.clientX - rect.left - offsetX);
            let y = (e.clientY - rect.top - offsetY);
            
            // Adjust x position based on text alignment
            if (textAlign === 'center') {
                x += draggedElement.offsetWidth / 2;
            } else if (textAlign === 'right') {
                x += draggedElement.offsetWidth;
            }
            
            x = Math.max(0, Math.min(x, 842 - (textAlign === 'left' ? draggedElement.offsetWidth : 0)));
            y = Math.max(0, Math.min(y, 595 - draggedElement.offsetHeight));
            
            draggedElement.style.left = x + 'px';
            draggedElement.style.top = y + 'px';
            
            const element = elements.find(el => el.id === draggedElement.dataset.id);
            if (element) {
                element.x = x;
                element.y = y;
            }
        });

        document.addEventListener('mouseup', function() {
            if (draggedElement) {
                draggedElement.classList.remove('selected');
                draggedElement = null;
            }
        });

        function updateElementsList() {
            const list = document.getElementById('elementsList');
            list.innerHTML = '';

            elements.forEach((element, index) => {
                const div = document.createElement('div');
                div.className = 'element-item';
                
                const text = document.createElement('span');
                text.textContent = element.type === 'text' ? 
                    `Teks: ${element.text.substring(0, 20)}${element.text.length > 20 ? '...' : ''}` : 
                    'Gambar';
                
                const buttons = document.createElement('div');
                
                const editBtn = document.createElement('button');
                editBtn.textContent = 'Edit';
                editBtn.onclick = () => editElement(index);
                
                const deleteBtn = document.createElement('button');
                deleteBtn.textContent = 'Hapus';
                deleteBtn.className = 'button-delete';
                deleteBtn.onclick = () => removeElement(index);
                
                buttons.appendChild(editBtn);
                buttons.appendChild(deleteBtn);
                
                div.appendChild(text);
                div.appendChild(buttons);
                list.appendChild(div);
            });
        }

        function removeElement(index) {
            if (confirm('Apakah Anda yakin ingin menghapus elemen ini?')) {
                elements.splice(index, 1);
                updatePreview();
                showNotification('Elemen berhasil dihapus');
            }
        }

        function editElement(index) {
            const element = elements[index];
            document.getElementById('elementType').value = element.type;
            toggleOptions();

            if (element.type === 'text') {
                document.getElementById('placeholderType').value = element.placeholderType || 'custom';
                document.getElementById('elementText').value = element.text;
                document.getElementById('fontSize').value = element.fontSize;
                document.getElementById('fontFamily').value = element.fontFamily;
                document.getElementById('textAlign').value = element.textAlign;
            } else {
                document.getElementById('imageUrl').value = element.imageUrl;
                document.getElementById('imageWidth').value = element.width;
                document.getElementById('imageHeight').value = element.height;
            }

            elements.splice(index, 1);
            updatePreview();
            showNotification('Elemen siap untuk diedit');
        }

        async function saveTemplate() {
            try {
                const preview = document.getElementById('certificate-preview');
                const templateName = document.getElementById('templateName').value;
                if (!templateName) {
                    showNotification('Harap masukkan nama template', 'error');
                    return;
                }
                if (!preview.classList.contains('has-bg')) {
                    showNotification('Harap upload background terlebih dahulu', 'error');
                    return;
                }
                if (elements.length === 0) {
                    showNotification('Harap tambahkan minimal satu elemen', 'error');
                    return;
                }
                showNotification('Menyimpan template...');

                // Get background image URL from preview
                const backgroundImage = preview.dataset.backgroundImage;

                const data = {
                    name: templateName,
                    background_image: backgroundImage,
                    elements: elements.map(el => ({
                        type: el.type,
                        x: el.x,
                        y: el.y,
                        ...(el.type === 'text' ? {
                            text: el.text,
                            fontSize: el.fontSize,
                            fontFamily: el.fontFamily,
                            textAlign: el.textAlign,
                            placeholderType: el.placeholderType
                        } : {
                            imageUrl: el.imageUrl,
                            width: el.width,
                            height: el.height
                        })
                    })),
                    _token: document.querySelector('meta[name="csrf-token"]').content
                };

                const response = await fetch('/sertifikat-templates', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (!response.ok) {
                    throw new Error(result.message || 'Gagal menyimpan template');
                }
                showNotification('Template berhasil disimpan');
                document.getElementById('templateName').value = '';
                elements = [];
                updatePreview();
            } catch (error) {
                console.error('Save template error:', error);
                showNotification(error.message || 'Gagal menyimpan template', 'error');
            }
        }

        async function generatePDF() {
            try {
                const preview = document.getElementById('certificate-preview');
                const templateName = document.getElementById('templateName').value;
                if (!templateName) {
                    showNotification('Harap masukkan nama template', 'error');
                    return;
                }
                if (!preview.classList.contains('has-bg')) {
                    showNotification('Harap upload background terlebih dahulu', 'error');
                    return;
                }
                if (elements.length === 0) {
                    showNotification('Harap tambahkan minimal satu elemen', 'error');
                    return;
                }
                showNotification('Membuat preview PDF...');

                // Get background image URL from preview
                const backgroundImage = preview.dataset.backgroundImage;

                const data = {
                    name: templateName,
                    background_image: backgroundImage,
                    elements: elements.map(el => ({
                        type: el.type,
                        x: el.x,
                        y: el.y,
                        ...(el.type === 'text' ? {
                            text: el.text,
                            fontSize: el.fontSize,
                            fontFamily: el.fontFamily,
                            textAlign: el.textAlign,
                            placeholderType: el.placeholderType
                        } : {
                            imageUrl: el.imageUrl,
                            width: el.width,
                            height: el.height
                        })
                    })),
                    _token: document.querySelector('meta[name="csrf-token"]').content
                };

                const response = await fetch('/sertifikat-templates/generate-pdf', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Gagal membuat preview PDF');
                }
                const blob = await response.blob();
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `sertifikat-${templateName}-preview.pdf`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                showNotification('Preview PDF berhasil dibuat');
            } catch (error) {
                console.error('Generate PDF error:', error);
                showNotification(error.message || 'Gagal membuat preview PDF', 'error');
            }
        }
    </script>
</body>
</html>
