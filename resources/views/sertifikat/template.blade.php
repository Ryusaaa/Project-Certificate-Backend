<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sertifikat</title>
    @php
        $fonts = [
            'Montserrat' => [
                'folder' => 'montserrat',
                'prefix' => 'Montserrat',
            ],
            'Playfair Display' => [
                'folder' => 'playfair-display',
                'prefix' => 'PlayfairDisplay',
            ],
            'Poppins' => [
                'folder' => 'poppins',
                'prefix' => 'Poppins',
            ]
        ];

        $weights = [
            ['weight' => '400', 'name' => 'Regular'],
            ['weight' => '500', 'name' => 'Medium'],
            ['weight' => '600', 'name' => 'SemiBold'],
            ['weight' => '700', 'name' => 'Bold']   
        ];

        // Validate font files exist
        foreach ($fonts as $fontFamily => $font) {
            foreach ($weights as $weightInfo) {
                $regularPath = public_path("fonts/{$font['folder']}/{$font['prefix']}-{$weightInfo['name']}.ttf");
                $italicPath = public_path("fonts/{$font['folder']}/{$font['prefix']}-{$weightInfo['name']}Italic.ttf");
                
                if (!file_exists($regularPath)) {
                    \Log::warning("Font file missing: {$regularPath}");
                }
                if (!file_exists($italicPath)) {
                    \Log::warning("Font file missing: {$italicPath}");
                }
            }
        }
    @endphp
    <style>
        /* Font Declarations */
        /* System Fonts */
        @font-face {
            font-family: 'Times New Roman';
            src: local('Times New Roman');
        }
        @font-face {
            font-family: 'Arial';
            src: local('Arial');
        }
        @font-face {
            font-family: 'Helvetica';
            src: local('Helvetica');
        }
        @font-face {
            font-family: 'Georgia';
            src: local('Georgia');
        }

        /* Custom Fonts */

        @foreach($fonts as $fontFamily => $font)
            @foreach($weights as $weightInfo)
                /* {{ $fontFamily }} - {{ $weightInfo['name'] }} */
                @font-face {
                    font-family: '{{ $fontFamily }}';
                    src: url('{{ public_path("fonts/{$font['folder']}/{$font['prefix']}-{$weightInfo['name']}.ttf") }}') format('truetype');
                    font-weight: {{ $weightInfo['weight'] }};
                    font-style: normal;
                    font-display: swap;
                }
                @font-face {
                    font-family: '{{ $fontFamily }}';
                    src: url('{{ public_path("fonts/{$font['folder']}/{$font['prefix']}-{$weightInfo['name']}Italic.ttf") }}') format('truetype');
                    font-weight: {{ $weightInfo['weight'] }};
                    font-style: italic;
                    font-display: swap;
                }
            @endforeach
        @endforeach
        @php
            // Use exact editor dimensions for PDF
            $pageWidth = 842;     // A4 Landscape width in points
            $pageHeight = 595;    // A4 Landscape height in points
            
            // Debug dimensions
            \Log::info('Template dimensions:', [
                'pdf' => [
                    'width' => $pageWidth,
                    'height' => $pageHeight
                ]
            ]);
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
        body {
            margin: 0;
            padding: 0;
            background-color: white;
            width: {{ $pageWidth }}pt;
            height: {{ $pageHeight }}pt;
            position: relative;
        }
        .certificate-container {
            margin: 0;
            padding: 0;
            width: {{ $pageWidth }}pt;
            height: {{ $pageHeight }}pt;
            position: relative;
            overflow: hidden;
            background: white;
        }
        .certificate {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        .background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: {{ $pageHeight }}pt;
            object-fit: cover;
            object-position: center;
            z-index: 1;
        }
        .element {
            position: absolute;
            transform-origin: top left;
            z-index: 2;
        }
        .text {
            margin: 0 !important;
            padding: 0 !important;
            white-space: pre-wrap !important;
            word-wrap: break-word !important;
            line-height: 1.2 !important;
            position: absolute !important;
            transform-origin: 0 0 !important;
            display: inline-block !important;
            max-width: none !important;
            /* Penting: set height agar konsisten dengan line-height */
            height: auto !important;
            /* Tambahkan padding minimal untuk kompensasi font metrics */
            padding-top: 0.1em !important;
            box-sizing: content-box !important;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
                width: {{ $pageWidth }}pt;
                height: {{ $pageHeight }}pt;
            }
            .certificate-container {
                margin: 0;
                padding: 0;
                width: {{ $pageWidth }}pt;
                height: {{ $pageHeight }}pt;
            }
            .certificate {
                margin: 0;
                padding: 0;
                width: {{ $pageWidth }}pt;
                height: {{ $pageHeight }}pt;
            }
            .background {
                position: absolute;
                top: 0;
                left: 0;
                width: {{ $pageWidth }}pt;
                height: {{ $pageHeight }}pt;
                object-fit: cover;
                object-position: center;
            }
        }
    </style>
