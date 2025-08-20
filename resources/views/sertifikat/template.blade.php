<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sertifikat</title>
    {{-- Inline critical CSS so PDF generator (dompdf) picks it up reliably --}}
    <link href="{{ asset('css/all-fonts.css') }}" rel="stylesheet">
    <style>
        /* begin inlined certificate.css */
        .certificate { width:100%; height:100%; position:relative; background-repeat:no-repeat; background-position:center; background-size:contain; }
        .element { position:absolute; transform-origin: top left; }
        /* end inlined certificate.css */
        @php
            use App\Helpers\FontHelper;

            // --- STRUKTUR FONT BARU ---
            // Struktur ini lebih detail, memisahkan file untuk setiap kombinasi weight dan style.
            // Font mapping configurations
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
                        '100' => ['normal' => 'Barlow-Thin.ttf', 'italic' => 'Barlow-ThinItalic.ttf'],
                        '200' => ['normal' => 'Barlow-ExtraLight.ttf', 'italic' => 'Barlow-ExtraLightItalic.ttf'],
                        '300' => ['normal' => 'Barlow-Light.ttf', 'italic' => 'Barlow-LightItalic.ttf'],
                        '400' => ['normal' => 'Barlow-Regular.ttf', 'italic' => 'Barlow-Italic.ttf'],
                        '500' => ['normal' => 'Barlow-Medium.ttf', 'italic' => 'Barlow-MediumItalic.ttf'],
                        '600' => ['normal' => 'Barlow-SemiBold.ttf', 'italic' => 'Barlow-SemiBoldItalic.ttf'],
                        '700' => ['normal' => 'Barlow-Bold.ttf', 'italic' => 'Barlow-BoldItalic.ttf'],
                        '800' => ['normal' => 'Barlow-ExtraBold.ttf', 'italic' => 'Barlow-ExtraBoldItalic.ttf'],
                        '900' => ['normal' => 'Barlow-Black.ttf', 'italic' => 'Barlow-BlackItalic.ttf']
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

            .element-qrcode {
                background-color: white !important;
                border: 0.75pt solid rgba(0,0,0,0.1) !important;
                border-radius: 2pt !important;
                z-index: 100 !important;
                display: block !important;
                position: absolute !important;
                overflow: hidden !important;
                transform-origin: center !important;
                transform: translate(0, 0) !important;
                padding: 4pt !important;
            }

            .element-qrcode img {
                width: 100% !important;
                height: 100% !important;
                display: block !important;
                background: white !important;
                object-fit: contain !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                image-rendering: crisp-edges !important;
                image-rendering: pixelated !important;
            }        @media print {
            .element-qrcode {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }

        /* --- Load and embed required fonts --- */
            @php
            // Collect required fonts from elements
            $requiredFonts = [];
            if (isset($elements) && is_array($elements)) {
                foreach ($elements as $el) {
                    if (!isset($el['type']) || $el['type'] !== 'text') continue;
                    
                    // Get font configuration
                    $f = isset($el['font']) ? $el['font'] : null;
                    if (!$f) continue;

                    // Get font family and look up in mappings
                    $fontFamily = isset($f['family']) ? $f['family'] : null;
                    if (!$fontFamily || !isset($fontMappings[$fontFamily])) continue;

                    $fontInfo = $fontMappings[$fontFamily];
                    if (isset($fontInfo['type']) && $fontInfo['type'] === 'system') continue;

                    // Get font details
                    $folder = $fontInfo['folder'];
                    $weight = isset($f['weight']) ? $f['weight'] : '400';
                    $style = isset($f['style']) ? $f['style'] : 'normal';
                    
                    // Get the variant file
                    $variantFile = null;
                    if (isset($fontInfo['variants'][$weight][$style])) {
                        $variantFile = $fontInfo['variants'][$weight][$style];
                    } elseif (isset($fontInfo['variants'][$weight]['normal'])) {
                        $variantFile = $fontInfo['variants'][$weight]['normal'];
                    } elseif (isset($fontInfo['variants']['400']['normal'])) {
                        $variantFile = $fontInfo['variants']['400']['normal'];
                    }

                    if ($folder && $variantFile) {
                        $key = "{$folder}||{$variantFile}||{$weight}||{$style}";
                        if (!isset($requiredFonts[$key])) {
                            $requiredFonts[$key] = [
                                'folder' => $folder,
                                'file' => $variantFile,
                                'weight' => $weight,
                                'style' => $style
                            ];
                        }
                    }
                }
            }

            // Generate @font-face declarations for each required font
            foreach ($requiredFonts as $key => $info) {
                $fontPath = public_path('fonts/' . $info['folder'] . '/' . $info['file']);
                if (file_exists($fontPath)) {
                    $fontBase64 = base64_encode(file_get_contents($fontPath));
                    $ext = strtolower(pathinfo($info['file'], PATHINFO_EXTENSION));
                    $format = $ext === 'otf' ? 'opentype' : 
                             ($ext === 'woff2' ? 'woff2' : 
                             ($ext === 'woff' ? 'woff' : 'truetype'));
                    
                    echo "@font-face {
                        font-family: '{$info['folder']}';
                        src: url('data:font/{$format};charset=utf-8;base64,{$fontBase64}') format('{$format}');
                        font-weight: {$info['weight']};
                        font-style: {$info['style']};
                        font-display: swap;
                    }\n";
                }
            }

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
                    $fontBase64 = FontHelper::getFontBase64($fontPath);
                    $generatedFamily = FontHelper::sanitizeFontName($info['folder']) . '-' . FontHelper::sanitizeFontName(pathinfo($resolvedFile, PATHINFO_FILENAME));

                    // detect whether the resolved file appears to be an italic variant
                    $isItalicFile = (bool) preg_match('/italic|oblique|ital/i', $resolvedFile);
                } else {
                    $fontBase64 = null;
                    $generatedFamily = FontHelper::sanitizeFontName($info['folder']) . '-' . FontHelper::sanitizeFontName(pathinfo($info['file'] ?? '', PATHINFO_FILENAME));
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

        @php
            // Build a mapping from folder->generatedFamily so rendering can use the same family name
            $generatedFamilyMap = [];
            foreach ($requiredFonts as $k => $info) {
                $folder = $info['folder'] ?? null;
                $file = $info['file'] ?? null;
                if ($folder && $file) {
                    $gen = FontHelper::sanitizeFontName($folder) . '-' . FontHelper::sanitizeFontName(pathinfo($file, PATHINFO_FILENAME));
                    $generatedFamilyMap[strtolower(preg_replace('/[^a-z0-9]/', '', $folder))] = $gen;
                    // also map by sanitized family name (in case folder name differs in case or punctuation)
                    $generatedFamilyMap[strtolower(preg_replace('/[^a-z0-9]/', '', $folder)) . '_alt'] = $gen;
                }
            }
        @endphp

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
            white-space: pre-wrap;  /* Changed to pre-wrap to preserve formatting */
            line-height: 1.2;
            transform-origin: top left;
        }

        .element-text {
            display: inline-block;
            max-width: 800px;  /* Added max-width for text wrapping */
            word-wrap: break-word;  /* Enable word wrapping */
        }

        /* Font loading for custom fonts */
        @foreach($fontMappings as $fontFamily => $fontInfo)
            @if(!isset($fontInfo['type']) || $fontInfo['type'] !== 'system')
                @foreach($fontInfo['variants'] as $weight => $styles)
                    @foreach($styles as $style => $filename)
                        @php
                        $fontPath = public_path('fonts/' . $fontInfo['folder'] . '/' . $filename);
                        @endphp
                        @if(file_exists($fontPath))
                            @font-face {
                                font-family: '{{ $fontFamily }}';
                                src: url('{{ asset("fonts/" . $fontInfo["folder"] . "/" . $filename) }}') format('{{ pathinfo($filename, PATHINFO_EXTENSION) === 'otf' ? 'opentype' : 'truetype' }}');
                                font-weight: {{ $weight }};
                                font-style: {{ $style }};
                            }
                        @endif
                    @endforeach
                @endforeach
            @endif
        @endforeach

        .element-qrcode {
            display: block;
            background: white;
            padding: 4px;
            border-radius: 2px;
            position: absolute;
            box-shadow: 0 0 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .element-qrcode > div {
            position: relative;
            width: 100%;
            height: 100%;
            background-color: white;
        }

        .element-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
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
                @if($element['type'] === 'qrcode')
                    @php
                        $x = $element['x'];
                        $y = $element['y'];
                        $width = isset($element['width']) ? $element['width'] : 120;
                        $height = isset($element['height']) ? $element['height'] : 120;
                        // Use px units to match client-side preview coordinates (client uses px)
                        $qrStyle = "left: {$x}px; top: {$y}px; width: {$width}px; height: {$height}px;";

                        // Determine QR content: accept data URI, storage path, external URL, or regenerate via controller
                        $qr = isset($element['qrcode']) ? $element['qrcode'] : null;
                        $qrContent = null;
                        if ($qr) {
                            if (is_string($qr) && substr($qr, 0, 5) === 'data:') {
                                $qrContent = $qr;
                            } elseif (is_string($qr) && (substr($qr, 0, 9) === '/storage/' || strpos($qr, 'storage/') !== false)) {
                                $rel = preg_replace('#^/storage/#', '', $qr);
                                $path = storage_path('app/public/' . ltrim($rel, '/'));
                                if (file_exists($path)) {
                                    $qrContent = 'data:image/png;base64,' . base64_encode(file_get_contents($path));
                                }
                            } elseif (is_string($qr) && filter_var($qr, FILTER_VALIDATE_URL)) {
                                // best-effort fetch
                                try {
                                    $contents = @file_get_contents($qr);
                                    if ($contents !== false) {
                                        $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                        $mime = finfo_buffer($finfo, $contents);
                                        finfo_close($finfo);
                                        $qrContent = 'data:' . ($mime ?: 'image/png') . ';base64,' . base64_encode($contents);
                                    }
                                } catch (\Exception $e) {
                                    $qrContent = null;
                                }
                            }
                        }

                        if (empty($qrContent)) {
                            $certificateNumber = isset($element['content']) ? $element['content'] : null;
                            if ($certificateNumber) {
                                $qrContent = app(\App\Http\Controllers\Sertifikat\SertifikatPesertaController::class)
                                    ->getQRCodeFromCertificate($certificateNumber);
                            }
                        }
                    @endphp

                    <div class="element element-qrcode" style="{{ $qrStyle }}">
                        @if(!empty($qrContent))
                            <img src="{{ $qrContent }}" alt="QR Code" style="width: 100%; height: 100%; display: block; image-rendering: pixelated;">
                        @else
                            <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; border: 1px dashed #ddd; font-size: 10pt; color: #666;">
                                QR tidak ditemukan
                            </div>
                        @endif
                    </div>
                @elseif($element['type'] === 'text')
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
                                $key = strtolower(preg_replace('/[^a-z0-9]/', '', $fontFolder));
                                if (isset($generatedFamilyMap[$key])) {
                                    $generatedFamily = $generatedFamilyMap[$key];
                                } else {
                                    $generatedFamily = FontHelper::sanitizeFontName($fontFolder) . '-' . FontHelper::sanitizeFontName(pathinfo($fontFile, PATHINFO_FILENAME));
                                }
                            } else {
                                // If we have an embedded mapping by sanitized family name, use it
                                $san = isset($element['font']['family']) ? strtolower(preg_replace('/[^a-z0-9]/', '', $element['font']['family'])) : null;
                                if ($san && isset($generatedFamilyMap[$san])) {
                                    $generatedFamily = $generatedFamilyMap[$san];
                                } else {
                                    $generatedFamily = isset($element['font']['family']) ? $element['font']['family'] : 'Arial';
                                }
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

                                // Use px units for on-screen rendering so positions match editor
                                // Get font family from the element's configuration
                            $fontFamilyName = isset($element['font']['family']) ? $element['font']['family'] : 'Arial';
                            
                            // Check if it's a custom font from our mappings
                            if (isset($fontMappings[$fontFamilyName])) {
                                $fontInfo = $fontMappings[$fontFamilyName];
                                if (isset($fontInfo['type']) && $fontInfo['type'] === 'system') {
                                    // For system fonts, use as is
                                    $fontFamilyCSS = $fontFamilyName;
                                } else {
                                    // For custom fonts, ensure we have the font loaded
                                    $fontFolder = $fontInfo['folder'];
                                    // Add the font to required fonts if not already added
                                    $fontKey = $fontFolder . '||' . $fontFamilyName . '||' . $fontWeight . '||' . $fontStyle;
                                    if (!isset($requiredFonts[$fontKey])) {
                                        $requiredFonts[$fontKey] = [
                                            'folder' => $fontFolder,
                                            'weight' => $fontWeight,
                                            'style' => $fontStyle
                                        ];
                                    }
                                    // Use both the custom font and fallback
                                    $fontFamilyCSS = "'{$fontFamilyName}', Arial";
                                }
                            } else {
                                // Fallback to Arial if font not found in mappings
                                $fontFamilyCSS = 'Arial';
                            }

                            $style = "
                                position: absolute;
                                left: {$x}px;
                                top: {$y}px;
                                font-family: {$fontFamilyCSS};
                                font-size: {$fontSize}px;
                                font-weight: {$fontWeight};
                                font-style: {$fontStyle};
                                text-align: {$textAlign};
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
                        // Accept multiple stored keys
                        $imgCandidate = null;
                        if (!empty($element['imageUrl'])) $imgCandidate = $element['imageUrl'];
                        elseif (!empty($element['image_url'])) $imgCandidate = $element['image_url'];
                        elseif (!empty($element['image'])) $imgCandidate = $element['image'];
                        elseif (!empty($element['src'])) $imgCandidate = $element['src'];
                        elseif (!empty($element['image_path'])) $imgCandidate = '/storage/' . ltrim($element['image_path'], '/');

                        if ($imgCandidate) {
                            if (is_string($imgCandidate) && substr($imgCandidate, 0, 5) === 'data:') {
                                $imageSrc = $imgCandidate;
                            } elseif (is_string($imgCandidate) && (substr($imgCandidate, 0, 9) === '/storage/' || strpos($imgCandidate, 'storage/') !== false)) {
                                $rel = preg_replace('#^/storage/#', '', $imgCandidate);
                                $path = storage_path('app/public/' . ltrim($rel, '/'));
                                if (file_exists($path)) {
                                    $ext = pathinfo($path, PATHINFO_EXTENSION);
                                    $data = base64_encode(file_get_contents($path));
                                    $imageSrc = 'data:image/' . $ext . ';base64,' . $data;
                                }
                            } elseif (is_string($imgCandidate) && file_exists(public_path($imgCandidate))) {
                                $imageSrc = public_path($imgCandidate);
                            } elseif (!empty($element['image_path']) && file_exists(storage_path('app/public/' . $element['image_path']))) {
                                $path = storage_path('app/public/' . $element['image_path']);
                                $ext = pathinfo($path, PATHINFO_EXTENSION);
                                $data = base64_encode(file_get_contents($path));
                                $imageSrc = 'data:image/' . $ext . ';base64,' . $data;
                            }
                        }
                    @endphp
                    @if($imageSrc)
                        <div class="element" style="left: {{ $element['x'] }}px; top: {{ $element['y'] }}px;">
                            <img src="{{ $imageSrc }}" style="width: {{ $element['width'] }}px; height: {{ $element['height'] }}px;">
                        </div>
                    @endif
                @endif
            @endforeach
        @endif
    </div>
</body>
</html>
