<?php

return [
    // Chemins système
    'pathCacheGmap'  => '@runtime/cache-gmap',
    'pathFontsTcpdf' => '@vendor/tecnickcom/tcpdf/fonts',
    'logFile'        => '@runtime/logs/topoguide.log',

    // CDN médias TourInSoft
    'mediaCdnUrl'    => 'https://cdt64.media.tourinsoft.eu/upload',

    // Jeton de sécurité pour le déclenchement batch HTTP (exec.php)
    'execJeton'      => '',

    // Chemin vers wkhtmltopdf (génération PDF)
    'wkhtmltopdf'    => '/usr/local/bin/wkhtmltopdf',

    // Moteur PDF par défaut : 'wkhtmltopdf' | 'weasyprint' | 'prince'
    'pdfEngine'      => 'wkhtmltopdf',

    // Chemin vers weasyprint (pip install weasyprint)
    'weasyprint'     => '/home/triton/.local/bin/weasyprint',

    // Chemin vers PrinceXML (https://www.princexml.com)
    'prince'         => '/usr/bin/prince',
];
