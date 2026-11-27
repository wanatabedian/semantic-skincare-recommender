<?php


return [

    
    'weights' => [
        'concern' => 3,     
        'skin' => 2,
        'category' => 2,
        'ingredient' => 2,
        'brand' => 1,       
        'price' => 1,
    ],

    
    'plan_b_limit' => 12,

    
    'partial_limit' => 12,

    
    'max_concerns' => 2,

    'teen_lock' => [
      
        'age_group' => 'Teen',

        
        'class' => 'Retinoid',

        
        'fallback_ingredients' => [
            'Retinol',
            'RetinolIngredient',
            'RetinalIngredient',
            'RetinaldehydeIngredient',
            'RetinylPalmitateIngredient',
            'RetinylRetinoateIngredient',
            'HydroxypinacoloneRetinoateIngredient',
            'GranactiveRetinoidIngredient',
            'AdapaleneIngredient',
            'TretinoinIngredient',
        ],
    ],


    'all_skin_types' => ['AllSkinType', 'AllSkin'],

   
    'cache_ttl' => (int) env('SPARQL_CACHE_TTL', 600),
];