</head>
<body>
    <div class="certificate-container">
        <div class="certificate">
            @if($background_image)
                @php
                    if (file_exists($background_image)) {
                        $imageData = base64_encode(file_get_contents($background_image));
                        $bgSrc = 'data:image/' . pathinfo($background_image, PATHINFO_EXTENSION) . ';base64,' . $imageData;
                    } else {
                        $bgSrc = $background_image;
                    }
                @endphp
                <img src="{{ $bgSrc }}" class="background" style="width: 100%; height: 100%; object-fit: cover; max-width: none; max-height: none;">
            @endif

            @if(is_array($elements))
                @foreach($elements as $key => $element)
                    @if(isset($element['x']) && isset($element['y']))
                        @php
                            // Use coordinates directly - they are already transformed to PDF points
                            $x = $element['x'];
                            $fontSize = $element['fontSize'] ?? 12;
                            // Tambahkan offset berbeda untuk teks kustom dan placeholder
                            $y = $element['y'];
                            if ($element['type'] === 'text') {
                                // Adjust Y position based on font metrics
                                $fontFamily = $element['font']['family'] ?? 'Arial';
                                $fontMetricsOffset = [
                                    'Arial' => 0,
                                    'Montserrat' => $fontSize * 0.2,
                                    'Playfair Display' => $fontSize * 0.15,
                                    'Poppins' => $fontSize * 0.18
                                ];
                                
                                // Get offset for the current font, default to Arial's offset (0)
                                $offset = $fontMetricsOffset[$fontFamily] ?? 0;
                                $y += $offset;
                            }
                            $elementWidth = $element['width'] ?? null;
                            $elementHeight = $element['height'] ?? null;
                            
                            // Log final render position
                            \Log::info('Rendering element at PDF coordinates:', [
                                'id' => $element['id'] ?? 'unknown',
                                'text' => $element['text'] ?? '',
                                'position' => [
                                    'x' => $x,
                                    'y' => $y
                                ],
                                'fontSize' => $fontSize
                            ]);

                            // Process image source if it's an image element
                            $imageSrc = null;
                            if ($element['type'] === 'image') {
                                $imageUrl = $element['image_url'] ?? $element['imageUrl'] ?? $element['url'] ?? $element['image'] ?? null;
                                if ($imageUrl && str_starts_with($imageUrl, '/storage/')) {
                                    $imagePath = storage_path('app/public/' . substr($imageUrl, 8));
                                    if (file_exists($imagePath)) {
                                        $imageData = base64_encode(file_get_contents($imagePath));
                                        $imageSrc = 'data:image/' . pathinfo($imagePath, PATHINFO_EXTENSION) . ';base64,' . $imageData;
                                    }
                                } else {
                                    $imageSrc = $imageUrl;
                                }
                            }
                        @endphp
                        <div class="element" style="
                            left: {{ $x }}pt; 
                            top: {{ $y }}pt;
                            @if(isset($element['rotate'])) transform: rotate({{ $element['rotate'] }}deg); @endif
                        ">
                            @if($element['type'] === 'text')
                                <p class="text" style="
                                    font-family: '{{ $element['font']['family'] ?? 'Arial' }}', sans-serif !important;
                                    font-size: {{ $fontSize }}pt !important;
                                    font-weight: {{ $element['font']['weight'] ?? '400' }} !important;
                                    font-style: {{ $element['font']['style'] ?? 'normal' }} !important;
                                    text-align: {{ $element['textAlign'] ?? 'left' }} !important;
                                    color: {{ $element['color'] ?? '#000000' }} !important;
                                    margin: 0 !important;
                                    padding: 0 !important;
                                    line-height: 1.2 !important;
                                    white-space: nowrap !important;
                                    overflow: visible !important;
                                    /* Tambahan untuk konsistensi font metrics */
                                    vertical-align: top !important;
                                    display: inline-block !important;
                                    position: absolute !important;
                                    transform-origin: 0 0 !important;
                                    @if($element['textAlign'] === 'center')
                                        transform: translateX(-50%) !important;
                                        left: 50% !important;
                                    @elseif($element['textAlign'] === 'right')
                                        transform: translateX(-100%) !important;
                                        left: 100% !important;
                                    @else
                                        transform: none !important;
                                        left: 0 !important;
                                    @endif
                                ">{!! $element['text'] ?? '' !!}</p>
                            @elseif($element['type'] === 'image' && $imageSrc)
                                <img src="{{ $imageSrc }}" 
                                    style="
                                        @if(isset($element['width'])) width: {{ $element['width'] }}pt; @endif
                                        @if(isset($element['height'])) height: {{ $element['height'] }}pt; @endif
                                        object-fit: contain;
                                        margin: 0;
                                        padding: 0;
                                        display: block;
                                    ">
                            @endif
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
</body>
</html>
