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
                'Alice' => ['folder' => 'Alice','variants' => ['400' => ['normal' => 'Alice-Regular.ttf']]],
                'Breathing' => ['folder' => 'breathing','variants' => ['400' => ['normal' => 'Breathing Personal Use Only.ttf']]],
                'Brighter' => ['folder' => 'brighter','variants' => ['400' => ['normal' => 'Brighter Regular.otf']]],
                'Brittany' => ['folder' => 'brittany_2','variants' => ['400' => ['normal' => 'Brittany.ttf']]],
                'Bryndan Write' => ['folder' => 'Bryndan Write','variants' => ['400' => ['normal' => 'BryndanWriteBook.ttf']]],
                'Caitlin Angelica' => ['folder' => 'caitlin_angelica','variants' => ['400' => ['normal' => 'Caitlin Angelica.ttf','italic'=>'Caitlin Angelica Italic.ttf']]],
                'Chau Philomene One' => ['folder' => 'Chau_Philomene_One','variants' => ['400' => ['normal' => 'ChauPhilomeneOne-Regular.ttf']]],
                'Chewy' => ['folder' => 'Chewy','variants' => ['400' => ['normal' => 'Chewy-Regular.ttf']]],
                'Chunkfive' => ['folder' => 'chunkfive_ex','variants' => ['400' => ['normal' => 'Chunkfive.ttf']]],
                'Cormorant Garamond' => ['folder' => 'Cormorant_Garamond','variants' => [
                    '400'=>['normal'=>'CormorantGaramond-Regular.ttf','italic'=>'CormorantGaramond-Italic.ttf'],
                    '500'=>['normal'=>'CormorantGaramond-Medium.ttf','italic'=>'CormorantGaramond-MediumItalic.ttf'],
                    '600'=>['normal'=>'CormorantGaramond-SemiBold.ttf','italic'=>'CormorantGaramond-SemiBoldItalic.ttf'],
                    '700'=>['normal'=>'CormorantGaramond-Bold.ttf','italic'=>'CormorantGaramond-BoldItalic.ttf']]],
                'DM Sans' => ['folder'=>'DM_Sans','variants'=>[
                    '400'=>['normal'=>'DMSans-Regular.ttf','italic'=>'DMSans-Italic.ttf'],
                    '500'=>['normal'=>'DMSans-Medium.ttf','italic'=>'DMSans-MediumItalic.ttf'],
                    '700'=>['normal'=>'DMSans-Bold.ttf','italic'=>'DMSans-BoldItalic.ttf']]],
                'DM Serif Display' => ['folder'=>'DM_Serif_Display','variants'=>['400'=>['normal'=>'DMSerifDisplay-Regular.ttf','italic'=>'DMSerifDisplay-Italic.ttf']]],
                'Forum' => ['folder'=>'Forum','variants'=>['400'=>['normal'=>'Forum-Regular.ttf']]],
                'Gentry Benedict' => ['folder'=>'gentry_benedict','variants'=>['400'=>['normal'=>'Gentry Benedict.otf']]],
                'Hammersmith One' => ['folder'=>'Hammersmith_One','variants'=>['400'=>['normal'=>'HammersmithOne-Regular.ttf']]],
                'Inria Serif' => ['folder'=>'Inria_Serif','variants'=>[
                    '400'=>['normal'=>'InriaSerif-Regular.ttf','italic'=>'InriaSerif-Italic.ttf'],
                    '700'=>['normal'=>'InriaSerif-Bold.ttf','italic'=>'InriaSerif-BoldItalic.ttf']]],
                'Inter' => ['folder'=>'Inter','variants'=>[
                    '400'=>['normal'=>'Inter-Regular.ttf'],
                    '500'=>['normal'=>'Inter-Medium.ttf'],
                    '600'=>['normal'=>'Inter-SemiBold.ttf'],
                    '700'=>['normal'=>'Inter-Bold.ttf']]],
                'League Gothic' => ['folder'=>'League_Gothic','variants'=>['400'=>['normal'=>'LeagueGothic-Regular.ttf']]],
                'Libre Baskerville' => ['folder'=>'Libre_Baskerville','variants'=>[
                    '400'=>['normal'=>'LibreBaskerville-Regular.ttf','italic'=>'LibreBaskerville-Italic.ttf'],
                    '700'=>['normal'=>'LibreBaskerville-Bold.ttf']]],
                'Lora' => ['folder'=>'Lora','variants'=>[
                    '400'=>['normal'=>'Lora-Regular.ttf','italic'=>'Lora-Italic.ttf'],
                    '500'=>['normal'=>'Lora-Medium.ttf','italic'=>'Lora-MediumItalic.ttf'],
                    '600'=>['normal'=>'Lora-SemiBold.ttf','italic'=>'Lora-SemiBoldItalic.ttf'],
                    '700'=>['normal'=>'Lora-Bold.ttf','italic'=>'Lora-BoldItalic.ttf']]],
                'Merriweather'=>['folder'=>'Merriweather','variants'=>[
                    '300'=>['normal'=>'Merriweather-Light.ttf','italic'=>'Merriweather-LightItalic.ttf'],
                    '400'=>['normal'=>'Merriweather-Regular.ttf','italic'=>'Merriweather-Italic.ttf'],
                    '700'=>['normal'=>'Merriweather-Bold.ttf','italic'=>'Merriweather-BoldItalic.ttf'],
                    '900'=>['normal'=>'Merriweather-Black.ttf','italic'=>'Merriweather-BlackItalic.ttf']]],
                'More Sugar'=>['folder'=>'more_sugar','variants'=>['400'=>['normal'=>'More Sugar.ttf']]],
                'Nunito'=>['folder'=>'Nunito','variants'=>[
                    '200'=>['normal'=>'Nunito-ExtraLight.ttf','italic'=>'Nunito-ExtraLightItalic.ttf'],
                    '300'=>['normal'=>'Nunito-Light.ttf','italic'=>'Nunito-LightItalic.ttf'],
                    '400'=>['normal'=>'Nunito-Regular.ttf','italic'=>'Nunito-Italic.ttf'],
                    '500'=>['normal'=>'Nunito-Medium.ttf','italic'=>'Nunito-MediumItalic.ttf'],
                    '600'=>['normal'=>'Nunito-SemiBold.ttf','italic'=>'Nunito-SemiBoldItalic.ttf'],
                    '700'=>['normal'=>'Nunito-Bold.ttf','italic'=>'Nunito-BoldItalic.ttf'],
                    '800'=>['normal'=>'Nunito-ExtraBold.ttf','italic'=>'Nunito-ExtraBoldItalic.ttf'],
                    '900'=>['normal'=>'Nunito-Black.ttf','italic'=>'Nunito-BlackItalic.ttf']]],
                'Open Sans'=>['folder'=>'Open_Sans','variants'=>[
                    '300'=>['normal'=>'OpenSans-Light.ttf','italic'=>'OpenSans-LightItalic.ttf'],
                    '400'=>['normal'=>'OpenSans-Regular.ttf','italic'=>'OpenSans-Italic.ttf'],
                    '500'=>['normal'=>'OpenSans-Medium.ttf','italic'=>'OpenSans-MediumItalic.ttf'],
                    '600'=>['normal'=>'OpenSans-SemiBold.ttf','italic'=>'OpenSans-SemiBoldItalic.ttf'],
                    '700'=>['normal'=>'OpenSans-Bold.ttf','italic'=>'OpenSans-BoldItalic.ttf'],
                    '800'=>['normal'=>'OpenSans-ExtraBold.ttf','italic'=>'OpenSans-ExtraBoldItalic.ttf']]],
                'Oswald'=>['folder'=>'Oswald','variants'=>[
                    '200'=>['normal'=>'Oswald-ExtraLight.ttf'],
                    '300'=>['normal'=>'Oswald-Light.ttf'],
                    '400'=>['normal'=>'Oswald-Regular.ttf'],
                    '500'=>['normal'=>'Oswald-Medium.ttf'],
                    '600'=>['normal'=>'Oswald-SemiBold.ttf'],
                    '700'=>['normal'=>'Oswald-Bold.ttf']]],
                'Questrial'=>['folder'=>'Questrial','variants'=>['400'=>['normal'=>'Questrial-Regular.ttf']]],
                'Quicksand'=>['folder'=>'Quicksand','variants'=>[
                    '300'=>['normal'=>'Quicksand-Light.ttf'],
                    '400'=>['normal'=>'Quicksand-Regular.ttf'],
                    '500'=>['normal'=>'Quicksand-Medium.ttf'],
                    '600'=>['normal'=>'Quicksand-SemiBold.ttf'],
                    '700'=>['normal'=>'Quicksand-Bold.ttf']]],
                'Railey'=>['folder'=>'railey','variants'=>['400'=>['normal'=>'Railey.ttf']]],
                'Raleway'=>['folder'=>'Raleway','variants'=>[
                    '100'=>['normal'=>'Raleway-Thin.ttf','italic'=>'Raleway-ThinItalic.ttf'],
                    '200'=>['normal'=>'Raleway-ExtraLight.ttf','italic'=>'Raleway-ExtraLightItalic.ttf'],
                    '300'=>['normal'=>'Raleway-Light.ttf','italic'=>'Raleway-LightItalic.ttf'],
                    '400'=>['normal'=>'Raleway-Regular.ttf','italic'=>'Raleway-Italic.ttf'],
                    '500'=>['normal'=>'Raleway-Medium.ttf','italic'=>'Raleway-MediumItalic.ttf'],
                    '600'=>['normal'=>'Raleway-SemiBold.ttf','italic'=>'Raleway-SemiBoldItalic.ttf'],
                    '700'=>['normal'=>'Raleway-Bold.ttf','italic'=>'Raleway-BoldItalic.ttf'],
                    '800'=>['normal'=>'Raleway-ExtraBold.ttf','italic'=>'Raleway-ExtraBoldItalic.ttf'],
                    '900'=>['normal'=>'Raleway-Black.ttf','italic'=>'Raleway-BlackItalic.ttf']]],
                'Roboto'=>['folder'=>'Roboto','variants'=>[
                    '100'=>['normal'=>'Roboto-Thin.ttf','italic'=>'Roboto-ThinItalic.ttf'],
                    '300'=>['normal'=>'Roboto-Light.ttf','italic'=>'Roboto-LightItalic.ttf'],
                    '400'=>['normal'=>'Roboto-Regular.ttf','italic'=>'Roboto-Italic.ttf'],
                    '500'=>['normal'=>'Roboto-Medium.ttf','italic'=>'Roboto-MediumItalic.ttf'],
                    '700'=>['normal'=>'Roboto-Bold.ttf','italic'=>'Roboto-BoldItalic.ttf'],
                    '900'=>['normal'=>'Roboto-Black.ttf','italic'=>'Roboto-BlackItalic.ttf']]],
                'Shrikhand'=>['folder'=>'Shrikhand','variants'=>['400'=>['normal'=>'Shrikhand-Regular.ttf']]],
                'Tenor Sans'=>['folder'=>'Tenor_Sans','variants'=>['400'=>['normal'=>'TenorSans-Regular.ttf']]],
                'Yeseva One'=>['folder'=>'Yeseva_One','variants'=>['400'=>['normal'=>'YesevaOne-Regular.ttf']]],
                'Allura'=>['folder'=>'Allura','variants'=>['400'=>['normal'=>'Allura-Regular.ttf']]],
                'Anonymous Pro'=>['folder'=>'Anonymous_Pro','variants'=>[
                    '400'=>['normal'=>'AnonymousPro-Regular.ttf','italic'=>'AnonymousPro-Italic.ttf'],
                    '700'=>['normal'=>'AnonymousPro-Bold.ttf','italic'=>'AnonymousPro-BoldItalic.ttf']]],
                'Anton'=>['folder'=>'Anton','variants'=>['400'=>['normal'=>'Anton-Regular.ttf']]],
                'Arapey'=>['folder'=>'Arapey','variants'=>['400'=>['normal'=>'Arapey-Regular.ttf','italic'=>'Arapey-Italic.ttf']]],
                'Archivo Black'=>['folder'=>'Archivo_Black','variants'=>['400'=>['normal'=>'ArchivoBlack-Regular.ttf']]],
                'Arimo'=>['folder'=>'Arimo','variants'=>[
                    '400'=>['normal'=>'Arimo-Regular.ttf','italic'=>'Arimo-Italic.ttf'],
                    '500'=>['normal'=>'Arimo-Medium.ttf','italic'=>'Arimo-MediumItalic.ttf'],
                    '600'=>['normal'=>'Arimo-SemiBold.ttf','italic'=>'Arimo-SemiBoldItalic.ttf'],
                    '700'=>['normal'=>'Arimo-Bold.ttf','italic'=>'Arimo-BoldItalic.ttf']]],
                'Barlow'=>['folder'=>'Barlow','variants'=>[
                    '400'=>['normal'=>'Barlow-Regular.ttf','italic'=>'Barlow-Italic.ttf'],
                    '500'=>['normal'=>'Barlow-Medium.ttf','italic'=>'Barlow-MediumItalic.ttf'],
                    '600'=>['normal'=>'Barlow-SemiBold.ttf','italic'=>'Barlow-SemiBoldItalic.ttf'],
                    '700'=>['normal'=>'Barlow-Bold.ttf','italic'=>'Barlow-BoldItalic.ttf']]],
                'Bebas Neue'=>['folder'=>'Bebas_Neue','variants'=>['400'=>['normal'=>'BebasNeue-Regular.ttf']]],
                'Belleza'=>['folder'=>'Belleza','variants'=>['400'=>['normal'=>'Belleza-Regular.ttf']]],
                'Bree Serif'=>['folder'=>'Bree_Serif','variants'=>['400'=>['normal'=>'BreeSerif-Regular.ttf']]],
                'Great Vibes'=>['folder'=>'Great_Vibes','variants'=>['400'=>['normal'=>'GreatVibes-Regular.ttf']]],
                'League Spartan'=>['folder'=>'League_Spartan','variants'=>['400'=>['normal'=>'LeagueSpartan-Regular.ttf'],'700'=>['normal'=>'LeagueSpartan-Bold.ttf']]],
                'Montserrat'=>['folder'=>'Montserrat','variants'=>[
                    '400'=>['normal'=>'Montserrat-Regular.ttf','italic'=>'Montserrat-Italic.ttf'],
                    '500'=>['normal'=>'Montserrat-Medium.ttf','italic'=>'Montserrat-MediumItalic.ttf'],
                    '600'=>['normal'=>'Montserrat-SemiBold.ttf','italic'=>'Montserrat-SemiBoldItalic.ttf'],
                    '700'=>['normal'=>'Montserrat-Bold.ttf','italic'=>'Montserrat-BoldItalic.ttf']]],
                'Playfair Display'=>['folder'=>'Playfair_Display','variants'=>[
                    '400'=>['normal'=>'PlayfairDisplay-Regular.ttf','italic'=>'PlayfairDisplay-Italic.ttf'],
                    '700'=>['normal'=>'PlayfairDisplay-Bold.ttf','italic'=>'PlayfairDisplay-BoldItalic.ttf']]],
                'Poppins'=>['folder'=>'Poppins','variants'=>[
                    '100'=>['normal'=>'Poppins-Thin.ttf','italic'=>'Poppins-ThinItalic.ttf'],
                    '200'=>['normal'=>'Poppins-ExtraLight.ttf','italic'=>'Poppins-ExtraLightItalic.ttf'],
                    '300'=>['normal'=>'Poppins-Light.ttf','italic'=>'Poppins-LightItalic.ttf'],
                    '400'=>['normal'=>'Poppins-Regular.ttf','italic'=>'Poppins-Italic.ttf'],
                    '500'=>['normal'=>'Poppins-Medium.ttf','italic'=>'Poppins-MediumItalic.ttf'],
                    '600'=>['normal'=>'Poppins-SemiBold.ttf','italic'=>'Poppins-SemiBoldItalic.ttf'],
                    '700'=>['normal'=>'Poppins-Bold.ttf','italic'=>'Poppins-BoldItalic.ttf'],
                    '800'=>['normal'=>'Poppins-ExtraBold.ttf','italic'=>'Poppins-ExtraBoldItalic.ttf'],
                    '900'=>['normal'=>'Poppins-Black.ttf','italic'=>'Poppins-BlackItalic.ttf']]],
                'Arial'=>['type'=>'system'],
                'Times New Roman'=>['type'=>'system'],
                'Helvetica'=>['type'=>'system'],
            ];

            $pageWidth = 842; $pageHeight = 595;

            // --- FONT LOADING LOGIC ---
            $requiredFonts = [];
            if (isset($elements) && is_array($elements)) {
                foreach ($elements as $el) {
                    if (!isset($el['type']) || $el['type'] !== 'text') continue;
                    $f = isset($el['font']) ? $el['font'] : (isset($el['fontFamily']) ? ['family' => $el['fontFamily']] : null);
                    if (!$f) continue;
                    $fontFamily = $f['family'] ?? null;
                    if (!$fontFamily || !isset($fontMappings[$fontFamily])) continue;
                    $fontInfo = $fontMappings[$fontFamily];
                    if (isset($fontInfo['type']) && $fontInfo['type'] === 'system') continue;
                    $folder = $fontInfo['folder'];
                    $weight = $el['fontWeight'] ?? ($f['weight'] ?? '400');
                    $style = $el['fontStyle'] ?? ($f['style'] ?? 'normal');
                    $variantFile = $fontInfo['variants'][$weight][$style] ?? ($fontInfo['variants'][$weight]['normal'] ?? ($fontInfo['variants']['400']['normal'] ?? null));
                    if ($folder && $variantFile) {
                        $key = "{$fontFamily}||{$weight}||{$style}";
                        if (!isset($requiredFonts[$key])) {
                            $requiredFonts[$key] = ['family'=>$fontFamily,'folder'=>$folder,'file'=>$variantFile,'weight'=>$weight,'style'=>$style];
                        }
                    }
                }
            }
            foreach ($requiredFonts as $info) {
                $fontPath = public_path('fonts/' . $info['folder'] . '/' . $info['file']);
                if (file_exists($fontPath)) {
                    $fontBase64 = base64_encode(file_get_contents($fontPath));
                    $ext = strtolower(pathinfo($info['file'], PATHINFO_EXTENSION));
                    $format = $ext === 'otf' ? 'opentype' : ($ext === 'woff2' ? 'woff2' : ($ext === 'woff' ? 'woff' : 'truetype'));
                    echo "@font-face { font-family:'{$info['family']}'; src:url('data:font/{$format};charset=utf-8;base64,{$fontBase64}') format('{$format}'); font-weight:{$info['weight']}; font-style:{$info['style']}; }\n";
                }
            }
        @endphp

        @page { margin:0; padding:0; size:{{ $pageWidth }}pt {{ $pageHeight }}pt; }
        body { margin:0; padding:0; width:{{ $pageWidth }}pt; height:{{ $pageHeight }}pt; position:relative; font-family:Arial, sans-serif; }
        .certificate-container { position:relative; width:100%; height:100%; overflow:hidden; }
        .background { position:absolute; top:0; left:0; width:100%; height:100%; z-index:1; }
        .element { position:absolute; z-index:2; white-space:pre-wrap; line-height:1.2; }
        .element-image img { width:100%; height:100%; object-fit:contain; }
        .element-qrcode img { width:100%; height:100%; display:block; }
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
                @php
                    $type = $element['type'];
                    $x = $element['x'] ?? 0;
                    $y = $element['y'] ?? 0;
                    $w = $element['width'] ?? null;
                    $h = $element['height'] ?? null;

                    $rotate = isset($element['rotate']) ? (float) $element['rotate'] : 0;
                    $scaleX = isset($element['scaleX']) ? (float) $element['scaleX'] : 1;
                    $scaleY = isset($element['scaleY']) ? (float) $element['scaleY'] : 1;

                    $transformParts = [];
                    if ($rotate !== 0) $transformParts[] = "rotate({$rotate}deg)";
                    if ($scaleX !== 1 || $scaleY !== 1) $transformParts[] = "scale({$scaleX}, {$scaleY})";
                    $transformCss = count($transformParts) > 0 ? 'transform:' . implode(' ', $transformParts) . ';' : '';
                @endphp

