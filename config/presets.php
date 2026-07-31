<?php

return [
    [
        'key' => '4-4-4',
        'collection' => 'core',
        'name' => 'Common Time',
        'numerator' => 4,
        'denominator' => 4,
        'grouping' => [4],

        'pattern' => [
            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],
        ],
    ],

    [
        'key' => '3-4-3',
        'collection' => 'core',
        'name' => 'Triple Time',
        'numerator' => 3,
        'denominator' => 4,
        'grouping' => [3],

        'pattern' => [
            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],
        ],
    ],

    [
        'key' => '5-4-5',
        'collection' => 'core',
        'name' => 'Quintuple Time',
        'numerator' => 5,
        'denominator' => 4,
        'grouping' => [5],

        'pattern' => [
            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],
        ],
    ],

    [
        'key' => '5-8-2-3',
        'collection' => 'core',
        'name' => 'Five Eight',
        'numerator' => 5,
        'denominator' => 8,
        'grouping' => [2, 3],

        'pattern' => [
            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],
        ],
    ],

    [
        'key' => '7-8-2-3-2',
        'collection' => 'core',
        'name' => 'Seven Eight',
        'numerator' => 7,
        'denominator' => 8,
        'grouping' => [2, 3, 2],

        'pattern' => [
            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
        ],
    ],

    [
        'key' => '9-8-3-3-3',
        'collection' => 'core',
        'name' => 'Compound Triple',
        'numerator' => 9,
        'denominator' => 8,
        'grouping' => [3, 3, 3],

        'pattern' => [
            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],
        ],
    ],

    [
        'key' => '11-8-3-3-3-2',
        'collection' => 'core',
        'name' => 'Eleven Eight',
        'numerator' => 11,
        'denominator' => 8,
        'grouping' => [3, 3, 3, 2],

        'pattern' => [
            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
        ],
    ],

    // BALKAN PRESETS
    [
        'key' => 'balkan-paidushko',
        'collection' => 'balkan',
        'name' => 'Paidushko',
        'numerator' => 5,
        'denominator' => 8,
        'grouping' => [2, 3],

        'pattern' => [
            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],
        ],
    ],

    [
        'key' => 'balkan-rachenitza',
        'collection' => 'balkan',
        'name' => 'Rachenitza',
        'numerator' => 7,
        'denominator' => 8,
        'grouping' => [2, 2, 3],

        'pattern' => [
            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],
        ],
    ],

    [
        'key' => 'balkan-kalamatianos',
        'collection' => 'balkan',
        'name' => 'Kalamatianos / Sitna Lisa / Cetvorno',
        'numerator' => 7,
        'denominator' => 8,
        'grouping' => [3, 2, 2],

        'pattern' => [
            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
        ],
    ],

    [
        'key' => 'balkan-dajcovo-karsilama',
        'collection' => 'balkan',
        'name' => 'Dajcovo / Karsilama',
        'numerator' => 9,
        'denominator' => 8,
        'grouping' => [2, 2, 2, 3],

        'pattern' => [
            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],
        ],
    ],

    [
        'key' => 'balkan-turkish-jurjuna',
        'collection' => 'balkan',
        'name' => 'Turkish Jurjuna',
        'numerator' => 10,
        'denominator' => 8,
        'grouping' => [3, 2, 2, 3],

        'pattern' => [
            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],
        ],
    ],

    [
        'key' => 'balkan-kopanica',
        'collection' => 'balkan',
        'name' => 'Kopanica',
        'numerator' => 11,
        'denominator' => 8,
        'grouping' => [2, 2, 3, 2, 2],

        'pattern' => [
            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
        ],
    ],

    [
        'key' => 'balkan-postupano-krivo-horo',
        'collection' => 'balkan',
        'name' => 'Postupano / Krivo Horo',
        'numerator' => 13,
        'denominator' => 8,
        'grouping' => [2, 2, 2, 3, 2, 2],

        'pattern' => [
            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
        ],
    ],

    [
        'key' => 'balkan-bucimis',
        'collection' => 'balkan',
        'name' => 'Bucimis',
        'numerator' => 15,
        'denominator' => 8,
        'grouping' => [2, 2, 2, 2, 3, 2, 2],

        'pattern' => [
            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],

            ['sound' => 'accent', 'groupStart' => true],
            ['sound' => 'click', 'groupStart' => false],
        ],
    ],
];