<?php declare(strict_types=1);

namespace Privacy\Form;

use Laminas\Form\Element;
use Laminas\Form\Form;

class ConfigForm extends Form
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'privacy_google_fonts',
                'type' => Element\Radio::class,
                'options' => [
                    'label' => 'Google Fonts policy', // @translate
                    'label_attributes' => [
                        'style' => 'display: block;',
                    ],
                    'info' => 'Choose how to handle external Google Fonts requests (fonts.googleapis.com / fonts.gstatic.com).', // @translate
                    'value_options' => [
                        'bundled' => 'Replace only the fonts bundled with this module (Lato, Source Code Pro, Open Sans): identical look, third-party fonts kept', // @translate
                        'block' => 'Block every Google Fonts request: non-bundled families fall back to the browser CSS', // @translate
                        'allow' => 'Keep all Google Fonts requests (no privacy protection)', // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'privacy_google_fonts',
                    'value' => 'bundled',
                ],
            ]);
    }
}