@if($type === 'text')
    @php
        // PERBAIKAN: Safely access font properties dengan fallback
        $font = $element['font'] ?? [];
        
        // Handle berbagai format font family
        $fontFamily = null;
        if (isset($element['fontFamily'])) {
            $fontFamily = $element['fontFamily'];
        } elseif (isset($font['family'])) {
            $fontFamily = $font['family'];
        } elseif (isset($element['font']['fontFamily'])) {
            $fontFamily = $element['font']['fontFamily'];
        }
        
        // Fallback ke Arial jika tidak ada font family
        $fontFamilyCSS = $fontFamily ? "'{$fontFamily}', Arial, sans-serif" : 'Arial, sans-serif';
        
        // Safe access untuk font properties lainnya
        $fontWeight = $element['fontWeight'] ?? ($font['weight'] ?? '400');
        $fontStyle  = $element['fontStyle'] ?? ($font['style'] ?? 'normal');
        $fontSize   = $element['fontSize'] ?? 16;
        $color      = $element['color'] ?? '#000000';
        $text       = $element['text'] ?? '';
        $textAlign  = $element['textAlign'] ?? 'left';
        $width = $w ? "{$w}pt" : "auto";

        $style = "position:absolute; "
               . "left:{$x}pt; top:{$y}pt; "
               . "width:{$width}; "
               . "font-family:{$fontFamilyCSS}; "
               . "font-size:{$fontSize}pt; "
               . "font-weight:{$fontWeight}; "
               . "font-style:{$fontStyle}; "
               . "color:{$color}; "
               . "text-align:{$textAlign}; "
               . "{$transformCss}";
               
        // Debug log untuk text element
        Log::info('Rendering text element', [
            'text' => substr($text, 0, 50),
            'fontFamily' => $fontFamily,
            'fontSize' => $fontSize,
            'position' => "x:{$x}, y:{$y}"
        ]);
    @endphp
    <div class="element" style="{!! $style !!}">{!! nl2br(e($text)) !!}</div>

                @elseif($type === 'image' || $type === 'qrcode')
                    @php
                        $src = null;
                        
                        $resolveImageFileToDataUri = function ($path) {
                            if (!$path) return null;
                            if (strpos($path, 'data:image') === 0) return $path;

                            if (strpos($path, '/storage/') === 0) {
                                $path = substr($path, strlen('/storage/'));
                            }
                            
                            $fullPath = storage_path('app/public/' . ltrim($path, '/'));

                            if (file_exists($fullPath)) {
                                $data = base64_encode(file_get_contents($fullPath));
                                $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
                                return 'data:image/' . $ext . ';base64,' . $data;
                            }
                            return null;
                        };

                        if ($type === 'image') {
                            $imagePath = $element['image_path'] ?? $element['imageUrl'] ?? $element['src'] ?? null;
                            $src = $resolveImageFileToDataUri($imagePath);
                        } 
                        elseif ($type === 'qrcode') {
                            $src = $element['qrcode'] ?? null;
                        }

                        $style = "left:{$x}pt; top:{$y}pt;
                                  width:{$w}pt; height:{$h}pt;
                                  {$transformCss}";
                    @endphp

                    @if($src)
                        <div class="element element-{{$type}}" style="position:absolute; {!! $style !!}">
                            <img src="{{ $src }}" alt="{{$type}}">
                        </div>
                    @endif
