<?php declare(strict_types=1);

namespace Privacy;

return [
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => \Laminas\I18n\Translator\Loader\Gettext::class,
                'base_dir' => __DIR__ . '/../language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\ConfigForm::class => Form\ConfigForm::class,
        ],
    ],
    'privacy' => [
        'config' => [
            // Policy applied to external Google Fonts requests:
            // - "bundled" (default): strip only the families self-hosted by
            //   this module (Lato, Source Code Pro, Open Sans) and replace them
            //   by the local versions; other Google Fonts requested by
            //   third-party themes are kept to preserve their look;
            // - "block": strip every fonts.googleapis.com / fonts.gstatic.com
            //   request; non-bundled families fall back to the CSS stack;
            // - "allow": keep all Google Fonts requests as is.
            'privacy_google_fonts' => 'bundled',
        ],
    ],
];
