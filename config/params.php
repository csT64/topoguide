<?php

return [
    // Chemins système
    'pathCacheGmap'  => '/cache/capture-gmap',
    'pathFontsTcpdf' => '@vendor/tecnickcom/tcpdf/fonts',
    'logFile'        => '@runtime/logs/topoguide.log',

    // URL interne utilisée par CutyCapt pour appeler les pages gmap/
    // En local : 'http://api.local'
    // En recette : 'https://api.adt64.fr'
    // En prod : 'https://api.tourisme64.com'
    'baseUrlGmap'    => 'http://api.local',

    // CDN médias TourInSoft
    'mediaCdnUrl'    => 'https://cdt64.media.tourinsoft.eu/upload',

    // Jeton de sécurité pour le déclenchement batch HTTP (exec.php)
    'execJeton'      => '',
];
