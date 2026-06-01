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
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Allow external Google Fonts', // @translate
                    'info' => 'When unchecked (default), the module self-hosts the fonts used by the admin (Lato, Source Code Pro) and the bundled default theme (Open Sans), so no request is sent to fonts.googleapis.com or fonts.gstatic.com. The fonts are identical, so the look is unchanged. Check this option to keep loading fonts from Google.', // @translate
                ],
                'attributes' => [
                    'id' => 'privacy_google_fonts',
                ],
            ]);
    }
}
