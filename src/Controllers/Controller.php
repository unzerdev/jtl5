<?php declare(strict_types = 1);

namespace Plugin\s360_unzer_shop5\src\Controllers;

use JTL\Plugin\PluginInterface;
use JTL\Shop;
use JTL\Smarty\JTLSmarty;
use Plugin\s360_unzer_shop5\src\Utils\Config;
use Plugin\s360_unzer_shop5\src\Utils\JtlLoggerTrait;

/**
 * Abstract Controller
 *
 * @package Plugin\s360_unzer_shop5\src\Controllers
 */
abstract class Controller
{
    use JtlLoggerTrait;

    /**
     * @var array
     */
    protected $request;

    /**
     * @var PluginInterface
     */
    protected $plugin;

    /**
     * @var JTLSmarty
     */
    protected $smarty;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @SuppressWarnings(PHPMD.Superglobals)
     * @param PluginInterface $plugin
     * @param JTLSmarty|null $smarty
     */
    public function __construct(PluginInterface $plugin, JTLSmarty $smarty = null)
    {
        /** @var Config $config */
        $this->config = Shop::Container()->get(Config::class);
        $this->plugin = $plugin;
        $this->smarty = $smarty ?? Shop::Smarty();

        $this->prepare();
    }

    /**
     * Expeected to fill smarty variables and return template.
     *
     * @return string
     */
    abstract public function handle(): string;

    /**
     * Prepare variable which are passed to the view.
     *
     * @return void
     */
    protected function prepare(): void
    {
    }

    /**
     * Render a template view.
     *
     * @param string $template
     * @param array $data
     * @return string
     */
    protected function view(string $template, array $data = []): string
    {
        foreach ($data as $key => $value) {
            $this->smarty->assign($key, $value);
        }

        $tpl = $this->plugin->getPaths()->getFrontendPath() . $template . '.tpl';
        $custom = $this->plugin->getPaths()->getFrontendPath() . $template . '_custom.tpl';

        if (file_exists($custom)) {
            $tpl = $custom;
        }

        return $this->smarty->fetch($tpl);
    }

    /**
     * Set plugin value.
     *
     * @param PluginInterface $plugin
     * @return self
     */
    public function setPlugin(PluginInterface $plugin): self
    {
        $this->plugin = $plugin;
        return $this;
    }

    /**
     * Get plugin value.
     *
     * @return PluginInterface
     */
    public function getPlugin(): PluginInterface
    {
        return $this->plugin;
    }
}
