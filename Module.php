<?php declare(strict_types=1);

namespace Privacy;

if (!class_exists('Common\TraitModule', false)) {
    require_once file_exists(dirname(__DIR__) . '/Common/src/TraitModule.php')
        ? dirname(__DIR__) . '/Common/src/TraitModule.php'
        : dirname(__DIR__) . '/Common/TraitModule.php';
}

use Common\TraitModule;
use Laminas\EventManager\Event;
use Laminas\EventManager\SharedEventManagerInterface;
use Laminas\Http\ClientStatic;
use Laminas\Mvc\Controller\AbstractController;
use Laminas\ServiceManager\ServiceLocatorInterface;
use Laminas\View\Renderer\PhpRenderer;
use Omeka\Module\AbstractModule;

/**
 * Privacy.
 *
 * @copyright Daniel Berthereau 2021-2026
 * @license http://www.cecill.info/licences/Licence_CeCILL_V2.1-en.txt
 */
class Module extends AbstractModule
{
    use TraitModule;

    const NAMESPACE = __NAMESPACE__;

    public function attachListeners(SharedEventManagerInterface $sharedEventManager): void
    {
        // On every admin and public page, the layout triggers "view.layout"
        // after the stylesheets are registered and before the head is rendered,
        // so the external font links can be replaced there.
        $sharedEventManager->attach(
            '*',
            'view.layout',
            [$this, 'handleExternalFonts']
        );
    }

    /**
     * Replace external font stylesheets (Google Fonts) by the local ones.
     *
     * Works whether the link is added through assetUrl or hardcoded in a layout
     * (admin, public core layout, default theme), because it acts on the
     * headLink container after it is filled.
     */
    public function handleExternalFonts(Event $event): void
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $policy = (string) $settings->get('privacy_google_fonts', 'bundled');
        if ($policy === 'allow') {
            return;
        }

        /** @var \Laminas\View\Renderer\PhpRenderer $view */
        $view = $event->getTarget();
        $headLink = $view->headLink();
        $container = $headLink->getContainer();

        // Family tokens self-hosted by this module, in their URL-encoded forms
        // as found in Google Fonts hrefs.
        $bundledTokens = ['lato', 'open+sans', 'open%20sans', 'source+code+pro', 'source%20code%20pro'];

        $items = $container->getArrayCopy();
        $bundledRemoved = false;
        $anyRemoved = false;
        foreach ($items as $key => $item) {
            $href = isset($item->href) ? (string) $item->href : '';
            if ($href === '' || !preg_match('~fonts\.(?:googleapis|gstatic)\.com~i', $href)) {
                continue;
            }
            $hrefLower = strtolower($href);
            $matchesBundled = false;
            foreach ($bundledTokens as $token) {
                if (strpos($hrefLower, $token) !== false) {
                    $matchesBundled = true;
                    break;
                }
            }
            if ($policy === 'bundled' && !$matchesBundled) {
                continue;
            }
            unset($items[$key]);
            $anyRemoved = true;
            $bundledRemoved = $bundledRemoved || $matchesBundled;
        }

        if (!$anyRemoved) {
            return;
        }

