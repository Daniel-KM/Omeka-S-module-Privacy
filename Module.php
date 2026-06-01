<?php declare(strict_types=1);

namespace NoGoogleChromeFlockTracking;

use Laminas\Http\ClientStatic;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Omeka\Module\AbstractModule;
use Omeka\Module\Exception\ModuleCannotInstallException;

/**
 * No Google Chrome Flock Tracking.
 *
 * @copyright Daniel Berthereau 2021-2023
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    public function getConfig()
    {
        return [
            'translator' => [
                'translation_file_patterns' => [
                    [
                        'type' => 'gettext',
                        'base_dir' => __DIR__ . '/language',
                        'pattern' => '%s.mo',
                        'text_domain' => null,
                    ],
                ],
            ],
        ];
    }

    public function install(ServiceLocatorInterface $services): void
    {
        $messenger = $services->get('ControllerPluginManager')->get('messenger');
        $t = $services->get('MvcTranslator');

        $viewHelpers = $services->get('ViewHelperManager');
        $serverUrl = $viewHelpers->get('serverUrl');
        $assetUrl = $viewHelpers->get('assetUrl');
        $url = $assetUrl('css/style.css', 'Omeka', false, false);
        try {
            $response = ClientStatic::get($serverUrl($url));
        } catch (\Throwable $e) {
        }

        // In some cases, the server cannot get its own url.
        if (empty($response)) {
            try {
                $response = ClientStatic::get('http://localhost' . $url);
            } catch (\Throwable $e) {
                throw new ModuleCannotInstallException(
                    $t->translate('The module is unable to check if the current install protects visitors against tracking and data theft via Google Chrome.') // @translate
                        . ' ' . $t->translate('See module’s installation documentation.') // @translate
                );
            }
        }

        $headers = $response->getHeaders();
        if (empty($headers)) {
            throw new ModuleCannotInstallException(
                $t->translate('The module is not able to check if the current install protects visitors against tracking and data theft via Google Chrome.') // @translate
                    . ' ' . $t->translate('See module’s installation documentation.') // @translate
            );
        }

        $permissionsPolicy = $headers->get('Permissions-Policy');
        if (!empty($permissionsPolicy)) {
            $value = is_array($permissionsPolicy) ? implode(',', array_map('strval', $permissionsPolicy)) : (string) $permissionsPolicy->getFieldValue();
            if (stripos($value, 'browsing-topics') !== false) {
                $messenger->addNotice('Your site is already configured and let unchanged.'); // @translate
                $messenger->addNotice('The module can be uninstalled.'); // @translate
                return;
            }
        }

        $htaccess = OMEKA_PATH . '/.htaccess';
        if (!file_exists($htaccess) || !is_readable($htaccess)) {
            throw new ModuleCannotInstallException(
                $t->translate('It seems this installation doesn’t use the web server Apache: there is no file ".htaccess" at the root of Omeka.') // @translate
                    . ' ' . $t->translate('See module’s installation documentation.') // @translate
            );
        }

        if (!is_writeable($htaccess)) {
            throw new ModuleCannotInstallException(
                $t->translate('The file ".htaccess" at the root of Omeka is not writeable and cannot be updated by this module.') // @translate
                    . ' ' . $t->translate('See module’s installation documentation.') // @translate
            );
        }

        $cli = $services->get('Omeka\Cli');
        $command = 'apachectl -t -D DUMP_MODULES';
        $output = $cli->execute($command);
        if ($output === false) {
            throw new ModuleCannotInstallException(
                $t->translate('It seems this installation doesn’t use the web server Apache: command "apachectl" is not available.') // @translate
                    . ' ' . $t->translate('See module’s installation documentation.') // @translate
            );
        }

        if (!stripos($output, 'headers_module')) {
            throw new ModuleCannotInstallException(
                $t->translate('Apache is working, but its module "headers" is not enabled. Your admin should run command "sudo a2enmod headers; sudo systemctl restart apache2" to enable it.') // @translate
                    . ' ' . $t->translate('See module’s installation documentation.') // @translate
            );
        }

        $content = file_get_contents($htaccess);

        // Migrate a legacy FLoC directive in place from "interest-cohort" to
        // "browsing-topics" (Google Topics api).
        if (stripos($content, 'interest-cohort') !== false && stripos($content, 'browsing-topics') === false) {
            $content = preg_replace('~interest-cohort\s*=\s*\(\s*\)~i', 'browsing-topics=()', $content);
            file_put_contents($htaccess, $content);
            $messenger->addSuccess('The privacy header has been upgraded from FLoC ("interest-cohort") to Topics API ("browsing-topics") in your file ".htaccess".'); // @translate
            $messenger->addNotice('The module can be uninstalled.'); // @translate
            return;
        }

        if (stripos($content, 'browsing-topics') !== false) {
            $messenger->addNotice('Your site is already configured and let unchanged.'); // @translate
            $messenger->addNotice('The module can be uninstalled.'); // @translate
            return;
        }

        $content .= <<<'HTACCESS'

<IfModule mod_headers.c>
    Header always set Permissions-Policy: browsing-topics=()
</IfModule>

HTACCESS;
        file_put_contents($htaccess, $content);

        $messenger->addSuccess('The privacy anti-tracking/anti-data-theft header has been added successfully to your file ".htaccess".'); // @translate
        $messenger->addNotice('The module can be uninstalled.'); // @translate
    }
}
