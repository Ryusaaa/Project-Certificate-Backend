<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sertifikat</title>
    <link href="{{ asset('css/all-fonts.css') }}" rel="stylesheet">
    <style>
        @php
            // Helper function to get font base64
            function getFontBase64($fontPath) {
                if (file_exists($fontPath)) {
                    return base64_encode(file_get_contents($fontPath));
                }
                // Log or handle error if font file not found
                error_log("Font file not found: " . $fontPath);
                return null;
            }

            // --- STRUKTUR FONT BARU ---
            // Struktur ini lebih detail, memisahkan file untuk setiap kombinasi weight dan style.
            // Anda perlu memastikan nama file di sini sesuai dengan file .ttf di folder public/fonts/ Anda.
            $fontMappings = [
                // Contoh untuk Poppins
                'Poppins' => [
                    'folder' => 'poppins',
                    'variants' => [
                        '400' => [
                            'normal' => 'Poppins-Regular.ttf',
                            'italic' => 'Poppins-Italic.ttf', // File untuk Poppins Italic
                        ],
                        '500' => [
                            'normal' => 'Poppins-Medium.ttf',
                            'italic' => 'Poppins-MediumItalic.ttf',
                        ],
                        '600' => [
                            'normal' => 'Poppins-SemiBold.ttf',
                            'italic' => 'Poppins-SemiBoldItalic.ttf',
                        ],
                        '700' => [
                            'normal' => 'Poppins-Bold.ttf',
                            'italic' => 'Poppins-BoldItalic.ttf', // File untuk Poppins Bold Italic
                        ],
                    ],
                ],
                // Contoh untuk Montserrat
                'Montserrat' => [
                    'folder' => 'montserrat',
                    'variants' => [
                        '400' => [
                            'normal' => 'Montserrat-Regular.ttf',
                            'italic' => 'Montserrat-Italic.ttf',
                        ],
                        '700' => [
                            'normal' => 'Montserrat-Bold.ttf',
                            'italic' => 'Montserrat-BoldItalic.ttf',
                        ],
                    ],
                ],
                // Contoh untuk font yang hanya punya satu gaya (misal: Great Vibes)
                'Great Vibes' => [
                    'folder' => 'Great_Vibes',
                    'variants' => [
                        '400' => [
                            'normal' => 'GreatVibes-Regular.ttf',
                        ],
                    ],
                ],
                 'Allura' => [
                    'folder' => 'Allura',
                    'variants' => [
                        '400' => ['normal' => 'Allura-Regular.ttf'],
                    ],
                ],
                // Tambahkan font lain di sini dengan struktur yang sama...
                'League Spartan' => [
                    'folder' => 'League_Spartan',
                    'variants' => [
                        '400' => ['normal' => 'LeagueSpartan-Regular.ttf'],
                        '700' => ['normal' => 'LeagueSpartan-Bold.ttf'],
                    ]
                ],
                 'Playfair Display' => [
                    'folder' => 'playfair-display',
                    'variants' => [
                        '400' => ['normal' => 'PlayfairDisplay-Regular.ttf', 'italic' => 'PlayfairDisplay-Italic.ttf'],
                        '700' => ['normal' => 'PlayfairDisplay-Bold.ttf', 'italic' => 'PlayfairDisplay-BoldItalic.ttf'],
                    ]
                ],
                // Font sistem sebagai fallback
                'Arial' => ['type' => 'system'],
                'Times New Roman' => ['type' => 'system'],
                'Helvetica' => ['type' => 'system'],
            ];

            $pageWidth = 842;
            $pageHeight = 595;
        @endphp

        @page {
            margin: 0;
            padding: 0;
            size: {{ $pageWidth }}pt {{ $pageHeight }}pt;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* --- Muat hanya font yang dipakai oleh elemen (embed base64) --- */
            @php
            // Kumpulkan daftar font yang diperlukan dari elemen
            // Untuk setiap elemen teks yang punya folder, coba temukan file font nyata yang cocok
            $requiredFonts = [];
            if (isset($elements) && is_array($elements)) {
                foreach ($elements as $el) {
                    if (!isset($el['type']) || $el['type'] !== 'text') continue;
                    $f = isset($el['font']) ? $el['font'] : null;
                    if (!$f) continue;
                    if (!isset($f['folder'])) continue;

                    $folder = $f['folder'];
                    $requestedFile = isset($f['weightFile']) ? $f['weightFile'] : null;
                    $style = isset($f['style']) ? $f['style'] : 'normal';
                    $weight = isset($f['cssWeight']) ? $f['cssWeight'] : (isset($f['weight']) ? $f['weight'] : '400');

                    $folderPath = public_path('fonts/'. $folder);
                    $resolvedFile = null;

                    // If the requestedFile looks like an actual filename and exists, use it
                    if ($requestedFile && preg_match('/\.(ttf|otf|woff2?|woff)$/i', $requestedFile)) {
                        $candidate = $folderPath . DIRECTORY_SEPARATOR . $requestedFile;
                        if (file_exists($candidate)) {
                            $resolvedFile = $requestedFile;
                        }
                    }

                    // Otherwise, try to scan folder and pick the best candidate matching style/weight
                    if (!$resolvedFile && is_dir($folderPath)) {
                        $filesInFolder = array_values(array_filter(scandir($folderPath), function($fn) use ($folderPath) {
                            if (in_array($fn, ['.', '..'])) return false;
                            return preg_match('/\.(ttf|otf|woff2?|woff)$/i', $fn) && is_file($folderPath . DIRECTORY_SEPARATOR . $fn);
                        }));

                        // Prefer italic files if style requested
                        if ($style === 'italic') {
                            foreach ($filesInFolder as $ff) {
                                if (stripos($ff, 'italic') !== false) { $resolvedFile = $ff; break; }
                            }
                        }

                        // Try matching by weight token
                        if (!$resolvedFile) {
                            foreach ($filesInFolder as $ff) {
                                $low = strtolower($ff);
                                if (strpos($low, (string)$weight) !== false) { $resolvedFile = $ff; break; }
                                // common tokens
                                if ($weight == '400' && (strpos($low, 'regular') !== false || strpos($low, '-regular') !== false)) { $resolvedFile = $ff; break; }
                                if ($weight == '700' && (strpos($low, 'bold') !== false || strpos($low, '-bold') !== false)) { $resolvedFile = $ff; break; }
                                if ($weight == '600' && (strpos($low, 'semibold') !== false || strpos($low, 'semi') !== false)) { $resolvedFile = $ff; break; }
                            }
                        }

                        // Fallback to first file
                        if (!$resolvedFile && count($filesInFolder) > 0) {
                            $resolvedFile = $filesInFolder[0];
                        }
                    }

                    if ($resolvedFile) {
                        $key = $folder . '||' . $resolvedFile . '||' . $weight . '||' . $style;
                        $requiredFonts[$key] = [
                            'folder' => $folder,
                            'file' => $resolvedFile,
                            'weight' => $weight,
                            'style' => $style,
                        ];
                    }
                }
            }

            // sanitize helper for css family name
            function sanit($s) { return preg_replace('/[^a-z0-9\-_]+/i','-', $s); }
        @endphp

        @foreach($requiredFonts as $k => $info)
            @php
                // try to locate actual file path. If $info['file'] is not a real filename (eg '400'),
                // scan the folder for a likely candidate matching requested weight/style.
                $folderPath = public_path('fonts/'.($info['folder'] ?? ''));
                $requestedFile = $info['file'];
                $resolvedFile = null;

                if ($requestedFile && preg_match('/\.(ttf|otf|woff2?|woff)$/i', $requestedFile)) {
                    $candidate = $folderPath . DIRECTORY_SEPARATOR . $requestedFile;
                    if (file_exists($candidate)) {
                        $resolvedFile = $requestedFile;
                    }
                }

                // if not resolved, attempt to scan folder for a filename that contains the weight token
                if (!$resolvedFile && is_dir($folderPath)) {
                    $filesInFolder = array_values(array_filter(scandir($folderPath), function($f) use ($folderPath) {
                        if (in_array($f, ['.', '..'])) return false;
                        return preg_match('/\.(ttf|otf|woff2?|woff)$/i', $f) && is_file($folderPath . DIRECTORY_SEPARATOR . $f);
                    }));

                    // try exact matches by weight numeric or token
                    $weight = $info['weight'] ?? '400';
                    $styleRequested = $info['style'] ?? 'normal';

                    // If italic requested, prefer filenames that contain 'italic' or similar
                    if ($styleRequested === 'italic') {
                        foreach ($filesInFolder as $ff) {
                            $low = strtolower($ff);
                            if (strpos($low, 'italic') !== false || strpos($low, 'oblique') !== false || strpos($low, 'ital') !== false) {
                                $resolvedFile = $ff;
                                break;
                            }
                        }
                    }

                    if (!$resolvedFile) {
                        foreach ($filesInFolder as $ff) {
                            $low = strtolower($ff);
                            if (strpos($low, (string)$weight) !== false) { $resolvedFile = $ff; break; }
                            if ($weight == '400' && (strpos($low, 'regular') !== false || strpos($low, '-regular') !== false)) { $resolvedFile = $ff; break; }
                            if ($weight == '700' && (strpos($low, 'bold') !== false || strpos($low, '-bold') !== false)) { $resolvedFile = $ff; break; }
                            if ($weight == '600' && (strpos($low, 'semibold') !== false || strpos($low, 'semi') !== false)) { $resolvedFile = $ff; break; }
                        }
                    }

                    // fallback: pick first file
                    if (!$resolvedFile && count($filesInFolder) > 0) {
                        $resolvedFile = $filesInFolder[0];
                    }
                }

                if ($resolvedFile) {
                    $fontPath = public_path('fonts/'.($info['folder'] ?? '').'/'.$resolvedFile);
                    $fontBase64 = getFontBase64($fontPath);
                    $generatedFamily = sanit($info['folder']) . '-' . sanit(pathinfo($resolvedFile, PATHINFO_FILENAME));

                    // detect whether the resolved file appears to be an italic variant
                    $isItalicFile = (bool) preg_match('/italic|oblique|ital/i', $resolvedFile);
                } else {
                    $fontBase64 = null;
                    $generatedFamily = sanit($info['folder']) . '-' . sanit(pathinfo($info['file'] ?? '', PATHINFO_FILENAME));
                    $isItalicFile = false;
                }

                // determine MIME/format by extension
                $format = 'truetype';
                if (!empty($resolvedFile)) {
                    $ext = strtolower(pathinfo($resolvedFile, PATHINFO_EXTENSION));
                    if ($ext === 'woff2') $format = 'woff2';
                    elseif ($ext === 'woff') $format = 'woff';
                    elseif ($ext === 'otf') $format = 'opentype';
                    else $format = 'truetype';
                }

                $weightVal = is_numeric($info['weight']) ? $info['weight'] : (($info['weight'] ?? '400'));
                $styleRequested = $info['style'] ?? 'normal';
            @endphp

            @if($fontBase64)
                @if($isItalicFile)
                    /* resolved file is italic */
                    @font-face {
                        font-family: '{{ $generatedFamily }}';
                        src: url('data:font/{{ $format }};charset=utf-8;base64,{{ $fontBase64 }}') format('{{ $format }}');
                        font-weight: {{ $weightVal }};
                        font-style: italic;
                        font-display: swap;
                    }
                @else
                    {{-- resolved file is non-italic --}}
                    @font-face {
                        font-family: '{{ $generatedFamily }}';
                        src: url('data:font/{{ $format }};charset=utf-8;base64,{{ $fontBase64 }}') format('{{ $format }}');
                        font-weight: {{ $weightVal }};
                        font-style: normal;
                        font-display: swap;
                    }

                    {{-- If the user requested italic but no italic file exists, register an italic face that reuses the same file as a fallback so renderers that match by font-style will find a face. --}}
                    @if($styleRequested === 'italic')
                        @php error_log("Font fallback: requested italic for {$info['folder']}/{$resolvedFile} but no italic file found; using synthetic fallback."); @endphp
                        @font-face {
                            font-family: '{{ $generatedFamily }}';
                            src: url('data:font/{{ $format }};charset=utf-8;base64,{{ $fontBase64 }}') format('{{ $format }}');
                            font-weight: {{ $weightVal }};
                            font-style: italic; /* fallback mapped to same file */
                            font-display: swap;
                        }
                    @endif
                @endif
            @endif
        @endforeach

        body {
            margin: 0;
            padding: 0;
            width: {{ $pageWidth }}pt;
            height: {{ $pageHeight }}pt;
            position: relative;
            font-family: Arial, sans-serif; /* Fallback default */
        }

        .certificate-container {
            position: relative;
            width: 100%;
            height: 100%;
        }

        .background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .element {
            position: absolute;
            z-index: 2;
            white-space: nowrap;
            line-height: 1; /* Set line-height ke 1 untuk konsistensi */
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        @if(isset($background_image) && $background_image)
            @php
                $bgSrc = '';
                if (file_exists($background_image)) {
                    $imageData = base64_encode(file_get_contents($background_image));
                    $bgSrc = 'data:image/' . pathinfo($background_image, PATHINFO_EXTENSION) . ';base64,' . $imageData;
                }
            @endphp
            @if($bgSrc)
                <img src="{{ $bgSrc }}" class="background">
            @endif
        @endif

        @if(isset($elements) && is_array($elements))
            @foreach($elements as $element)
                @if($element['type'] === 'text')
                        @php
                            // Use saved folder + weightFile to build generated CSS family name
                            $fontFolder = isset($element['font']['folder']) ? $element['font']['folder'] : null;
                            $fontFile   = isset($element['font']['weightFile']) ? $element['font']['weightFile'] : null;
                            $fontWeight = isset($element['font']['cssWeight']) ? $element['font']['cssWeight'] : (isset($element['font']['weight']) ? $element['font']['weight'] : '400');
                            $fontStyle  = isset($element['font']['style']) ? $element['font']['style'] : 'normal';
                            $fontSize   = isset($element['fontSize']) ? $element['fontSize'] : 16;
                            $textAlign  = isset($element['textAlign']) ? $element['textAlign'] : 'left';
                            $color      = isset($element['color']) ? $element['color'] : '#000000';
                            $text       = isset($element['text']) ? $element['text'] : '';
                            $x = $element['x']; $y = $element['y'];

                            // compute generated family that matches client-side registerFontFace
                            if ($fontFolder && $fontFile) {
                                $generatedFamily = preg_replace('/[^a-z0-9\-_]+/i','-', $fontFolder) . '-' . preg_replace('/[^a-z0-9\-_]+/i','-', pathinfo($fontFile, PATHINFO_FILENAME));
                            } else {
                                $generatedFamily = isset($element['font']['family']) ? $element['font']['family'] : 'Arial';
                            }

                            // Build transform functions array so we can append skew if needed
                            $transformFns = [];
                            if ($textAlign === 'center') $transformFns[] = 'translateX(-50%)';
                            elseif ($textAlign === 'right') $transformFns[] = 'translateX(-100%)';

                            // Determine if the resolved file is an italic variant
                            $isFileItalic = false;
                            if (!empty($fontFile)) {
                                $isFileItalic = (bool) preg_match('/italic|oblique|ital/i', $fontFile);
                            }

                            // If user requested italic but the embedded file is not italic, apply a visual skew fallback
                            $applySkewFallback = ($fontStyle === 'italic' && !$isFileItalic);
                            if ($applySkewFallback) {
                                $transformFns[] = 'skewX(-10deg)';
                            }

                            $transformCss = '';
                            if (count($transformFns) > 0) {
                                $transformCss = 'transform: ' . implode(' ', $transformFns) . ';';
                            }

                            // If applying skew fallback ensure element is inline-block so transform affects glyphs
                            $displayCss = $applySkewFallback ? 'display: inline-block;' : '';

                            $style = "
                                position: absolute;
                                left: {$x}pt;
                                top: {$y}pt;
                                font-family: '{$generatedFamily}', Arial, sans-serif;
                                font-size: {$fontSize}pt;
                                font-weight: {$fontWeight};
                                font-style: {$fontStyle};
                                color: {$color};
                                {$displayCss}
                                {$transformCss}
                            ";
                        @endphp
                        <div class="element" style="{!! $style !!}">
                            {!! $text !!}
                        </div>

                @elseif($element['type'] === 'image')
                    @php
                        $imageSrc = null;
                        $imageUrl = isset($element['imageUrl']) ? $element['imageUrl'] : null;
                        // GANTI str_starts_with dengan substr
                        if ($imageUrl && substr($imageUrl, 0, strlen('/storage/')) === '/storage/') {
                            $imagePath = storage_path('app/public/' . substr($imageUrl, 8));
                            if (file_exists($imagePath)) {
                                $imageData = base64_encode(file_get_contents($imagePath));
                                $imageSrc = 'data:image/' . pathinfo($imagePath, PATHINFO_EXTENSION) . ';base64,' . $imageData;
                            }
                        }
                    @endphp
                    @if($imageSrc)
                        <div class="element" style="left: {{ $element['x'] }}pt; top: {{ $element['y'] }}pt;">
                            <img src="{{ $imageSrc }}" style="width: {{ $element['width'] }}pt; height: {{ $element['height'] }}pt;">
                        </div>
                    @endif
                @endif
            @endforeach
        @endif
    </div>
</body>
</html>
