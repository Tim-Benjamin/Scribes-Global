<?php
function renderBadgeSVG($color, $size = 24) {
    $colors = [
        'gold' => ['outer' => '#FFD700', 'inner' => '#FFA500'],
        'red' => ['outer' => '#FF6B6B', 'inner' => '#C92A2A'],
        'purple' => ['outer' => '#9B7EDE', 'inner' => '#6B46C1'],
        'pink' => ['outer' => '#FF69B4', 'inner' => '#FF1493'],
        'green' => ['outer' => '#51CF66', 'inner' => '#2F9E44'],
        'silver' => ['outer' => '#C0C0C0', 'inner' => '#808080'],
        'blue' => ['outer' => '#4A9EFF', 'inner' => '#1971C2'],
        'teal' => ['outer' => '#56CCF2', 'inner' => '#2D9CDB'],
        'white' => ['outer' => '#FFFFFF', 'inner' => '#E8E8E8'],
        'black' => ['outer' => '#2C2C2C', 'inner' => '#1A1A1A'],
        'orange' => ['outer' => '#FF922B', 'inner' => '#F76707'],
    ];
    
    $colorScheme = $colors[$color] ?? $colors['silver'];
    
    return <<<SVG
<svg width="{$size}" height="{$size}" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
    <g>
        <!-- Outer star shape -->
        <path d="M256 0L289.2 83.8L378.1 51.9L371.3 144.7L470.1 141.9L427.4 225.7L512 256L427.4 286.3L470.1 370.1L371.3 367.3L378.1 460.1L289.2 428.2L256 512L222.8 428.2L133.9 460.1L140.7 367.3L41.9 370.1L84.6 286.3L0 256L84.6 225.7L41.9 141.9L140.7 144.7L133.9 51.9L222.8 83.8L256 0Z" fill="{$colorScheme['outer']}"/>
        
        <!-- Inner circle -->
        <circle cx="256" cy="256" r="160" fill="{$colorScheme['inner']}"/>
        
        <!-- Checkmark -->
        <path d="M369 190L233 326L143 236" stroke="white" stroke-width="40" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
    </g>
</svg>
SVG;
}
?>