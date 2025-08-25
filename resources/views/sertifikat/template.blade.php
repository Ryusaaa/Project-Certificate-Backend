<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sertifikat</title>
    {{-- Inline critical CSS so PDF generator (dompdf) picks it up reliably --}}
    <link href="{{ asset('css/all-fonts.css') }}" rel="stylesheet">
    <style>
        @php
            // --- FONT MAPPING CONFIGURATION ---
            $fontMappings = [
                'Alice' => [
                    'folder' => 'Alice',
                    'variants' => [
                        '400' => ['normal' => 'Alice-Regular.ttf']
                    ]
                ],
                'Breathing' => [
                    'folder' => 'breathing',
                    'variants' => [
                        '400' => ['normal' => 'Breathing Personal Use Only.ttf']
                    ]
                ],
                'Brighter' => [
                    'folder' => 'brighter',
                    'variants' => [
                        '400' => ['normal' => 'Brighter Regular.otf']
                    ]
                ],
                'Brittany' => [
                    'folder' => 'brittany_2',
                    'variants' => [
                        '400' => ['normal' => 'Brittany.ttf']
                    ]
                ],
                'Bryndan Write' => [
                    'folder' => 'Bryndan Write',
                    'variants' => [
                        '400' => ['normal' => 'BryndanWriteBook.ttf']
                    ]
                ],
                'Caitlin Angelica' => [
                    'folder' => 'caitlin_angelica',
                    'variants' => [
                        '400' => ['normal' => 'Caitlin Angelica.ttf', 'italic' => 'Caitlin Angelica Italic.ttf']
                    ]
                ],
                'Chau Philomene One' => [
                    'folder' => 'Chau_Philomene_One',
                    'variants' => [
                        '400' => ['normal' => 'ChauPhilomeneOne-Regular.ttf']
                    ]
                ],
                'Chewy' => [
                    'folder' => 'Chewy',
                    'variants' => [
                        '400' => ['normal' => 'Chewy-Regular.ttf']
                    ]
                ],
                'Chunkfive' => [
                    'folder' => 'chunkfive_ex',
                    'variants' => [
                        '400' => ['normal' => 'Chunkfive.ttf']
                    ]
                ],
                'Cormorant Garamond' => [
                    'folder' => 'Cormorant_Garamond',
                    'variants' => [
                        '400' => ['normal' => 'CormorantGaramond-Regular.ttf', 'italic' => 'CormorantGaramond-Italic.ttf'],
                        '500' => ['normal' => 'CormorantGaramond-Medium.ttf', 'italic' => 'CormorantGaramond-MediumItalic.ttf'],
                        '600' => ['normal' => 'CormorantGaramond-SemiBold.ttf', 'italic' => 'CormorantGaramond-SemiBoldItalic.ttf'],
                        '700' => ['normal' => 'CormorantGaramond-Bold.ttf', 'italic' => 'CormorantGaramond-BoldItalic.ttf']
                    ]
                ],
                'DM Sans' => [
                    'folder' => 'DM_Sans',
                    'variants' => [
                        '400' => ['normal' => 'DMSans-Regular.ttf', 'italic' => 'DMSans-Italic.ttf'],
                        '500' => ['normal' => 'DMSans-Medium.ttf', 'italic' => 'DMSans-MediumItalic.ttf'],
                        '700' => ['normal' => 'DMSans-Bold.ttf', 'italic' => 'DMSans-BoldItalic.ttf']
                    ]
                ],
                'DM Serif Display' => [
                    'folder' => 'DM_Serif_Display',
                    'variants' => [
                        '400' => ['normal' => 'DMSerifDisplay-Regular.ttf', 'italic' => 'DMSerifDisplay-Italic.ttf']
                    ]
                ],
                'Forum' => [
                    'folder' => 'Forum',
                    'variants' => [
                        '400' => ['normal' => 'Forum-Regular.ttf']
                    ]
                ],
                'Gentry Benedict' => [
                    'folder' => 'gentry_benedict',
                    'variants' => [
                        '400' => ['normal' => 'Gentry Benedict.otf']
                    ]
                ],
                'Hammersmith One' => [
                    'folder' => 'Hammersmith_One',
                    'variants' => [
                        '400' => ['normal' => 'HammersmithOne-Regular.ttf']
                    ]
                ],
                'Inria Serif' => [
                    'folder' => 'Inria_Serif',
                    'variants' => [
                        '400' => ['normal' => 'InriaSerif-Regular.ttf', 'italic' => 'InriaSerif-Italic.ttf'],
                        '700' => ['normal' => 'InriaSerif-Bold.ttf', 'italic' => 'InriaSerif-BoldItalic.ttf']
                    ]
                ],
                'Inter' => [
                    'folder' => 'Inter',
                    'variants' => [
                        '400' => ['normal' => 'Inter-Regular.ttf'],
                        '500' => ['normal' => 'Inter-Medium.ttf'],
                        '600' => ['normal' => 'Inter-SemiBold.ttf'],
                        '700' => ['normal' => 'Inter-Bold.ttf']
                    ]
                ],
                'League Gothic' => [
                    'folder' => 'League_Gothic',
                    'variants' => [
                        '400' => ['normal' => 'LeagueGothic-Regular.ttf']
                    ]
                ],
                'Libre Baskerville' => [
                    'folder' => 'Libre_Baskerville',
                    'variants' => [
                        '400' => ['normal' => 'LibreBaskerville-Regular.ttf', 'italic' => 'LibreBaskerville-Italic.ttf'],
                        '700' => ['normal' => 'LibreBaskerville-Bold.ttf']
                    ]
                ],
                'Lora' => [
                    'folder' => 'Lora',
                    'variants' => [
                        '400' => ['normal' => 'Lora-Regular.ttf', 'italic' => 'Lora-Italic.ttf'],
                        '500' => ['normal' => 'Lora-Medium.ttf', 'italic' => 'Lora-MediumItalic.ttf'],
                        '600' => ['normal' => 'Lora-SemiBold.ttf', 'italic' => 'Lora-SemiBoldItalic.ttf'],
                        '700' => ['normal' => 'Lora-Bold.ttf', 'italic' => 'Lora-BoldItalic.ttf']
                    ]
                ],
                'Merriweather' => [
                    'folder' => 'Merriweather',
                    'variants' => [
                        '300' => ['normal' => 'Merriweather-Light.ttf', 'italic' => 'Merriweather-LightItalic.ttf'],
                        '400' => ['normal' => 'Merriweather-Regular.ttf', 'italic' => 'Merriweather-Italic.ttf'],
                        '700' => ['normal' => 'Merriweather-Bold.ttf', 'italic' => 'Merriweather-BoldItalic.ttf'],
                        '900' => ['normal' => 'Merriweather-Black.ttf', 'italic' => 'Merriweather-BlackItalic.ttf']
                    ]
                ],
                'More Sugar' => [
                    'folder' => 'more_sugar',
                    'variants' => [
                        '400' => ['normal' => 'More Sugar.ttf']
                    ]
                ],
                'Nunito' => [
                    'folder' => 'Nunito',
                    'variants' => [
                        '200' => ['normal' => 'Nunito-ExtraLight.ttf', 'italic' => 'Nunito-ExtraLightItalic.ttf'],
                        '300' => ['normal' => 'Nunito-Light.ttf', 'italic' => 'Nunito-LightItalic.ttf'],
                        '400' => ['normal' => 'Nunito-Regular.ttf', 'italic' => 'Nunito-Italic.ttf'],
                        '500' => ['normal' => 'Nunito-Medium.ttf', 'italic' => 'Nunito-MediumItalic.ttf'],
                        '600' => ['normal' => 'Nunito-SemiBold.ttf', 'italic' => 'Nunito-SemiBoldItalic.ttf'],
                        '700' => ['normal' => 'Nunito-Bold.ttf', 'italic' => 'Nunito-BoldItalic.ttf'],
                        '800' => ['normal' => 'Nunito-ExtraBold.ttf', 'italic' => 'Nunito-ExtraBoldItalic.ttf'],
                        '900' => ['normal' => 'Nunito-Black.ttf', 'italic' => 'Nunito-BlackItalic.ttf']
                    ]
                ],
                'Open Sans' => [
                    'folder' => 'Open_Sans',
                    'variants' => [
                        '300' => ['normal' => 'OpenSans-Light.ttf', 'italic' => 'OpenSans-LightItalic.ttf'],
                        '400' => ['normal' => 'OpenSans-Regular.ttf', 'italic' => 'OpenSans-Italic.ttf'],
                        '500' => ['normal' => 'OpenSans-Medium.ttf', 'italic' => 'OpenSans-MediumItalic.ttf'],
                        '600' => ['normal' => 'OpenSans-SemiBold.ttf', 'italic' => 'OpenSans-SemiBoldItalic.ttf'],
                        '700' => ['normal' => 'OpenSans-Bold.ttf', 'italic' => 'OpenSans-BoldItalic.ttf'],
                        '800' => ['normal' => 'OpenSans-ExtraBold.ttf', 'italic' => 'OpenSans-ExtraBoldItalic.ttf']
                    ]
                ],
                'Oswald' => [
                    'folder' => 'Oswald',
                    'variants' => [
                        '200' => ['normal' => 'Oswald-ExtraLight.ttf'],
                        '300' => ['normal' => 'Oswald-Light.ttf'],
                        '400' => ['normal' => 'Oswald-Regular.ttf'],
                        '500' => ['normal' => 'Oswald-Medium.ttf'],
                        '600' => ['normal' => 'Oswald-SemiBold.ttf'],
                        '700' => ['normal' => 'Oswald-Bold.ttf']
                    ]
                ],
                'Questrial' => [
                    'folder' => 'Questrial',
                    'variants' => [
                        '400' => ['normal' => 'Questrial-Regular.ttf']
                    ]
                ],
                'Quicksand' => [
                    'folder' => 'Quicksand',
                    'variants' => [
                        '300' => ['normal' => 'Quicksand-Light.ttf'],
                        '400' => ['normal' => 'Quicksand-Regular.ttf'],
                        '500' => ['normal' => 'Quicksand-Medium.ttf'],
                        '600' => ['normal' => 'Quicksand-SemiBold.ttf'],
                        '700' => ['normal' => 'Quicksand-Bold.ttf']
                    ]
                ],
                'Railey' => [
                    'folder' => 'railey',
                    'variants' => [
                        '400' => ['normal' => 'Railey.ttf']
                    ]
                ],
                'Raleway' => [
                    'folder' => 'Raleway',
                    'variants' => [
                        '100' => ['normal' => 'Raleway-Thin.ttf', 'italic' => 'Raleway-ThinItalic.ttf'],
                        '200' => ['normal' => 'Raleway-ExtraLight.ttf', 'italic' => 'Raleway-ExtraLightItalic.ttf'],
                        '300' => ['normal' => 'Raleway-Light.ttf', 'italic' => 'Raleway-LightItalic.ttf'],
                        '400' => ['normal' => 'Raleway-Regular.ttf', 'italic' => 'Raleway-Italic.ttf'],
                        '500' => ['normal' => 'Raleway-Medium.ttf', 'italic' => 'Raleway-MediumItalic.ttf'],
                        '600' => ['normal' => 'Raleway-SemiBold.ttf', 'italic' => 'Raleway-SemiBoldItalic.ttf'],
                        '700' => ['normal' => 'Raleway-Bold.ttf', 'italic' => 'Raleway-BoldItalic.ttf'],
                        '800' => ['normal' => 'Raleway-ExtraBold.ttf', 'italic' => 'Raleway-ExtraBoldItalic.ttf'],
                        '900' => ['normal' => 'Raleway-Black.ttf', 'italic' => 'Raleway-BlackItalic.ttf']
                    ]
                ],
                'Roboto' => [
                    'folder' => 'Roboto',
                    'variants' => [
                        '100' => ['normal' => 'Roboto-Thin.ttf', 'italic' => 'Roboto-ThinItalic.ttf'],
                        '300' => ['normal' => 'Roboto-Light.ttf', 'italic' => 'Roboto-LightItalic.ttf'],
                        '400' => ['normal' => 'Roboto-Regular.ttf', 'italic' => 'Roboto-Italic.ttf'],
                        '500' => ['normal' => 'Roboto-Medium.ttf', 'italic' => 'Roboto-MediumItalic.ttf'],
                        '700' => ['normal' => 'Roboto-Bold.ttf', 'italic' => 'Roboto-BoldItalic.ttf'],
                        '900' => ['normal' => 'Roboto-Black.ttf', 'italic' => 'Roboto-BlackItalic.ttf']
                    ]
                ],
                'Shrikhand' => [
                    'folder' => 'Shrikhand',
                    'variants' => [
                        '400' => ['normal' => 'Shrikhand-Regular.ttf']
                    ]
                ],
                'Tenor Sans' => [
                    'folder' => 'Tenor_Sans',
                    'variants' => [
                        '400' => ['normal' => 'TenorSans-Regular.ttf']
                    ]
                ],
                'Yeseva One' => [
                    'folder' => 'Yeseva_One',
                    'variants' => [
                        '400' => ['normal' => 'YesevaOne-Regular.ttf']
                    ]
                ],
                'Allura' => [
                    'folder' => 'Allura',
                    'variants' => [
                        '400' => ['normal' => 'Allura-Regular.ttf']
                    ]
                ],
                'Anonymous Pro' => [
                    'folder' => 'Anonymous_Pro',
                    'variants' => [
                        '400' => ['normal' => 'AnonymousPro-Regular.ttf', 'italic' => 'AnonymousPro-Italic.ttf'],
                        '700' => ['normal' => 'AnonymousPro-Bold.ttf', 'italic' => 'AnonymousPro-BoldItalic.ttf']
                    ]
                ],
                'Anton' => [
                    'folder' => 'Anton',
                    'variants' => [
                        '400' => ['normal' => 'Anton-Regular.ttf']
                    ]
                ],
                'Arapey' => [
                    'folder' => 'Arapey',
                    'variants' => [
                        '400' => ['normal' => 'Arapey-Regular.ttf', 'italic' => 'Arapey-Italic.ttf']
                    ]
                ],
                'Archivo Black' => [
                    'folder' => 'Archivo_Black',
                    'variants' => [
                        '400' => ['normal' => 'ArchivoBlack-Regular.ttf']
                    ]
                ],
                'Arimo' => [
                    'folder' => 'Arimo',
                    'variants' => [
                        '400' => ['normal' => 'Arimo-Regular.ttf', 'italic' => 'Arimo-Italic.ttf'],
                        '500' => ['normal' => 'Arimo-Medium.ttf', 'italic' => 'Arimo-MediumItalic.ttf'],
                        '600' => ['normal' => 'Arimo-SemiBold.ttf', 'italic' => 'Arimo-SemiBoldItalic.ttf'],
                        '700' => ['normal' => 'Arimo-Bold.ttf', 'italic' => 'Arimo-BoldItalic.ttf']
                    ]
                ],
                'Barlow' => [
                    'folder' => 'Barlow',
                    'variants' => [
                        '400' => ['normal' => 'Barlow-Regular.ttf', 'italic' => 'Barlow-Italic.ttf'],
                        '500' => ['normal' => 'Barlow-Medium.ttf', 'italic' => 'Barlow-MediumItalic.ttf'],
                        '600' => ['normal' => 'Barlow-SemiBold.ttf', 'italic' => 'Barlow-SemiBoldItalic.ttf'],
                        '700' => ['normal' => 'Barlow-Bold.ttf', 'italic' => 'Barlow-BoldItalic.ttf']
                    ]
                ],
                'Bebas Neue' => [
                    'folder' => 'Bebas_Neue',
                    'variants' => [
                        '400' => ['normal' => 'BebasNeue-Regular.ttf']
                    ]
                ],
                'Belleza' => [
                    'folder' => 'Belleza',
                    'variants' => [
                        '400' => ['normal' => 'Belleza-Regular.ttf']
                    ]
                ],
                'Bree Serif' => [
                    'folder' => 'Bree_Serif',
                    'variants' => [
                        '400' => ['normal' => 'BreeSerif-Regular.ttf']
                    ]
                ],
                'Great Vibes' => [
                    'folder' => 'Great_Vibes',
                    'variants' => [
                        '400' => ['normal' => 'GreatVibes-Regular.ttf']
                    ]
                ],
                'League Spartan' => [
                    'folder' => 'League_Spartan',
                    'variants' => [
                        '400' => ['normal' => 'LeagueSpartan-Regular.ttf'],
                        '700' => ['normal' => 'LeagueSpartan-Bold.ttf']
                    ]
                ],
                'Montserrat' => [
                    'folder' => 'Montserrat',
                    'variants' => [
                        '400' => ['normal' => 'Montserrat-Regular.ttf', 'italic' => 'Montserrat-Italic.ttf'],
                        '500' => ['normal' => 'Montserrat-Medium.ttf', 'italic' => 'Montserrat-MediumItalic.ttf'],
                        '600' => ['normal' => 'Montserrat-SemiBold.ttf', 'italic' => 'Montserrat-SemiBoldItalic.ttf'],
                        '700' => ['normal' => 'Montserrat-Bold.ttf', 'italic' => 'Montserrat-BoldItalic.ttf']
                    ]
                ],
                'Playfair Display' => [
                    'folder' => 'Playfair_Display',
                    'variants' => [
                        '400' => ['normal' => 'PlayfairDisplay-Regular.ttf', 'italic' => 'PlayfairDisplay-Italic.ttf'],
                        '700' => ['normal' => 'PlayfairDisplay-Bold.ttf', 'italic' => 'PlayfairDisplay-BoldItalic.ttf']
                    ]
                ],
                'Poppins' => [
                    'folder' => 'Poppins',
                    'variants' => [
                        '100' => ['normal' => 'Poppins-Thin.ttf', 'italic' => 'Poppins-ThinItalic.ttf'],
                        '200' => ['normal' => 'Poppins-ExtraLight.ttf', 'italic' => 'Poppins-ExtraLightItalic.ttf'],
                        '300' => ['normal' => 'Poppins-Light.ttf', 'italic' => 'Poppins-LightItalic.ttf'],
                        '400' => ['normal' => 'Poppins-Regular.ttf', 'italic' => 'Poppins-Italic.ttf'],
                        '500' => ['normal' => 'Poppins-Medium.ttf', 'italic' => 'Poppins-MediumItalic.ttf'],
                        '600' => ['normal' => 'Poppins-SemiBold.ttf', 'italic' => 'Poppins-SemiBoldItalic.ttf'],
                        '700' => ['normal' => 'Poppins-Bold.ttf', 'italic' => 'Poppins-BoldItalic.ttf'],
                        '800' => ['normal' => 'Poppins-ExtraBold.ttf', 'italic' => 'Poppins-ExtraBoldItalic.ttf'],
                        '900' => ['normal' => 'Poppins-Black.ttf', 'italic' => 'Poppins-BlackItalic.ttf']
                    ]
                ],
                'Arial' => ['type' => 'system'],
                'Times New Roman' => ['type' => 'system'],
                'Helvetica' => ['type' => 'system']
            ];

            $pageWidth = 842;
            $pageHeight = 595;

            // --- FONT LOADING LOGIC ---
            $requiredFonts = [];
            if (isset($elements) && is_array($elements)) {
                foreach ($elements as $el) {
                    if (!isset($el['type']) || $el['type'] !== 'text') continue;
                    
                    $f = isset($el['font']) ? $el['font'] : null;
                    if (!$f) continue;

                    $fontFamily = isset($f['family']) ? $f['family'] : null;
                    if (!$fontFamily || !isset($fontMappings[$fontFamily])) continue;

                    $fontInfo = $fontMappings[$fontFamily];
                    if (isset($fontInfo['type']) && $fontInfo['type'] === 'system') continue;

                    $folder = $fontInfo['folder'];
                    $weight = isset($f['weight']) ? $f['weight'] : '400';
                    $style = isset($f['style']) ? $f['style'] : 'normal';
                    
                    $variantFile = null;
                    if (isset($fontInfo['variants'][$weight][$style])) {
                        $variantFile = $fontInfo['variants'][$weight][$style];
                    } elseif (isset($fontInfo['variants'][$weight]['normal'])) {
                        $variantFile = $fontInfo['variants'][$weight]['normal'];
                    } elseif (isset($fontInfo['variants']['400']['normal'])) {
                        $variantFile = $fontInfo['variants']['400']['normal'];
                    }

                    if ($folder && $variantFile) {
                        $key = "{$fontFamily}||{$weight}||{$style}";
                        if (!isset($requiredFonts[$key])) {
                            $requiredFonts[$key] = [
                                'family' => $fontFamily,
                                'folder' => $folder,
                                'file' => $variantFile,
                                'weight' => $weight,
                                'style' => $style
                            ];
                        }
                    }
                }
            }

            // Generate @font-face declarations
            foreach ($requiredFonts as $info) {
                $fontPath = public_path('fonts/' . $info['folder'] . '/' . $info['file']);
                if (file_exists($fontPath)) {
                    $fontBase64 = base64_encode(file_get_contents($fontPath));
                    $ext = strtolower(pathinfo($info['file'], PATHINFO_EXTENSION));
                    $format = $ext === 'otf' ? 'opentype' : ($ext === 'woff2' ? 'woff2' : ($ext === 'woff' ? 'woff' : 'truetype'));
                    
                    echo "@font-face {
                        font-family: '{$info['family']}';
                        src: url('data:font/{$format};charset=utf-8;base64,{$fontBase64}') format('{$format}');
                        font-weight: {$info['weight']};
                        font-style: {$info['style']};
                    }\n";
                }
            }
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
            width: {{ $pageWidth }}pt;
            height: {{ $pageHeight }}pt;
            position: relative;
            font-family: Arial, sans-serif;
        }
        .certificate-container {
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
            height: 100%;
            z-index: 1;
        }
        .element {
            position: absolute;
            z-index: 2;
            white-space: pre-wrap;
            line-height: 1.2;
            transform-origin: top left;
        }
        .element-text {
            display: inline-block;
        }
        .element-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .element-qrcode {
            position: absolute !important;
            z-index: 9999 !important; /* ensure top-most */
            background: transparent !important; /* keep transparent so PNG background shows through */
            border-radius: 0 !important;
            border: none !important;
            overflow: visible !important; /* allow full QR rendering */
            box-shadow: none !important;
            box-sizing: border-box !important;
            display: block !important;
            transform-origin: center center !important;
            padding: 0 !important;
        }
        /* q-inner will exactly match element box and provide inner padding if needed */
        .element-qrcode .q-inner {
            width: 100% !important;
            height: 100% !important;
            box-sizing: border-box !important;
            padding: 6px !important; /* inner white inset so QR doesn't touch edges */
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .element-qrcode img {
            width: 100% !important;
            height: 100% !important;
            max-width: 100% !important;
            max-height: 100% !important;
            display: block !important;
            object-fit: contain !important;
            image-rendering: pixelated !important; /* For sharp QR codes */
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            margin: 0 !important;
        }
        @media print {
            .element-qrcode {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
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
            {{-- Render non-QR elements first so QR elements can be placed above them --}}
            @foreach($elements as $element)
                @if($element['type'] !== 'qrcode')
                    @php
                        $type = $element['type'];
                        $x = $element['x'] ?? 0;
                        $y = $element['y'] ?? 0;
                        $w = $element['width'] ?? null;
                        $h = $element['height'] ?? null;

                        // rotation (degrees) and scale support
                        $rotate = isset($element['rotate']) ? (float) $element['rotate'] : 0;
                        $scaleX = isset($element['scaleX']) ? (float) $element['scaleX'] : 1;
                        $scaleY = isset($element['scaleY']) ? (float) $element['scaleY'] : 1;
                        $transformParts = [];
                        if ($rotate !== 0) $transformParts[] = "rotate({$rotate}deg)";
                        if ($scaleX !== 1 || $scaleY !== 1) $transformParts[] = "scale({$scaleX}, {$scaleY})";

                        // horizontal alignment transforms for text
                        $textAlign = $element['textAlign'] ?? null;
                        if ($textAlign === 'center') $transformParts[] = 'translateX(-50%)';
                        elseif ($textAlign === 'right') $transformParts[] = 'translateX(-100%)';

                        $transformCss = count($transformParts) ? 'transform: ' . implode(' ', $transformParts) . ';' : '';
                    @endphp

                    @if($type === 'text')
                        @php
                            $fontFamily = isset($element['font']['family']) ? "'{$element['font']['family']}', Arial, sans-serif" : 'Arial, sans-serif';
                            $fontWeight = isset($element['font']['weight']) ? $element['font']['weight'] : '400';
                            $fontStyle  = isset($element['font']['style']) ? $element['font']['style'] : 'normal';
                            $fontSize   = isset($element['fontSize']) ? $element['fontSize'] : 16;
                            $color      = isset($element['color']) ? $element['color'] : '#000000';
                            $text       = isset($element['text']) ? $element['text'] : '';

                            $style = "position: absolute; left: {$x}px; top: {$y}px; font-family: {$fontFamily}; font-size: {$fontSize}px; font-weight: {$fontWeight}; font-style: {$fontStyle}; text-align: {$textAlign}; color: {$color}; {$transformCss}";
                        @endphp
                        <div class="element element-text" style="{!! $style !!}">{!! nl2br(e($text)) !!}</div>
                    @elseif($type === 'image')
                        @php
                            $imageSrc = null;
                            if (!empty($element['image_path']) && file_exists(storage_path('app/public/' . $element['image_path']))) {
                                $path = storage_path('app/public/' . $element['image_path']);
                                $ext = pathinfo($path, PATHINFO_EXTENSION);
                                $data = base64_encode(file_get_contents($path));
                                $imageSrc = 'data:image/' . $ext . ';base64,' . $data;
                            }
                        @endphp
                        @if($imageSrc)
                            @php $style = "left: {$x}px; top: {$y}px;"; $sizeStyle = '';
                                if ($w) $sizeStyle .= "width: {$w}px;"; if ($h) $sizeStyle .= "height: {$h}px;";
                            @endphp
                            <div class="element element-image" style="position: absolute; {!! $style !!} {!! $sizeStyle !!} {!! $transformCss !!}">
                                <img src="{{ $imageSrc }}" style="width: 100%; height: 100%;">
                            </div>
                        @endif
                    @endif
                @endif
            @endforeach

            {{-- helper: convert svg/raw base64 QR to transparent PNG using Imagick if available --}}
            @php
                if (!function_exists('qr_to_transparent_png_datauri')) {
                function qr_to_transparent_png_datauri($data, $targetW = 256, $targetH = 256, $margin = null) {
                    // Accept data URI or raw svg/base64 or already png datauri
                    if (!$data) return null;
                    // If it's already a data URI for PNG, return as-is
                    if (strpos($data, 'data:image/png') === 0) return $data;

                    // If it's a full data URI for svg or png, extract payload
                    if (preg_match('#^data:image/[^;]+;base64,(.+)$#', $data, $m)) {
                        $raw = base64_decode($m[1]);
                    } else {
                        // assume raw base64 or raw SVG bytes
                        // try to base64-decode; if fails, use raw string
                        $decoded = base64_decode($data, true);
                        $raw = $decoded === false ? $data : $decoded;
                    }

                    if (!extension_loaded('imagick')) {
                        // fallback: if raw already PNG bytes, return data uri
                        if (strlen($raw) > 8 && substr($raw,0,8) === "\x89PNG\r\n\x1a\n") {
                            return 'data:image/png;base64,' . base64_encode($raw);
                        }
                        // else try to return as svg data uri (dompdf may not support svg well)
                        return 'data:image/svg+xml;base64,' . base64_encode($raw);
                    }

                    try {
                        $im = new Imagick();

                        // Read SVG or PNG
                        // Ensure we use a density for better rasterization
                        $im->setBackgroundColor(new ImagickPixel('transparent'));
                        // set resolution higher for vector sources to get crisp raster
                        $im->setResolution(300,300);

                        // If SVG, readImageBlob will rasterize; if PNG, it will load
                        $im->readImageBlob($raw);

                        // flatten with transparent background
                        $im->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
                        $im->setImageBackgroundColor(new ImagickPixel('transparent'));

                        // compute margin if not provided: 4% of smaller side (smaller margin so QR appears larger)
                        if ($margin === null) {
                            $margin = (int) max(3, round(min($targetW, $targetH) * 0.04));
                        }

                        $innerW = max(1, $targetW - ($margin * 2));
                        $innerH = max(1, $targetH - ($margin * 2));

                        // Resize preserving aspect to fit inner box
                        $im->resizeImage($innerW, $innerH, Imagick::FILTER_LANCZOS, 1, true);

                        // Create canvas (targetW x targetH) and composite centered
                        $canvas = new Imagick();
                        $canvas->newImage($targetW, $targetH, new ImagickPixel('transparent'));
                        $canvas->setImageFormat('png32');

                        $x = (int)(($targetW - $im->getImageWidth()) / 2);
                        $y = (int)(($targetH - $im->getImageHeight()) / 2);
                        $canvas->compositeImage($im, Imagick::COMPOSITE_DEFAULT, $x, $y);

                        // Ensure solid black foreground by converting to RGB and increasing contrast
                        $canvas->transformImageColorspace(Imagick::COLORSPACE_RGB);
                        $canvas->contrastImage(1);

                        $canvas->setImageAlphaChannel(Imagick::ALPHACHANNEL_SET);
                        $png = $canvas->getImageBlob();
                        $canvas->clear(); $canvas->destroy(); $im->clear(); $im->destroy();

                        return 'data:image/png;base64,' . base64_encode($png);
                    } catch (Exception $e) {
                        // on failure, fallback to original (if base64) or svg datauri
                        if (strpos($data, 'data:image') === 0) return $data;
                        return 'data:image/svg+xml;base64,' . base64_encode($raw);
                    }
                }
                }
            @endphp

            {{-- Render QR elements last so they appear above background/artwork --}}
            @foreach($elements as $element)
                @if(isset($element['type']) && $element['type'] === 'qrcode')
                    @php
                        $qrcodeSrc = $element['qrcode'] ?? ($element['content'] ?? '');
                        $x = isset($element['x']) ? (int) $element['x'] : 0;
                        $y = isset($element['y']) ? (int) $element['y'] : 0;
                        $rawW = isset($element['width']) ? (int) $element['width'] : 0;
                        $rawH = isset($element['height']) ? (int) $element['height'] : 0;
                        // respect editor-provided size; enforce minimum (180px) for scan reliability
                        $minSize = 180;
                        $w = $rawW > 0 ? max($rawW, $minSize) : $minSize;
                        $h = $rawH > 0 ? max($rawH, $minSize) : $minSize;
                        $rotate = isset($element['rotate']) ? (float) $element['rotate'] : 0;
                        $scaleX = isset($element['scaleX']) ? (float) $element['scaleX'] : 1;
                        $scaleY = isset($element['scaleY']) ? (float) $element['scaleY'] : 1;
                        $transformParts = [];
                        if ($rotate !== 0) $transformParts[] = "rotate({$rotate}deg)";
                        if ($scaleX !== 1 || $scaleY !== 1) $transformParts[] = "scale({$scaleX}, {$scaleY})";
                        $transformCss = count($transformParts) ? 'transform: ' . implode(' ', $transformParts) . ';' : '';

                        // process via Imagick helper to produce transparent PNG data URI using editor size
                        $processedQr = null;
                        if ($qrcodeSrc) {
                            $processedQr = qr_to_transparent_png_datauri($qrcodeSrc, $w, $h, null);
                            Log::debug("Processed QR code for element at ({$x},{$y}) to {$w}x{$h}: " . $processedQr);
                        }
                    @endphp

                    <div class="element element-qrcode" style="left: {{ $x }}px; top: {{ $y }}px; width: {{ $w }}px; height: {{ $h }}px; transform-origin: center center; {!! $transformCss !!}">
                        @if($processedQr)
                            <img src="{{ $processedQr }}" alt="QR Code" style="background: transparent; display:block; image-rendering: pixelated; width:100%; height:100%;">
                        @else
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: transparent; color:#999;">QR not found</div>
                        @endif
                    </div>
                @endif
            @endforeach
        @endif
    </div>
</body>
</html>
