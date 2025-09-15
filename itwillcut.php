<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class Itwillcut extends Module
{
    /** @var string Nom technique du module (dossier) */
    public $name = 'itwillcut';

    /** @var string */
    public $version = '1.0.0';

    /** @var string */
    public $author = 'Fl-Toussaint';

    /** @var bool */
    public $bootstrap = true; // active Bootstrap BO pour HelperForm

    /** Clés de configuration (stockées via Configuration) */
    public const CFG_ENABLED_FOOTER   = 'ITWILLCUT_ENABLED_FOOTER';
    public const CFG_ENABLED_CHECKOUT = 'ITWILLCUT_ENABLED_CHECKOUT';
    public const CFG_CMS_ID           = 'ITWILLCUT_CMS_ID';
    public const CFG_TEXT_FOOTER      = 'ITWILLCUT_TEXT_FOOTER';
    public const CFG_TEXT_CHECKOUT    = 'ITWILLCUT_TEXT_CHECKOUT';
    public const CFG_AGE_LIMIT        = 'ITWILLCUT_AGE_LIMIT';

    /**
     * Constructeur: métadonnées module
     */
    public function __construct()
    {
        parent::__construct();

        $this->tab = 'front_office_features';
        $this->displayName = $this->trans('ItWillCut – Knife age warning', [], 'Modules.Itwillcut.Admin');
        $this->description = $this->trans('Displays an age restriction notice (18+) in footer and at checkout summary.', [], 'Modules.Itwillcut.Admin');

        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];
    }

    /**
     * Indique qu’on utilise le nouveau système de traduction (XLF).
     * @return bool
     */
    public function isUsingNewTranslationSystem()
    {
        return true; // cf. doc "new translation system" 1.7.6+ / PS9. :contentReference[oaicite:4]{index=4}
    }

    /**
     * Installation du module:
     * - Enregistre les hooks displayFooter & displayCheckoutSummaryTop
     * - Enregistre actionFrontControllerSetMedia pour les assets
     * - Initialise la config par défaut
     */
    public function install()
    {
        return parent::install()
            && $this->registerHook('displayFooter')              // Footer section. :contentReference[oaicite:5]{index=5}
            && $this->registerHook('displayCheckoutSummaryTop')  // Au-dessus du récapitulatif. :contentReference[oaicite:6]{index=6}
            && $this->registerHook('actionFrontControllerSetMedia') // Chargement CSS/JS. :contentReference[oaicite:7]{index=7}
            && Configuration::updateValue(self::CFG_ENABLED_FOOTER, 1)
            && Configuration::updateValue(self::CFG_ENABLED_CHECKOUT, 1)
            && Configuration::updateValue(self::CFG_CMS_ID, 0) // Aucun lien au début
            && Configuration::updateValue(self::CFG_AGE_LIMIT, 18)
            // Textes par défaut (EN base, avec placeholder {age_limit})
            && Configuration::updateValue(self::CFG_TEXT_FOOTER, 'Sale of knives to minors is prohibited. {age_limit}+ only.')
            && Configuration::updateValue(self::CFG_TEXT_CHECKOUT, 'Reminder: sale of knives to minors is prohibited ({age_limit}+).');
    }

    /**
     * Désinstallation: supprime les clés de configuration.
     */
    public function uninstall()
    {
        $ok = parent::uninstall();
        $keys = [
            self::CFG_ENABLED_FOOTER, self::CFG_ENABLED_CHECKOUT, self::CFG_CMS_ID,
            self::CFG_TEXT_FOOTER, self::CFG_TEXT_CHECKOUT, self::CFG_AGE_LIMIT,
        ];
        foreach ($keys as $k) {
            $ok = $ok && Configuration::deleteByName($k);
        }
        return $ok;
    }

    /**
     * Page de configuration (Legacy HelperForm).
     * Champs:
     * - switches d’activation
     * - select CMS page (lien)
     * - age_limit (int)
     * - textes personnalisables (multilingues dans le BO via système de trad)
     *
     * @return string HTML
     */
    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitItwillcut')) {
            // Sanitize + save
            Configuration::updateValue(self::CFG_ENABLED_FOOTER, (int)Tools::getValue(self::CFG_ENABLED_FOOTER));
            Configuration::updateValue(self::CFG_ENABLED_CHECKOUT, (int)Tools::getValue(self::CFG_ENABLED_CHECKOUT));
            Configuration::updateValue(self::CFG_CMS_ID, (int)Tools::getValue(self::CFG_CMS_ID));
            Configuration::updateValue(self::CFG_AGE_LIMIT, max(0, (int)Tools::getValue(self::CFG_AGE_LIMIT)));
            Configuration::updateValue(self::CFG_TEXT_FOOTER, Tools::getValue(self::CFG_TEXT_FOOTER));
            Configuration::updateValue(self::CFG_TEXT_CHECKOUT, Tools::getValue(self::CFG_TEXT_CHECKOUT));

            $output .= $this->displayConfirmation($this->trans('Settings updated', [], 'Modules.Itwillcut.Admin'));
        }

        // Build HelperForm
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;
        $helper->submit_action = 'submitItwillcut';

        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Modules.Itwillcut.Admin'),
                    'icon'  => 'icon-cogs',
                ],
                'input'  => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Show in footer', [], 'Modules.Itwillcut.Admin'),
                        'name' => self::CFG_ENABLED_FOOTER,
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'on', 'value' => 1, 'label' => $this->trans('Enabled', [], 'Modules.Itwillcut.Admin')],
                            ['id' => 'off', 'value' => 0, 'label' => $this->trans('Disabled', [], 'Modules.Itwillcut.Admin')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Show on checkout summary top', [], 'Modules.Itwillcut.Admin'),
                        'name' => self::CFG_ENABLED_CHECKOUT,
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'on2', 'value' => 1, 'label' => $this->trans('Enabled', [], 'Modules.Itwillcut.Admin')],
                            ['id' => 'off2', 'value' => 0, 'label' => $this->trans('Disabled', [], 'Modules.Itwillcut.Admin')],
                        ],
                        'desc' => $this->trans('Displays the notice above the order summary during checkout.', [], 'Modules.Itwillcut.Admin'),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('CMS page for “disclaimer” link', [], 'Modules.Itwillcut.Admin'),
                        'name'  => self::CFG_CMS_ID,
                        'options' => [
                            'query' => $this->getCmsPagesOptions(),
                            'id'    => 'id',
                            'name'  => 'name',
                        ],
                        'desc' => $this->trans('If empty, the notice is shown without link. You can create the page later.', [], 'Modules.Itwillcut.Admin'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->trans('Age limit', [], 'Modules.Itwillcut.Admin'),
                        'name'  => self::CFG_AGE_LIMIT,
                        'class' => 'fixed-width-sm',
                        'desc'  => $this->trans('Default: 18. Used to replace {age_limit} in texts.', [], 'Modules.Itwillcut.Admin'),
                    ],
                    [
                        'type'  => 'textarea',
                        'label' => $this->trans('Footer text (EN base)', [], 'Modules.Itwillcut.Admin'),
                        'name'  => self::CFG_TEXT_FOOTER,
                        'rows'  => 2,
                        'desc'  => $this->trans('Placeholders: {age_limit}', [], 'Modules.Itwillcut.Admin'),
                    ],
                    [
                        'type'  => 'textarea',
                        'label' => $this->trans('Checkout text (EN base)', [], 'Modules.Itwillcut.Admin'),
                        'name'  => self::CFG_TEXT_CHECKOUT,
                        'rows'  => 2,
                        'desc'  => $this->trans('Placeholders: {age_limit}', [], 'Modules.Itwillcut.Admin'),
                    ],
                ],
                'submit' => ['title' => $this->trans('Save', [], 'Modules.Itwillcut.Admin')],
            ],
        ];

        $helper->fields_value = [
            self::CFG_ENABLED_FOOTER   => (int)Configuration::get(self::CFG_ENABLED_FOOTER),
            self::CFG_ENABLED_CHECKOUT => (int)Configuration::get(self::CFG_ENABLED_CHECKOUT),
            self::CFG_CMS_ID           => (int)Configuration::get(self::CFG_CMS_ID),
            self::CFG_AGE_LIMIT        => (int)Configuration::get(self::CFG_AGE_LIMIT),
            self::CFG_TEXT_FOOTER      => (string)Configuration::get(self::CFG_TEXT_FOOTER),
            self::CFG_TEXT_CHECKOUT    => (string)Configuration::get(self::CFG_TEXT_CHECKOUT),
        ];

        return $output.$helper->generateForm([$fields_form]);
    }

    /**
     * Récupère les pages CMS pour le select.
     * @return array [['id'=>0,'name'=>'— None —'], ...]
     */
    private function getCmsPagesOptions()
    {
        $options = [
            ['id' => 0, 'name' => $this->trans('— None —', [], 'Modules.Itwillcut.Admin')],
        ];

        $idLang = (int)$this->context->language->id;
        $pages = CMS::listCms($idLang, false, true); // pages actives
        if (is_array($pages)) {
            foreach ($pages as $p) {
                $options[] = ['id' => (int)$p['id_cms'], 'name' => sprintf('#%d — %s', $p['id_cms'], $p['meta_title'])];
            }
        }
        return $options;
    }

    /**
     * Remplace {age_limit} dans un texte par la valeur de config.
     * @param string $text
     * @return string
     */
    private function renderText(string $text)
    {
        $age = (int)Configuration::get(self::CFG_AGE_LIMIT);
        return strtr($text, ['{age_limit}' => (string)$age]);
    }

    /**
     * Hook d’injection Footer.
     * @return string HTML
     */
    public function hookDisplayFooter()
    {
        if (!Configuration::get(self::CFG_ENABLED_FOOTER)) {
            return '';
        }

        $text = $this->renderText((string)Configuration::get(self::CFG_TEXT_FOOTER));
        $this->context->smarty->assign([
            'itwillcut_text' => $text,
            'itwillcut_cms_url' => $this->getCmsLinkUrl(),
        ]);

        return $this->fetch('module:'.$this->name.'/views/templates/hook/footer_banner.tpl');
    }

    /**
     * Hook Checkout summary top (au-dessus du récap).
     * @return string
     */
    public function hookDisplayCheckoutSummaryTop()
    {
        if (!Configuration::get(self::CFG_ENABLED_CHECKOUT)) {
            return '';
        }

        $text = $this->renderText((string)Configuration::get(self::CFG_TEXT_CHECKOUT));
        $this->context->smarty->assign([
            'itwillcut_text' => $text,
            'itwillcut_cms_url' => $this->getCmsLinkUrl(),
        ]);

        return $this->fetch('module:'.$this->name.'/views/templates/hook/checkout_notice.tpl');
    }

    /**
     * Enregistre CSS/JS front (vanilla) via le hook recommandé.
     * - On garde ça léger: une seule feuille de style et un JS “no-op” pour évolutivité.
     * @return void
     */
    public function hookActionFrontControllerSetMedia()
    {
        // CSS global (footer + checkout)
        $this->context->controller->registerStylesheet(
            'module-'.$this->name.'-front',
            'modules/'.$this->name.'/views/css/front/itwillcut.css',
            ['media' => 'all', 'priority' => 150]
        );

        // JS (léger, prêt pour évolutions si besoin)
        $this->context->controller->registerJavascript(
            'module-'.$this->name.'-front',
            'modules/'.$this->name.'/views/js/front/itwillcut.js',
            ['position' => 'bottom', 'priority' => 150]
        );
    }

    /**
     * Construit le lien vers la page CMS, ou renvoie null si non configuré.
     * @return string|null
     */
    private function getCmsLinkUrl(): ?string
    {
        $idCms = (int)Configuration::get(self::CFG_CMS_ID);
        if ($idCms <= 0) {
            return null;
        }
        try {
            $link = $this->context->link->getCMSLink($idCms, null, null, $this->context->language->id);
            return $link ?: null;
        } catch (Exception $e) {
            return null;
        }
    }
}