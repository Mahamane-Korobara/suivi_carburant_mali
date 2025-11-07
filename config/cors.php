<?php

return [
    'paths' => ['api/*'], // ❌ Retirer 'sanctum/csrf-cookie' car on ne l'utilise plus
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => ['http://localhost:3000', 'http://127.0.0.1:3000'],
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => ['*'],
    
    'exposed_headers' => [],
    
    'max_age' => 0,
    
    'supports_credentials' => false, // ✅ false car on n'utilise plus les cookies, seulement les tokens Bearer
];