@elseif($type === 'shape')
    @php
        $shapeType = $element['shapeType'] ?? 'rectangle';
        $style = $element['style'] ?? [];
        $fillColor = $style['fillColor'] ?? 'transparent';
        $strokeColor = $style['color'] ?? $style['strokeColor'] ?? '#000000';
        $strokeWidth = isset($style['strokeWidth']) ? floatval($style['strokeWidth']) : 1;
        $opacity = $style['opacity'] ?? 1;
        $borderRadius = $style['borderRadius'] ?? 0;
        $svgW = $w ?? 100;
        $svgH = $h ?? 100;
        
        // Check visibility
        $isVisible = ($element['isVisible'] ?? true) && ($opacity > 0);
        
        Log::info('Rendering shape in blade', [
            'shapeType' => $shapeType,
            'isVisible' => $isVisible,
            'position' => "x:{$x}, y:{$y}",
            'size' => "w:{$svgW}, h:{$svgH}",
            'fillColor' => $fillColor,
            'strokeColor' => $strokeColor
        ]);
    @endphp
    
    @if($isVisible)
        {{-- Use px units for better DomPDF compatibility --}}
        @if($shapeType === 'rectangle')
            <div style="position:absolute; left:{{$x}}px; top:{{$y}}px; width:{{$svgW}}px; height:{{$svgH}}px; background-color:{{$fillColor}}; border:{{$strokeWidth}}px solid {{$strokeColor}}; border-radius:{{$borderRadius}}px; opacity:{{$opacity}}; {{$transformCss}}"></div>
        @elseif($shapeType === 'circle')
            <div style="position:absolute; left:{{$x}}px; top:{{$y}}px; {{$transformCss}}">
                <svg width="{{$svgW}}px" height="{{$svgH}}px" viewBox="0 0 {{$svgW}} {{$svgH}}" xmlns="http://www.w3.org/2000/svg">
                    <ellipse cx="{{$svgW/2}}" cy="{{$svgH/2}}" rx="{{$svgW/2}}" ry="{{$svgH/2}}" fill="{{$fillColor}}" stroke="{{$strokeColor}}" stroke-width="{{$strokeWidth}}" opacity="{{$opacity}}" />
                </svg>
            </div>
        @elseif($shapeType === 'line')
            <div style="position:absolute; left:{{$x}}px; top:{{ $y + ($svgH/2) }}px; width:{{$svgW}}px; height:{{$strokeWidth}}px; background-color:{{$strokeColor}}; opacity:{{$opacity}}; {{$transformCss}}"></div>
        @else
            {{-- For complex shapes, use simplified SVG with explicit namespace --}}
            @php
                // Generate simple SVG paths for DomPDF
                $shapePath = '';
                switch($shapeType) {
                    case 'triangle':
                        $shapePath = "M" . ($svgW / 2) . ",0 L{$svgW},{$svgH} L0,{$svgH} Z";
                        break;
                    case 'diamond':
                        $shapePath = "M" . ($svgW / 2) . ",0 L{$svgW}," . ($svgH / 2) . " L" . ($svgW / 2) . ",{$svgH} L0," . ($svgH / 2) . " Z";
                        break;
                    case 'star':
                        // Simplified 5-point star
                        $cx = $svgW / 2; $cy = $svgH / 2; $r = min($svgW, $svgH) / 3;
                        $shapePath = "M{$cx}," . ($cy - $r) . " L" . ($cx + $r*0.3) . "," . ($cy - $r*0.3) . " L" . ($cx + $r) . "," . ($cy - $r*0.3) . " L" . ($cx + $r*0.5) . "," . ($cy + $r*0.2) . " L" . ($cx + $r*0.8) . "," . ($cy + $r) . " L{$cx}," . ($cy + $r*0.5) . " L" . ($cx - $r*0.8) . "," . ($cy + $r) . " L" . ($cx - $r*0.5) . "," . ($cy + $r*0.2) . " L" . ($cx - $r) . "," . ($cy - $r*0.3) . " L" . ($cx - $r*0.3) . "," . ($cy - $r*0.3) . " Z";
                        break;
                    default:
                        $shapePath = "M0,0 L{$svgW},0 L{$svgW},{$svgH} L0,{$svgH} Z";
                }
            @endphp
            <div style="position:absolute; left:{{$x}}px; top:{{$y}}px; {{$transformCss}}">
                <svg width="{{$svgW}}px" height="{{$svgH}}px" viewBox="0 0 {{$svgW}} {{$svgH}}" xmlns="http://www.w3.org/2000/svg" version="1.1">
                    <path d="{{ $shapePath }}" 
                          fill="{{$fillColor === 'transparent' ? 'none' : $fillColor}}" 
                          stroke="{{$strokeColor}}" 
                          stroke-width="{{$strokeWidth}}" 
                          opacity="{{$opacity}}" />
                </svg>
            </div>
        @endif
    @endif
@endif
            @endforeach
        @endif
    </div>
</body>
</html>
