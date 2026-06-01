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
            // Allow external Google Fonts (admin: Lato, Source Code Pro;
            // bundled default theme: Open Sans). Disabled by default: the
            // module self-hosts the same fonts so no request is sent to
            // fonts.googleapis.com or fonts.gstatic.com.
            'privacy_google_fonts' => false,
        ],
    ],
];
