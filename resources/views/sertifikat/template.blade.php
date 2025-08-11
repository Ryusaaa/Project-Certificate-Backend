<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sertifikat</title>
    <style>
        @php
            function getFontBase64($fontPath) {
                if (file_exists($fontPath)) {
                    return base64_encode(file_get_contents($fontPath));
                }
                return null;
            }
            
            $fontRegular = getFontBase64(storage_path('app/public/fonts/league-spartan/LeagueSpartan-Regular.ttf'));
            $fontMedium = getFontBase64(storage_path('app/public/fonts/league-spartan/LeagueSpartan-Medium.ttf'));
            $fontSemiBold = getFontBase64(storage_path('app/public/fonts/league-spartan/LeagueSpartan-SemiBold.ttf'));
            $fontBold = getFontBase64(storage_path('app/public/fonts/league-spartan/LeagueSpartan-Bold.ttf'));
        @endphp
        
        @if($fontRegular)
        @font-face {
            font-family: 'League Spartan';
            src: url('data:font/truetype;charset=utf-8;base64,{{ $fontRegular }}') format('truetype');
            font-weight: 400;
            font-style: normal;
        }
        @endif
        
        @if($fontMedium)
        @font-face {
            font-family: 'League Spartan';
            src: url('data:font/truetype;charset=utf-8;base64,{{ $fontMedium }}') format('truetype');
            font-weight: 500;
            font-style: normal;
        }
        @endif
        
        @if($fontSemiBold)
        @font-face {
            font-family: 'League Spartan';
            src: url('data:font/truetype;charset=utf-8;base64,{{ $fontSemiBold }}') format('truetype');
            font-weight: 600;
            font-style: normal;
        }
        @endif
        
        @if($fontBold)
        @font-face {
            font-family: 'League Spartan';
            src: url('data:font/truetype;charset=utf-8;base64,{{ $fontBold }}') format('truetype');
            font-weight: 700;
            font-style: normal;
        }
        @endif
        @page {
            margin: 0;
            padding: 0;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
    </style>
    @php
        $fonts = [
            'League Spartan' => [
                'folder' => 'league-spartan',
                'prefix' => 'LeagueSpartan',
            ],
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
            ],
            'Alice' => [
                'folder' => 'Alice',
                'prefix' => 'Alice',
            ],
            'Allura' => [
                'folder' => 'Allura',
                'prefix' => 'Allura',
            ],
            'Anonymous Pro' => [
                'folder' => 'Anonymous_Pro',
                'prefix' => 'AnonymousPro',
            ],
            'Anton' => [
                'folder' => 'Anton',
                'prefix' => 'Anton',
            ],
            'Arapey' => [
                'folder' => 'Arapey',
                'prefix' => 'Arapey',
            ],
            'Archivo Black' => [
                'folder' => 'Archivo_Black',
                'prefix' => 'ArchivoBlack',
            ],
            'Arimo' => [
                'folder' => 'Arimo',
                'prefix' => 'Arimo',
            ],
            'Barlow' => [
                'folder' => 'Barlow',
                'prefix' => 'Barlow',
            ],
            'Bebas Neue' => [
                'folder' => 'Bebas_Neue',
                'prefix' => 'BebasNeue',
            ],
            'Belleza' => [
                'folder' => 'Belleza',
                'prefix' => 'Belleza',
            ],
            'Bree Serif' => [
                'folder' => 'Bree_Serif',
                'prefix' => 'BreeSerif',
            ],
            'Bryndan Write' => [
                'folder' => 'Bryndan Write',
                'prefix' => 'BryndanWrite',
            ],
            'Chewy' => [
                'folder' => 'Chewy',
                'prefix' => 'Chewy',
            ],
            'Chunkfive Ex' => [
                'folder' => 'chunkfive_ex',
                'prefix' => 'ChunkfiveEx',
            ],
            'Cormorant Garamond' => [
                'folder' => 'Cormorant_Garamond',
                'prefix' => 'CormorantGaramond',
            ],
            'DM Sans' => [
                'folder' => 'DM_Sans',
                'prefix' => 'DMSans',
            ],
            'DM Serif Display' => [
                'folder' => 'DM_Serif_Display',
                'prefix' => 'DMSerifDisplay',
            ],
            'Forum' => [
                'folder' => 'Forum',
                'prefix' => 'Forum',
            ],
            'Great Vibes' => [
                'folder' => 'Great_Vibes',
                'prefix' => 'GreatVibes',
            ],
            'Hammersmith One' => [
                'folder' => 'Hammersmith_One',
                'prefix' => 'HammersmithOne',
            ],
            'Inria Serif' => [
                'folder' => 'Inria_Serif',
                'prefix' => 'InriaSerif',
            ],
            'Inter' => [
                'folder' => 'Inter',
                'prefix' => 'Inter',
            ],
            'League Gothic' => [
                'folder' => 'League_Gothic',
                'prefix' => 'LeagueGothic',
            ],
            'League Spartan' => [
                'folder' => 'League_Spartan',
                'prefix' => 'LeagueSpartan',
            ],
            'Libre Baskerville' => [
                'folder' => 'Libre_Baskerville',
                'prefix' => 'LibreBaskerville',
            ],
            'Lora' => [
                'folder' => 'Lora',
                'prefix' => 'Lora',
            ],
            'Merriweather' => [
                'folder' => 'Merriweather',
                'prefix' => 'Merriweather',
            ],
            'Nunito' => [
                'folder' => 'Nunito',
                'prefix' => 'Nunito',
            ],
            'Open Sans' => [
                'folder' => 'Open_Sans',
                'prefix' => 'OpenSans',
            ],
            'Oswald' => [
                'folder' => 'Oswald',
                'prefix' => 'Oswald',
            ],
            'Questrial' => [
                'folder' => 'Questrial',
                'prefix' => 'Questrial',
            ],
            'Quicksand' => [
                'folder' => 'Quicksand',
                'prefix' => 'Quicksand',
            ],
            'Raleway' => [
                'folder' => 'Raleway',
                'prefix' => 'Raleway',
            ],
            'Roboto' => [
                'folder' => 'Roboto',
                'prefix' => 'Roboto',
            ],
            'Shrikhand' => [
                'folder' => 'Shrikhand',
                'prefix' => 'Shrikhand',
            ],
            'Tenor Sans' => [
                'folder' => 'Tenor_Sans',
                'prefix' => 'TenorSans',
            ],
            'Yeseva One' => [
                'folder' => 'Yeseva_One',
                'prefix' => 'YesevaOne',
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
            src: local('Times New Roman'), 
                 url('{{ public_path("fonts/times-new-roman/times-new-roman.ttf") }}') format('truetype');
            font-weight: normal;
            font-style: normal;
            font-display: block;
        }
        @font-face {
            font-family: 'Helvetica';
            src: local('Helvetica'), 
                 url('{{ public_path("fonts/helvetica/Helvetica.ttf") }}') format('truetype');
            font-weight: normal;
            font-style: normal;
            font-display: block;
        }
        @font-face {
            font-family: 'Georgia';
            src: local('Georgia'), 
                 url('{{ public_path("fonts/georgia/georgia.ttf") }}') format('truetype');
            font-weight: normal;
            font-style: normal;
            font-display: block;
        }
        @font-face {
            font-family: 'Arial';
            src: local('Arial');
            font-weight: normal;
            font-style: normal;
            font-display: block;
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
            line-height: 1 !important;
            position: absolute !important;
            transform-origin: left top !important;
            display: block !important;
            max-width: none !important;
            height: auto !important;
            box-sizing: border-box !important;
            /* Perbaikan font metrics dan rendering */
            text-rendering: geometricPrecision !important;
            -webkit-font-smoothing: antialiased !important;
            -moz-osx-font-smoothing: grayscale !important;
            font-kerning: normal !important;
            font-feature-settings: "kern" 1, "liga" 1 !important;
            -webkit-text-size-adjust: 100% !important;
            shape-rendering: crispEdges !important;
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
                                // Adjust Y position and X offset based on font metrics
                                $fontFamily = $element['font']['family'] ?? 'Arial';
                                // Koreksi posisi vertikal dan horizontal [y, x]
                                $fontMetricsOffset = [
                                    // System Fonts - baseline reference
                                    'Arial' => [0, 0],
                                    'Times New Roman' => [floor(min($fontSize * 0.015, 1)), floor(min($fontSize * 0.005, 0.5))],
                                    'Helvetica' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                    'Georgia' => [floor(min($fontSize * 0.018, 1.2)), floor(min($fontSize * 0.006, 0.6))],
                                    // Semua font lain offset kecil agar konsisten
                                                    'Montserrat' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Poppins' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Open Sans' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Roboto' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Nunito' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Inter' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Oswald' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Quicksand' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Raleway' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Arimo' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Barlow' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'DM Sans' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'League Spartan' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Anonymous Pro' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Anton' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Archivo Black' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Bebas Neue' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Hammersmith One' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Questrial' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Tenor Sans' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Playfair Display' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Bree Serif' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Cormorant Garamond' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'DM Serif Display' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Libre Baskerville' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Lora' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Merriweather' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Alice' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Arapey' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Belleza' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Forum' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Inria Serif' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Allura' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Bryndan Write' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Chewy' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Chunkfive Ex' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Great Vibes' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'League Gothic' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Shrikhand' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                                    'Yeseva One' => [floor(min($fontSize * 0.012, 0.8)), floor(min($fontSize * 0.005, 0.5))],
                                    
                                    // Sans-serif fonts dengan koreksi dinamis [y, x]
                                    'Montserrat' => [min($fontSize * 0.045, 4.5), min($fontSize * 0.025, 2.5)],
                                    'Poppins' => [min($fontSize * 0.04, 4), min($fontSize * 0.02, 2)],
                                    'Open Sans' => [min($fontSize * 0.035, 3.5), min($fontSize * 0.02, 2)],
                                    'Roboto' => [min($fontSize * 0.035, 3.5), min($fontSize * 0.02, 2)],
                                    'Nunito' => [min($fontSize * 0.04, 4), min($fontSize * 0.025, 2.5)],
                                    'Inter' => [min($fontSize * 0.035, 3.5), min($fontSize * 0.02, 2)],
                                    'Oswald' => [min($fontSize * 0.03, 3), min($fontSize * 0.02, 2)],
                                    'Quicksand' => [min($fontSize * 0.04, 4), min($fontSize * 0.02, 2)],
                                    'Raleway' => [min($fontSize * 0.035, 3.5), min($fontSize * 0.02, 2)],
                                    'Arimo' => [min($fontSize * 0.035, 3.5), min($fontSize * 0.02, 2)],
                                    'Barlow' => [min($fontSize * 0.035, 3.5), min($fontSize * 0.02, 2)],
                                    'DM Sans' => [min($fontSize * 0.035, 3.5), min($fontSize * 0.02, 2)],
                                    'League Spartan' => [min($fontSize * 0.035, 3.5), min($fontSize * 0.025, 2.5)],
                                    'Anonymous Pro' => [min($fontSize * 0.03, 3), min($fontSize * 0.02, 2)],
                                    'Anton' => [min($fontSize * 0.03, 3), min($fontSize * 0.02, 2)],
                                    'Archivo Black' => [min($fontSize * 0.04, 4), min($fontSize * 0.02, 2)],
                                    'Bebas Neue' => [min($fontSize * 0.03, 3), min($fontSize * 0.02, 2)],
                                    'Hammersmith One' => [min($fontSize * 0.04, 4), min($fontSize * 0.02, 2)],
                                    'Questrial' => [min($fontSize * 0.04, 4), min($fontSize * 0.02, 2)],
                                    'Tenor Sans' => [min($fontSize * 0.04, 4), min($fontSize * 0.02, 2)],
                                    
                                    // Serif fonts dengan koreksi proporsional [y, x]
                                    'Playfair Display' => [min($fontSize * 0.045, 4.5), min($fontSize * 0.025, 2.5)],
                                    'Bree Serif' => [min($fontSize * 0.045, 4.5), min($fontSize * 0.025, 2.5)],
                                    'Cormorant Garamond' => [min($fontSize * 0.05, 5), min($fontSize * 0.03, 3)],
                                    'DM Serif Display' => [min($fontSize * 0.05, 5), min($fontSize * 0.03, 3)],
                                    'Libre Baskerville' => [min($fontSize * 0.05, 5), min($fontSize * 0.03, 3)],
                                    'Lora' => [min($fontSize * 0.05, 5), min($fontSize * 0.03, 3)],
                                    'Merriweather' => [min($fontSize * 0.05, 5), min($fontSize * 0.03, 3)],
                                    'Alice' => [min($fontSize * 0.045, 4.5), min($fontSize * 0.025, 2.5)],
                                    'Arapey' => [min($fontSize * 0.045, 4.5), min($fontSize * 0.025, 2.5)],
                                    'Belleza' => [min($fontSize * 0.04, 4), min($fontSize * 0.02, 2)],
                                    'Forum' => [min($fontSize * 0.045, 4.5), min($fontSize * 0.025, 2.5)],
                                    'Inria Serif' => [min($fontSize * 0.045, 4.5), min($fontSize * 0.025, 2.5)],
                                    
                                    // Display/Decorative fonts dengan koreksi khusus [y, x]
                                    'Allura' => [min($fontSize * 0.06, 6), min($fontSize * 0.03, 3)],
                                    'Bryndan Write' => [min($fontSize * 0.06, 6), min($fontSize * 0.03, 3)],
                                    'Chewy' => [min($fontSize * 0.045, 4.5), min($fontSize * 0.025, 2.5)],
                                    'Chunkfive Ex' => [min($fontSize * 0.045, 4.5), min($fontSize * 0.025, 2.5)],
                                    'Great Vibes' => [min($fontSize * 0.06, 6), min($fontSize * 0.035, 3.5)],
                                    'League Gothic' => [min($fontSize * 0.04, 4), min($fontSize * 0.02, 2)],
                                    'Shrikhand' => [min($fontSize * 0.045, 4.5), min($fontSize * 0.025, 2.5)],
                                    'Yeseva One' => [min($fontSize * 0.045, 4.5), min($fontSize * 0.025, 2.5)]
                                ];
                                
                                // Get offsets for the current font, default to Arial's offset [0, 0]
                                $offsets = $fontMetricsOffset[$fontFamily] ?? [0, 0];
                                $y += $offsets[0];  // Vertical offset
                                // Untuk horizontal offset, batasi agar tidak terlalu kiri kecuali Arial
                                if ($fontFamily !== 'Arial') {
                                    $x += max($offsets[1], 2); // Minimal offset 2pt agar tidak terlalu kiri
                                } else {
                                    $x += $offsets[1];
                                }
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
                                    text-align: {{ $element['textAlign'] ?? 'center' }} !important;
                                    color: {{ $element['color'] ?? '#000000' }} !important;
                                    margin: 0 !important;
                                    padding: 0 !important;
                                    line-height: 1 !important;
                                    white-space: nowrap !important;
                                    overflow: visible !important;
                                    vertical-align: baseline !important;
                                    display: block !important;
                                    position: absolute !important;
                                    transform-origin: left top !important;
                                    letter-spacing: normal !important;
                                    word-spacing: normal !important;
                                    /* Tambahan untuk stabilitas rendering */
                                    text-rendering: geometricPrecision !important;
                                    -webkit-font-smoothing: antialiased !important;
                                    -moz-osx-font-smoothing: grayscale !important;
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
