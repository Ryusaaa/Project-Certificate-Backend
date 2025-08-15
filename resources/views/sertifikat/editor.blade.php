<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Editor Sertifikat</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Alice&family=Allura&family=Anonymous+Pro:wght@400;700&family=Anton&family=Arapey:ital@0;1&family=Archivo+Black&family=Arimo:wght@400;500;600;700&family=Barlow:wght@400;500;600;700&family=Bebas+Neue&family=Belleza&family=Bree+Serif&family=Chewy&family=Cormorant+Garamond:wght@400;500;600;700&family=DM+Sans:wght@400;500;700&family=DM+Serif+Display&family=Forum&family=Great+Vibes&family=Hammersmith+One&family=Inria+Serif:wght@400;700&family=Inter:wght@400;500;600;700&family=League+Gothic&family=League+Spartan:wght@400;500;600;700&family=Libre+Baskerville:wght@400;700&family=Lora:wght@400;500;600;700&family=Merriweather:wght@400;700&family=Montserrat:wght@400;500;600;700&family=Nunito:wght@400;500;600;700&family=Open+Sans:wght@400;500;600;700&family=Oswald:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&family=Questrial&family=Quicksand:wght@400;500;600;700&family=Raleway:wght@400;500;600;700&family=Roboto:wght@400;500;700&family=Shrikhand&family=Tenor+Sans&family=Yeseva+One&display=swap" rel="stylesheet">
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
            overflow-y: auto;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .preview-container {
            flex: none;
            background-color: #fff;
            position: relative;
            width: 842px;
            height: 595px;
            margin: 20px auto;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
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
            background-size: 100% 100%;
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

    /* Penyesuaian umum untuk elemen */
        .element {
            position: absolute;
            cursor: move;
            border: 1px solid transparent;
            white-space: nowrap;
            font-size: inherit;
            line-height: 1.2;
            display: inline-block;
            box-sizing: content-box;
        }

        /* Styling untuk elemen yang sedang dihover atau dipilih */
        .element:hover {
            border-color: rgba(52, 152, 219, 0.5);
        }
        
        .element.selected {
            border-color: #3498db;
        }

        /* Container untuk teks di dalam elemen */
        .element p {
            position: absolute;
            margin: 0;
            padding: 0;
            white-space: nowrap;
            width: fit-content;
        }

/* Penyesuaian untuk elemen dengan text-align center */
    .element[data-text-align="center"] {
        transform: translateX(-50%);
        text-align: center;
}

/* Penyesuaian untuk elemen dengan text-align right */
    .element[data-text-align="right"] {
        transform: translateX(-100%);
        text-align: right;
}

/* Penyesuaian untuk gambar di dalam elemen */
    .element img {
        display: block;
        position: relative;
        margin: 0;
        padding: 0;
}/* Penyesuaian khusus untuk editor */
    .editor .element {
        /* Penyesuaian posisi */
        transform: translateY(50%); /* Selaraskan dengan template */
    
        /* Penyesuaian padding/margin */
        padding: 0;
        margin: 0;
}

/* Penyesuaian untuk elemen tertentu */
    .editor .element[data-name="nama"] {
        transform: translate(-30px, 15px); /* Contoh penyesuaian untuk elemen nama */
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
        {{-- ========================================================== --}}
        {{-- PERUBAHAN 1: Tambahkan input tersembunyi untuk merchant_id --}}
        {{-- Pastikan Anda mengirimkan variabel $merchant_id dari controller --}}
        {{-- ========================================================== --}}
        <input type="hidden" id="merchantId" value="1">

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
                    <select id="fontFamily" style="font-family: var(--selected-font)">
                        <optgroup label="System Fonts">
                            <option value="Arial" style="font-family: Arial">Arial</option>
                            <option value="Times New Roman" style="font-family: 'Times New Roman'">Times New Roman</option>
                            <option value="Helvetica" style="font-family: Helvetica">Helvetica</option>
                            <option value="Georgia" style="font-family: Georgia">Georgia</option>
                        </optgroup>
                        <optgroup label="Custom Fonts">
                            @php
                                // Generate options from public/fonts folders. Value = folder name, label = folder with spaces
                                $fontsDir = public_path('fonts');
                                if (is_dir($fontsDir)) {
                                    $folders = array_filter(scandir($fontsDir), function($d) use ($fontsDir){
                                        return $d !== '.' && $d !== '..' && is_dir($fontsDir . DIRECTORY_SEPARATOR . $d);
                                    });
                                    foreach ($folders as $folder) {
                                        // prettify label
                                        $label = str_replace(['_','-'], ' ', $folder);
                                        $label = ucwords($label);
                                        echo "<option value=\"{$folder}\" data-folder=\"{$folder}\" style=\"font-family: '{$label}'\">{$label}</option>\n";
                                    }
                                }
                            @endphp
                        </optgroup>
                    </select>
                </div>

                <div class="form-group">
                    <label for="fontWeight">Ketebalan Font</label>
                    <select id="fontWeight">
                        <option value="400">Regular</option>
                        <option value="500">Medium</option>
                        <option value="600">Semi Bold</option>
                        <option value="700">Bold</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="fontStyle">Gaya Font</label>
                    <select id="fontStyle">
                        <option value="normal">Normal</option>
                        <option value="italic">Italic</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="textAlign">Perataan Teks</label>
                    <select id="textAlign">
                        <option value="left">Kiri</option>
                        <option value="center" selected>Tengah</option>
                        <option value="right">Kanan</option>
                    </select>
                </div>
            </div>

            <div id="imageOptions" style="display: none;">
                <div class="form-group">
                    <label for="imageFile">Upload Gambar</label>
                    <div class="file-input-wrapper" style="border: 2px dashed var(--border-color); padding: 20px; border-radius: 8px; text-align: center; background: #f8f9fa;">
                        <div class="file-input-container" style="margin-bottom: 10px;">
                            <label for="imageFile" style="display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; padding: 10px 20px; background-color: var(--primary-color); color: white; border-radius: 5px;">
                                <span style="font-size: 24px;">🖼️</span>
                                <span>Pilih Gambar</span>
                            </label>
                            <input type="file" 
                                   id="imageFile" 
                                   accept="image/jpeg,image/png,image/gif" 
                                   onchange="handleImageSelect(event)" 
                                   style="display: none;">
                        </div>
                        <div class="image-preview" style="margin: 10px 0; display: none;">
                            <img id="imagePreview" style="max-width: 200px; max-height: 100px; object-fit: contain;">
                        </div>
                        <div style="font-size: 12px; color: #666; margin-top: 8px;">
                            Format: JPG, PNG, atau GIF (Maks. 2MB)
                        </div>
                    </div>
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

        async function uploadImage(file) {
            const formData = new FormData();
            formData.append('background_image', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

            const response = await fetch('/sertifikat-templates/upload-image', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                const result = await response.json();
                throw new Error(result.message || 'Upload gagal');
            }

            const result = await response.json();
            return result.url;
        }

        async function handleImageSelect(event) {
            try {
                const file = event.target.files[0];
                const preview = document.getElementById('imagePreview');
                const previewContainer = preview.parentElement;

                if (!file) {
                    previewContainer.style.display = 'none';
                    return;
                }

                // Validate file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    showNotification('Format file harus JPG, PNG, atau GIF', 'error');
                    event.target.value = '';
                    previewContainer.style.display = 'none';
                    return;
                }

                // Validate file size (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showNotification('Ukuran file maksimal 2MB', 'error');
                    event.target.value = '';
                    previewContainer.style.display = 'none';
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);

                // Upload image
                const imageUrl = await uploadImage(file);
                preview.dataset.uploadedUrl = imageUrl;
                showNotification('Gambar berhasil diupload');

            } catch (error) {
                console.error('Image upload error:', error);
                showNotification(error.message || 'Gagal mengupload gambar', 'error');
            }
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

                const familyDisplay = document.getElementById('fontFamily').value;
                const weightSelect = document.getElementById('fontWeight');
                const weightOption = weightSelect.options[weightSelect.selectedIndex];
                const file = weightOption ? (weightOption.dataset.file || weightOption.value) : null;
                const cssWeight = weightOption ? (weightOption.dataset.cssWeight || weightOption.value) : '400';
                const cssStyle = document.getElementById('fontStyle').value || 'normal';

                // register a generated family so preview uses the exact file
                const generatedFamily = registerFontFace(familyDisplay, file, cssWeight, cssStyle, familyDisplay) || familyDisplay;

                element.text = text;
                element.fontSize = parseInt(document.getElementById('fontSize').value) || 16;
                element.font = {
                    // display name (what user selected), used for storing
                    family: familyDisplay,
                    // folder name where font files are stored
                    folder: familyDisplay,
                    // css weight used for PDF generation (numeric string)
                    weight: String(cssWeight),
                    cssWeight: String(cssWeight),
                    style: cssStyle,
                    // actual filename in public/fonts/<family>/
                    weightFile: file,
                    // previewFamily used only on client to render correct file
                    previewFamily: generatedFamily
                };
                element.textAlign = document.getElementById('textAlign').value;
                element.placeholderType = placeholderType;
            } else {
                const imagePreview = document.getElementById('imagePreview');
                const uploadedUrl = imagePreview.dataset.uploadedUrl;
                if (!uploadedUrl) {
                    showNotification('Harap upload gambar terlebih dahulu', 'error');
                    return;
                }

                element.imageUrl = uploadedUrl;
                element.width = parseInt(document.getElementById('imageWidth').value) || 100;
                element.height = parseInt(document.getElementById('imageHeight').value) || 100;
                
                // Debug image data
                console.log('Adding image element:', {
                    url: uploadedUrl,
                    width: element.width,
                    height: element.height
                });
            }

            elements.push(element);
            updatePreview();
            
            // Reset form fields based on element type
            if (type === 'text') {
                document.getElementById('elementText').value = '';
            }
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

            // Create a container for elements that matches PDF dimensions exactly
            const elementContainer = document.createElement('div');
            elementContainer.style.cssText = `
                position: absolute;
                width: 842px;
                height: 595px;
                transform-origin: top left;
                transform: scale(${preview.offsetWidth / 842});
                left: 0;
                top: 0;
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
                    // Ensure font-face for this element is registered (if editor stored folder+weightFile)
                    if (element.font && element.font.folder && element.font.weightFile) {
                        try {
                            const gen = registerFontFace(element.font.folder, element.font.weightFile, element.font.cssWeight || element.font.weight || '400', element.font.style || 'normal', element.font.family || element.font.folder);
                            if (gen) element.font.previewFamily = gen;
                        } catch (e) {
                            console.warn('registerFontFace failed for', element.font, e);
                        }
                    }
                    const textDiv = document.createElement('p');
                    textDiv.style.fontSize = element.fontSize + 'px';
            // Prefer previewFamily (registered @font-face) if available, otherwise use display family
            const previewFamily = element.font && element.font.previewFamily ? element.font.previewFamily : element.font.family;
            textDiv.style.fontFamily = "'" + previewFamily + "', sans-serif";
            textDiv.style.fontWeight = element.font.weight || '400';
            textDiv.style.fontStyle = element.font.style || 'normal';
                    textDiv.style.textAlign = element.textAlign;
                    textDiv.style.margin = '0';
                    textDiv.style.padding = '0';
                    textDiv.style.whiteSpace = 'nowrap';
                    
                    // Ensure font is loaded (try to check previewFamily first)
                    document.fonts.ready.then(() => {
                        const checkFam = previewFamily || element.font.family;
                        // Normalize weight to a numeric token or 'normal'
                        let weightToken = String(element.font.weight || element.font.cssWeight || '400');
                        if (!/^[0-9]+$/.test(weightToken)) {
                            // map common textual tokens to numeric weights
                            const wt = weightToken.toLowerCase();
                            if (wt === 'bold') weightToken = '700';
                            else if (wt === 'semibold' || wt === 'semi' || wt === '600') weightToken = '600';
                            else if (wt === 'medium' || wt === '500') weightToken = '500';
                            else weightToken = '400';
                        }
                        const styleToken = (element.font.style || 'normal');
                        const sizeToken = (element.fontSize || 16) + 'px';
                        const descriptor = `${styleToken} ${weightToken} ${sizeToken} "${checkFam}"`;
                        try {
                            if (!document.fonts.check(descriptor)) {
                                console.warn(`Font ${checkFam} might not be loaded properly (checked: ${descriptor})`);
                            }
                        } catch (e) {
                            // In case descriptor is still invalid for some unexpected value, fallback to simple check
                            try {
                                if (!document.fonts.check(`${sizeToken} "${checkFam}"`)) {
                                    console.warn(`Font ${checkFam} might not be loaded properly (fallback check)`);
                                }
                            } catch (ignored) {
                                console.warn('Font check failed with unexpected descriptor', descriptor, ignored);
                            }
                        }
                    });
                    textDiv.style.position = 'absolute';
                    textDiv.style.display = 'block';
                    textDiv.style.left = '0';
                    textDiv.style.top = '0';

                    // Set width to auto to get natural text width
                    textDiv.style.width = 'auto';
                    textDiv.textContent = element.text;
                    div.appendChild(textDiv);

                    // Add a slight delay to ensure text is rendered before measuring
                    setTimeout(() => {
                        // Set the container size to match the text content
                        const bounds = textDiv.getBoundingClientRect();
                        div.style.width = bounds.width + 'px';
                        div.style.height = bounds.height + 'px';

                        // Position the container based on alignment
                        if (element.textAlign === 'center') {
                            div.style.left = element.x + 'px';
                            div.style.transform = 'translateX(-50%)';
                            div.style.transformOrigin = '50% 0';
                        } else if (element.textAlign === 'right') {
                            div.style.left = element.x + 'px';
                            div.style.transformOrigin = '100% 0';
                            div.style.transform = 'translateX(-100%)';
                        } else {
                            // Left alignment
                            div.style.left = element.x + 'px';
                            div.style.transformOrigin = '0 0';
                            div.style.transform = 'none';
                        }
                    }, 0);
                } else {
                    const img = document.createElement('img');
                    img.src = element.imageUrl;
                    img.style.width = element.width + 'px';
                    img.style.height = element.height + 'px';
                    img.style.margin = '0';
                    img.style.padding = '0';
                    img.style.display = 'block';
                    div.style.width = element.width + 'px';
                    div.style.height = element.height + 'px';
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
            const isImage = draggedElement.querySelector('img') !== null;

            let x = (e.clientX - rect.left - offsetX);
            let y = (e.clientY - rect.top - offsetY);
            
            // Adjust position based on element type and text alignment
            if (!isImage) {
                // Text element adjustments
                if (textAlign === 'center') {
                    x += draggedElement.offsetWidth / 2;
                    y += draggedElement.offsetHeight / 2;
                } else if (textAlign === 'right') {
                    x += draggedElement.offsetWidth;
                    y += draggedElement.offsetHeight / 2;
                } else {
                    y += draggedElement.offsetHeight / 2;
                }
            }
            
            x = Math.max(0, Math.min(x, 842 - (textAlign === 'left' ? draggedElement.offsetWidth : 0)));
            y = Math.max(0, Math.min(y, 595));
            
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

                // set display family first, then populate weights and select the correct file
                const familyDisplay = element.font.family || element.font.previewFamily || document.getElementById('fontFamily').value;
                document.getElementById('fontFamily').value = familyDisplay;

                // populate weights and then choose the option that matches saved weightFile
                fetchWeightsForFont(familyDisplay).then(() => {
                    const weightSelect = document.getElementById('fontWeight');
                    for (let i = 0; i < weightSelect.options.length; i++) {
                        const opt = weightSelect.options[i];
                        if (opt.dataset && opt.dataset.file && opt.dataset.file === element.font.weightFile) {
                            weightSelect.selectedIndex = i;
                            break;
                        }
                        // fallback: match cssWeight
                        if (opt.dataset && opt.dataset.cssWeight && opt.dataset.cssWeight === String(element.font.weight)) {
                            weightSelect.selectedIndex = i;
                            break;
                        }
                    }

                    // ensure generated family is registered so preview shows correctly
                    const selOpt = weightSelect.options[weightSelect.selectedIndex];
                    const file = selOpt ? (selOpt.dataset.file || selOpt.value) : element.font.weightFile;
                    const cssWeight = selOpt ? (selOpt.dataset.cssWeight || selOpt.value) : element.font.weight || '400';
                    const cssStyle = element.font.style || 'normal';
                    const generated = registerFontFace(familyDisplay, file, cssWeight, cssStyle, familyDisplay);
                    // update element.font.family to the generated one used by preview
                    element.font.previewFamily = generated || familyDisplay;
                });

                document.getElementById('fontStyle').value = element.font.style || 'normal';
                document.getElementById('textAlign').value = element.textAlign;
            } else {
                // Update width and height for image
                document.getElementById('imageWidth').value = element.width;
                document.getElementById('imageHeight').value = element.height;
                
                // Show image preview if exists
                const preview = document.getElementById('imagePreview');
                const previewContainer = preview.parentElement;
                if (element.imageUrl) {
                    preview.src = element.imageUrl;
                    preview.dataset.uploadedUrl = element.imageUrl;
                    previewContainer.style.display = 'block';
                }
            }

            elements.splice(index, 1);
            updatePreview();
            showNotification('Elemen siap untuk diedit');
        }

    async function saveTemplate() {
            try {
                const preview = document.getElementById('certificate-preview');
                const templateName = document.getElementById('templateName').value;
                
                // ==========================================================
                // PERUBAHAN 2: Baca merchant_id dari input tersembunyi
                // ==========================================================
                const merchantId = document.getElementById('merchantId').value;
                
                if (!merchantId) {
                    showNotification('Merchant ID tidak ditemukan. Harap muat ulang halaman.', 'error');
                    return;
                }
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

                const backgroundImage = preview.dataset.backgroundImage;

                // normalize elements to ensure font.style exists and weightFile is valid
                function normalizeElementsForSave(list) {
                    return list.map(el => {
                        if (el.type === 'text') {
                            el.font = el.font || {};
                            // ensure style is present
                            el.font.style = el.font.style || (document.getElementById('fontStyle') ? document.getElementById('fontStyle').value : 'normal') || 'normal';
                            // ensure cssWeight
                            el.font.cssWeight = el.font.cssWeight || el.font.weight || '400';
                            // only keep weightFile if it looks like a real font filename
                            if (el.font.weightFile && !/\.(ttf|otf|woff2?|woff)$/i.test(String(el.font.weightFile))) {
                                delete el.font.weightFile;
                            }
                            el.font.folder = el.font.folder || el.font.family || null;
                        }
                        return el;
                    });
                }

                const data = {
                    name: templateName,
                    background_image: backgroundImage,
                    elements: normalizeElementsForSave(elements).map(el => ({
                        type: el.type,
                        x: el.x,
                        y: el.y,
                        ...(el.type === 'text' ? {
                            text: el.text,
                            fontSize: el.fontSize,
                            // send normalized font object (do not include previewFamily)
                            font: {
                                family: el.font.family,
                                folder: el.font.folder || el.font.family,
                                weight: el.font.weight,
                                cssWeight: el.font.cssWeight || el.font.weight,
                                style: el.font.style,
                                weightFile: el.font.weightFile
                            },
                            textAlign: el.textAlign,
                            placeholderType: el.placeholderType
                        } : {
                            imageUrl: el.imageUrl,
                            width: el.width,
                            height: el.height
                        })
                    })),
                    // ==========================================================
                    // PERUBAHAN 3: Tambahkan merchant_id ke payload
                    // ==========================================================
                    merchant_id: merchantId,
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

    // Helper: map numeric weight to label
        function weightLabel(weight) {
            switch (String(weight)) {
                case '100': return 'Thin';
                case '200': return 'Extra Light';
                case '300': return 'Light';
                case '400': return 'Regular';
                case '500': return 'Medium';
                case '600': return 'Semi Bold';
                case '700': return 'Bold';
                case '800': return 'Extra Bold';
                case '900': return 'Black';
                default: return String(weight);
            }
        }

        // Register @font-face dynamically for a specific file under public/fonts/{folder}/{file}
        const _registeredFontFaces = {};
        function sanitizeKey(s) {
            return String(s || '').replace(/[^a-z0-9\-_]+/gi, '-').toLowerCase();
        }

        function _formatFromExtension(fileName) {
            if (!fileName) return 'truetype';
            const ext = String(fileName).split('.').pop().toLowerCase();
            if (ext === 'woff2') return 'woff2';
            if (ext === 'woff') return 'woff';
            if (ext === 'otf') return 'opentype';
            return 'truetype';
        }

        function registerFontFace(folderName, fileName, cssWeight = '400', cssStyle = 'normal', displayFamily = null) {
            if (!folderName) return null;

            // Prefer using filename (without extension) as part of generated family so it matches server embedding
            let genFamily;
            if (fileName && /\.(ttf|otf|woff2?|woff)$/i.test(fileName)) {
                const base = sanitizeKey(folderName);
                const filenameBase = sanitizeKey(fileName.replace(/\.[^.]+$/, ''));
                genFamily = `${base}-${filenameBase}`;
            } else {
                // fallback: use folder + weight
                const base = sanitizeKey(folderName);
                genFamily = `${base}-${sanitizeKey(cssWeight)}`;
            }

            if (_registeredFontFaces[genFamily]) return genFamily;

            // If no actual file provided, nothing to register
            if (!fileName || !/\.(ttf|otf|woff2?|woff)$/i.test(fileName)) return null;

            const url = `/fonts/${encodeURIComponent(folderName)}/${encodeURIComponent(fileName)}`;
            const format = _formatFromExtension(fileName);
            const style = document.createElement('style');
            style.type = 'text/css';
            style.textContent = `
                @font-face {
                    font-family: '${genFamily}';
                    src: url('${url}') format('${format}');
                    font-weight: ${cssWeight};
                    font-style: ${cssStyle};
                    font-display: swap;
                }
            `;
            document.head.appendChild(style);
            _registeredFontFaces[genFamily] = true;
            return genFamily;
        }

        // Fetch available weights from server endpoint; fallback to Google Fonts parse
        async function fetchWeightsForFont(fontFamily) {
            const weightSelect = document.getElementById('fontWeight');
            weightSelect.innerHTML = '';

            if (!fontFamily) {
                const opt = new Option('Regular', '400');
                // do not set dataset.file when we don't have an actual font file
                opt.dataset.cssWeight = '400';
                opt.dataset.folder = fontFamily;
                opt.dataset.style = 'normal';
                weightSelect.appendChild(opt);
                return;
            }

            try {
                const res = await fetch(`/fonts/${encodeURIComponent(fontFamily)}/weights`);
                if (res.ok) {
                    const data = await res.json();
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(item => {
                            const file = item.file || item.filename || item.name || item;
                            const cssWeight = item.key || item.cssWeight || item.weight || '400';
                            const label = item.label || weightLabel(cssWeight) || file;

                            const opt = new Option(label, file);
                            // only treat `file` as real filename when it looks like a font file
                            opt.dataset.file = /\.(ttf|otf|woff2?|woff)$/i.test(String(file)) ? String(file) : '';
                            opt.dataset.cssWeight = cssWeight;
                            opt.dataset.key = item.key || cssWeight;
                            opt.dataset.folder = fontFamily;
                            opt.dataset.style = item.style || 'normal';
                            weightSelect.appendChild(opt);
                        });
                        return;
                    }
                }
            } catch (e) {
                console.warn('Could not fetch weights from server:', e);
            }

            // Fallback: try to parse Google Fonts link if exists
            const linkElement = document.querySelector('link[href*="fonts.googleapis.com/css2"]');
            if (linkElement) {
                try {
                    const url = new URL(linkElement.href);
                    const families = url.searchParams.get('family');
                    if (families) {
                        const map = {};
                        families.split('&').forEach(familyParam => {
                            const parts = familyParam.split(':');
                            const ff = parts[0].replace(/\+/g, ' ');
                            let weights = ['400'];
                            if (parts.length > 1 && parts[1].startsWith('wght@')) {
                                weights = parts[1].substring(5).split(';');
                            }
                            map[ff] = weights;
                        });
                        const available = map[fontFamily] || ['400'];
                        available.forEach(w => {
                            const opt = new Option(weightLabel(w), w);
                            // no real filename in Google Fonts fallback; leave dataset.file empty
                            opt.dataset.file = '';
                            opt.dataset.cssWeight = w;
                            opt.dataset.folder = fontFamily;
                            opt.dataset.style = 'normal';
                            weightSelect.appendChild(opt);
                        });
                        return;
                    }
                } catch (e) {
                    console.warn('Failed parsing Google Fonts URL', e);
                }
            }

            // Final fallback: standard four options
            ['400','500','600','700'].forEach(w => {
                const opt = new Option(weightLabel(w), w);
                // fallback weights without real files; don't set dataset.file
                opt.dataset.file = '';
                opt.dataset.cssWeight = w;
                opt.dataset.folder = fontFamily;
                opt.dataset.style = 'normal';
                weightSelect.appendChild(opt);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const fontFamilySelect = document.getElementById('fontFamily');
            const elementTextInput = document.getElementById('elementText');

            // Initial populate based on current selected font
            fetchWeightsForFont(fontFamilySelect.value);

            fontFamilySelect.addEventListener('change', function() {
                const selectedFont = fontFamilySelect.value;
                if (elementTextInput) {
                    elementTextInput.style.fontFamily = `'${selectedFont}', Arial, sans-serif`;
                }
                if (selectedElement && selectedElement.type === 'text') {
                    // when editing an element in-place, update its display name but don't overwrite generated family until weight selected
                    selectedElement.font.family = selectedFont;
                    updatePreview();
                }
                fetchWeightsForFont(selectedFont);
            });

            // when weight changes, register font-face for preview immediately
            document.getElementById('fontWeight').addEventListener('change', function() {
                const familyDisplay = document.getElementById('fontFamily').value;
                const opt = this.options[this.selectedIndex];
                // use dataset.file only when it's a real filename; otherwise null
                const file = opt ? (opt.dataset.file && /\.(ttf|otf|woff2?|woff)$/i.test(opt.dataset.file) ? opt.dataset.file : null) : null;
                const cssWeight = opt ? (opt.dataset.cssWeight || opt.value) : '400';
                const cssStyle = document.getElementById('fontStyle').value || 'normal';
                const generated = file ? registerFontFace(familyDisplay, file, cssWeight, cssStyle, familyDisplay) : null;

                // if editing an existing element, update its generated family so preview updates
                if (selectedElement && selectedElement.type === 'text') {
                    selectedElement.font.previewFamily = generated || familyDisplay;
                    // only store weightFile when we have an actual file
                    selectedElement.font.weightFile = file ? file : null;
                    selectedElement.font.weight = String(cssWeight);
                    selectedElement.font.style = cssStyle;
                    updatePreview();
                }
            });
        });
    </script>
</body>
</html>
