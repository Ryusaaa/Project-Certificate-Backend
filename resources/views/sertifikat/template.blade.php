<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sertifikat</title>
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

        /* --- LOGIKA BARU UNTUK MEMUAT FONT --- */
        @foreach($fontMappings as $fontName => $fontConfig)
            @if(isset($fontConfig['folder']) && isset($fontConfig['variants']))
                @foreach($fontConfig['variants'] as $weight => $styles)
                    @foreach($styles as $style => $fileName)
                        @php
                            $fontPath = public_path("fonts/{$fontConfig['folder']}/{$fileName}");
                            $fontBase64 = getFontBase64($fontPath);
                        @endphp
                        @if($fontBase64)
                        @font-face {
                            font-family: '{{ $fontName }}';
                            src: url('data:font/truetype;charset=utf-8;base64,{{ $fontBase64 }}') format('truetype');
                            font-weight: {{ $weight }};
                            font-style: {{ $style }}; /* <-- FONT STYLE SEKARANG DINAMIS */
                        }
                        @endif
                    @endforeach
                @endforeach
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

        .element-text {
            /* Tidak perlu style tambahan di sini, semua akan di-handle inline */
        }

        .element-image {
            display: block;
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        @if($background_image)
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

        @if(is_array($elements))
            @foreach($elements as $element)
                @if($element['type'] === 'text')
                    @php
                        // Menggunakan isset() untuk kompatibilitas PHP < 7.0
                        $fontFamily = isset($element['font']['family']) ? $element['font']['family'] : 'Arial';
                        $fontWeight = isset($element['font']['weight']) ? $element['font']['weight'] : '400';
                        $fontStyle = isset($element['font']['style']) ? $element['font']['style'] : 'normal';
                        $fontSize = isset($element['fontSize']) ? $element['fontSize'] : 16;
                        $textAlign = isset($element['textAlign']) ? $element['textAlign'] : 'left';
                        $color = isset($element['color']) ? $element['color'] : '#000000';
                        $text = isset($element['text']) ? $element['text'] : '';
                        $x = $element['x'];
                        $y = $element['y'];

                        // Normalisasi weight untuk konsistensi
                        if ($fontWeight === 'normal') $fontWeight = '400';
                        if ($fontWeight === 'bold') $fontWeight = '700';

                        // Penyesuaian posisi berdasarkan perataan teks
                        $style = "
                            position: absolute;
                            left: {$x}pt;
                            top: {$y}pt;
                            font-family: '{$fontFamily}', Arial, sans-serif;
                            font-size: {$fontSize}pt;
                            font-weight: {$fontWeight};
                            font-style: {$fontStyle};
                            color: {$color};
                            text-align: {$textAlign};
                            width: {$pageWidth}pt; /* Beri lebar agar text-align berfungsi */
                        ";

                        // Untuk text-align center dan right, kita perlu menyesuaikan posisi X
                        if ($textAlign === 'center') {
                            $style .= "left: 0pt;"; // Mulai dari kiri dan biarkan text-align bekerja
                        } elseif ($textAlign === 'right') {
                            $doublePageWidth = $pageWidth * 2;
                            $style .= "left: -{$pageWidth}pt; width: {$doublePageWidth}pt;";
                        }
                    @endphp
                    <div class="element element-text" style="{{ $style }}">
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
                            <img src="{{ $imageSrc }}" class="element-image" style="width: {{ $element['width'] }}pt; height: {{ $element['height'] }}pt;">
                        </div>
                    @endif
                @endif
            @endforeach
        @endif
    </div>
</body>
</html>
