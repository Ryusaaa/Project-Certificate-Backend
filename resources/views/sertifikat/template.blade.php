<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sertifikat</title>
    <style>
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
            margin: 0;
            padding: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
            line-height: 1.2;
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
                                if (isset($element['placeholderType']) && $element['placeholderType'] !== 'custom') {
                                    $y += $fontSize / 4; // offset yang lebih kecil untuk placeholder
                                } else {
                                    $y += $fontSize / 3; // offset yang lebih besar untuk teks kustom
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
                                'fontSize' => $fontSize,
                                'page_size' => [
                                    'width' => $pageWidth,
                                    'height' => $pageHeight
                                ]
                            ]);
                        @endphp
                        <div class="element" style="
                            left: {{ $x }}pt; 
                            top: {{ $y }}pt;
                            @if(isset($element['rotate'])) transform: rotate({{ $element['rotate'] }}deg); @endif
                        ">
                            @if($element['type'] === 'text')
                                <p class="text" style="
                                    font-size: {{ $fontSize }}pt;
                                    font-family: {{ $element['fontFamily'] ?? 'Arial' }}, sans-serif;
                                    text-align: {{ $element['textAlign'] ?? 'left' }};
                                    color: {{ $element['color'] ?? '#000000' }};
                                    @if($elementWidth) width: {{ $elementWidth }}pt; @endif
                                    margin: 0;
                                    padding: 0;
                                    line-height: 1.2;
                                    white-space: nowrap;
                                    position: absolute;
                                    transform-origin: left top;
                                    {{ \Log::info('Text element style:', [
                                        'fontSize' => $fontSize,
                                        'position' => ['x' => $x, 'y' => $y],
                                        'text' => $element['text'] ?? '',
                                        'align' => $element['textAlign'] ?? 'left'
                                    ]) ? '' : '' }}
                                    @if($element['textAlign'] === 'center')
                                        transform: translateX(-50%);
                                    @elseif($element['textAlign'] === 'right')
                                        transform: translateX(-100%);
                                    @endif
                                ">
                                    {!! $element['text'] ?? '' !!}
                                </p>
                            @elseif($element['type'] === 'image')
                                @php
                                    \Log::info('Processing image element:', $element);
                                    $imageUrl = $element['image_url'] ?? $element['imageUrl'] ?? $element['url'] ?? $element['image'] ?? null;
                                    \Log::info('Image URL resolved to:', ['url' => $imageUrl]);
                                    
                                    // Convert image URL to base64 like we do for background
                                    $imageSrc = $imageUrl;
                                    if ($imageUrl && str_starts_with($imageUrl, '/storage/')) {
                                        $imagePath = storage_path('app/public/' . substr($imageUrl, 8));
                                        \Log::info('Resolving image path:', ['path' => $imagePath]);
                                        
                                        if (file_exists($imagePath)) {
                                            $imageData = base64_encode(file_get_contents($imagePath));
                                            $imageSrc = 'data:image/' . pathinfo($imagePath, PATHINFO_EXTENSION) . ';base64,' . $imageData;
                                            \Log::info('Image successfully encoded');
                                        } else {
                                            \Log::error('Image file not found:', ['path' => $imagePath]);
                                        }
                                    }
                                @endphp
                                @if($imageSrc)
                                    <img src="{{ $imageSrc }}" 
                                        style="
                                            @if(isset($element['width'])) width: {{ $element['width'] }}pt; @endif
                                            @if(isset($element['height'])) height: {{ $element['height'] }}pt; @endif
                                            object-fit: contain;
                                            margin: 0;
                                            padding: 0;
                                            display: block;
                                        ">
                                @else
                                    <!-- Log warning if no image URL found -->
                                    @php \Log::warning('No image URL found in element:', $element); @endphp
                                @endif
                            @endif
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
</body>
</html>
