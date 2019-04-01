<?php
/**
 * Routes.
 *
 * @copyright Zikula contributors (Zikula)
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @author Zikula contributors <info@ziku.la>.
 * @link https://ziku.la
 * @link https://ziku.la
 * @version Generated by ModuleStudio 1.0.0 (https://modulestudio.de).
 */

namespace Zikula\RoutesModule\Helper;

use Exception;
use FOS\JsRoutingBundle\Command\DumpCommand;
use JMS\I18nRoutingBundle\Router\I18nLoader;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Zikula\Common\Translator\TranslatorInterface;
use Zikula\ExtensionsModule\Api\ApiInterface\VariableApiInterface;
use Zikula\SettingsModule\Api\ApiInterface\LocaleApiInterface;

class RouteDumperHelper
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var VariableApiInterface
     */
    private $variableApi;

    /**
     * @var LocaleApiInterface
     */
    private $localeApi;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var DumpCommand
     */
    private $dumpCommand;

    public function __construct(
        ContainerInterface $container,
        VariableApiInterface $variableApi,
        LocaleApiInterface $localeApi,
        TranslatorInterface $translator,
        DumpCommand $dumpCommand
    ) {
        $this->container = $container;
        $this->variableApi = $variableApi;
        $this->localeApi = $localeApi;
        $this->translator = $translator;
        $this->dumpCommand = $dumpCommand;
    }

    /**
     * Dump the routes exposed to javascript to '/web/js/fos_js_routes.js'
     *
     * @throws Exception
     */
    public function dumpJsRoutes(string $lang = null): string
    {
        // determine list of supported languages
        $installedLanguages = $this->localeApi->getSupportedLocales();
        if (isset($lang) && in_array($lang, $installedLanguages, true)) {
            // use provided lang if available
            $langs = [$lang];
        } else {
            $multilingual = (bool)$this->variableApi->getSystemVar('multilingual');
            if ($multilingual) {
                // get all available locales
                $langs = $installedLanguages;
            } else {
                // get only the default locale
                $langs = [$this->variableApi->getSystemVar('language_i18n', 'en')]; //$this->container->getParameter('locale');
            }
        }

        $errors = '';

        // force deletion of existing file
        $targetPath = sprintf('%s/web/js/fos_js_routes.js', $this->container->getParameter('kernel.project_dir'));
        if (file_exists($targetPath)) {
            try {
                unlink($targetPath);
            } catch (Exception $exception) {
                $errors .= $this->translator->__f("Error: Could not delete '%path' because %msg", [
                    '%path' => $targetPath,
                    '%msg' => $exception->getMessage()
                ]);
            }
        }

        foreach ($langs as $locale) {
            $input = new ArrayInput(['--locale' => $locale . I18nLoader::ROUTING_PREFIX]);
            $output = new NullOutput();
            try {
                $this->dumpCommand->run($input, $output);
            } catch (RuntimeException $exception) {
                $errors .= $exception->getMessage() . '. ';
            }
        }

        return $errors;
    }
}