        $container->exchangeArray(array_values($items));
        // The local stylesheet only covers bundled families: skip it when no
        // bundled link was actually replaced.
        if ($bundledRemoved) {
            $headLink->appendStylesheet($view->assetUrl('css/fonts.css', 'Privacy'));
        }
    }

    public function getConfigForm(PhpRenderer $renderer): string
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $defaults = $services->get('Config')['privacy']['config'];

        // Re-run the Apache/header check at every page load so the admin sees
        // the current status (and the .htaccess is upgraded/added on the fly if
        // it became writeable after a previous failed install).
        $messenger = $services->get('ControllerPluginManager')->get('messenger');
        $this->checkHeaderSetup($services, $messenger);

        $data = [];
        foreach ($defaults as $name => $value) {
            $data[$name] = $settings->get($name, $value);
        }

        $form = $services->get('FormElementManager')->get(Form\ConfigForm::class);
        $form->init();
        $form->setData($data);

        return $renderer->formCollection($form);
    }

    public function handleConfigForm(AbstractController $controller): bool
    {
        $services = $this->getServiceLocator();
        $settings = $services->get('Omeka\Settings');
        $defaults = $services->get('Config')['privacy']['config'];

        $form = $services->get('FormElementManager')->get(Form\ConfigForm::class);
        $form->init();
        $form->setData($controller->getRequest()->getPost());
        if (!$form->isValid()) {
            $controller->messenger()->addErrors($form->getMessages());
            return false;
        }

        $data = $form->getData();
        foreach ($defaults as $name => $default) {
            $value = $data[$name] ?? $default;
            $settings->set($name, is_bool($default) ? (bool) $value : $value);
        }
        return true;
    }

    public function install(ServiceLocatorInterface $services): void
    {
        $messenger = $services->get('ControllerPluginManager')->get('messenger');

        // Store default settings. Done first, so they are set even if the
        // htaccess header check below cannot complete on this server. The
        // module config is not merged into Config yet at install, so read and
        // write defaults early via getConfig() from TraitModule.
        $settings = $services->get('Omeka\Settings');
        $defaults = $this->getConfig()['privacy']['config'] ?? [];
        foreach ($defaults as $name => $value) {
            if ($settings->get($name) === null) {
                $settings->set($name, $value);
            }
        }

        $this->checkHeaderSetup($services, $messenger);
    }

    /**
     * Probe the current install for the Permissions-Policy header and write
     * (or upgrade) the directive in the root .htaccess when possible. Called
     * both at install time and every time the config form is rendered, so a
     * site that became compliant later (e.g. after fixing permissions) is
     * picked up automatically. Any failure produces a warning instead of an
     * exception: the module is functional even without the header.
     */
    protected function checkHeaderSetup(ServiceLocatorInterface $services, $messenger): void
    {
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
                $messenger->addWarning('The module is unable to check if the current install protects visitors against tracking and data theft via Google Chrome. See module’s installation documentation to set the header manually.'); // @translate
                $this->removeLegacyModule($services, $messenger);
                return;
            }
        }

        $headers = $response->getHeaders();
        if (empty($headers)) {
            $messenger->addWarning('The module is not able to check if the current install protects visitors against tracking and data theft via Google Chrome. See module’s installation documentation to set the header manually.'); // @translate
            $this->removeLegacyModule($services, $messenger);
            return;
        }

        $permissionsPolicy = $headers->get('Permissions-Policy');
        if (!empty($permissionsPolicy)) {
            $value = is_array($permissionsPolicy) ? implode(',', array_map('strval', $permissionsPolicy)) : (string) $permissionsPolicy->getFieldValue();
            if (stripos($value, 'browsing-topics') !== false) {
                $this->removeLegacyModule($services, $messenger);
                return;
            }
        }

        $htaccess = OMEKA_PATH . '/.htaccess';
        if (!file_exists($htaccess) || !is_readable($htaccess)) {
            $messenger->addWarning('It seems this installation doesn’t use the web server Apache: there is no file ".htaccess" at the root of Omeka. See module’s installation documentation to set the header manually.'); // @translate
            $this->removeLegacyModule($services, $messenger);
            return;
        }

        if (!is_writeable($htaccess)) {
            $messenger->addWarning('The file ".htaccess" at the root of Omeka is not writeable and cannot be updated by this module. See module’s installation documentation to set the header manually.'); // @translate
            $this->removeLegacyModule($services, $messenger);
            return;
        }

        $cli = $services->get('Omeka\Cli');
        $command = 'apachectl -t -D DUMP_MODULES';
        $output = $cli->execute($command);
        if ($output === false) {
            $messenger->addWarning('It seems this installation doesn’t use the web server Apache: command "apachectl" is not available. See module’s installation documentation to set the header manually.'); // @translate
            $this->removeLegacyModule($services, $messenger);
            return;
        }

        if (!stripos($output, 'headers_module')) {
            $messenger->addWarning('Apache is working, but its module "headers" is not enabled. Your admin should run command "sudo a2enmod headers; sudo systemctl restart apache2" to enable it.'); // @translate
            $this->removeLegacyModule($services, $messenger);
            return;
        }

        $content = file_get_contents($htaccess);

        // Migrate a legacy FLoC directive in place from "interest-cohort" to
        // "browsing-topics" (Google Topics api).
        if (stripos($content, 'interest-cohort') !== false && stripos($content, 'browsing-topics') === false) {
            $content = preg_replace('~interest-cohort\s*=\s*\(\s*\)~i', 'browsing-topics=()', $content);
            file_put_contents($htaccess, $content);
            $messenger->addSuccess('The privacy header has been upgraded from FLoC ("interest-cohort") to Topics API ("browsing-topics") in your file ".htaccess".'); // @translate
            $this->removeLegacyModule($services, $messenger);
            return;
        }

        if (stripos($content, 'browsing-topics') !== false) {
            $this->removeLegacyModule($services, $messenger);
            return;
        }

        $content .= <<<'HTACCESS'

<IfModule mod_headers.c>
    Header always set Permissions-Policy: browsing-topics=()
</IfModule>

HTACCESS;
        file_put_contents($htaccess, $content);

        $messenger->addSuccess('The privacy anti-tracking/anti-data-theft header has been added successfully to your file ".htaccess".'); // @translate
        $this->removeLegacyModule($services, $messenger);
    }

    /**
     * Drop the legacy NoGoogleChromeFlockTracking row from the "module" table
     * once Privacy is installed successfully, so the deprecated module stops
     * appearing in the admin modules list. The on-disk module directory is left
     * untouched and can be removed manually.
     */
    protected function removeLegacyModule(ServiceLocatorInterface $services, $messenger): void
    {
        try {
            /** @var \Doctrine\DBAL\Connection $connection */
            $connection = $services->get('Omeka\Connection');
            $affected = (int) $connection->executeStatement(
                'DELETE FROM module WHERE id = ?',
                ['NoGoogleChromeFlockTracking']
            );
        } catch (\Throwable $e) {
            return;
        }
        if ($affected > 0) {
            $messenger->addNotice('The deprecated module "NoGoogleChromeFlockTracking" has been removed from the modules table. Its files on disk can be deleted manually.'); // @translate
        }
    }
}
