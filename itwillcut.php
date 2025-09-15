<?php

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

class Itwillcut extends Module
{
    /** Clés de configuration (stockées via Configuration) */
    private const CFG_ENABLED_FOOTER   = 'ITWILLCUT_ENABLED_FOOTER';
    private const CFG_ENABLED_CHECKOUT = 'ITWILLCUT_ENABLED_CHECKOUT';
    private const CFG_CMS_ID           = 'ITWILLCUT_CMS_ID';
    private const CFG_TEXT_FOOTER      = 'ITWILLCUT_TEXT_FOOTER';
    private const CFG_TEXT_CHECKOUT    = 'ITWILLCUT_TEXT_CHECKOUT';
    private const CFG_ARIA_LABEL       = 'ITWILLCUT_ARIA_LABEL';
    private const CFG_ALT_LOGO         = 'ITWILLCUT_ALT_LOGO';
    private const CFG_COMPANY          = 'ITWILLCUT_COMPANY';
    private const CFG_CMS_CONTENT      = 'ITWILLCUT_CMS_CONTENT';
    private const CFG_AGE_LIMIT        = 'ITWILLCUT_AGE_LIMIT';

    /**
     * Constructeur: métadonnées module
     */
    public function __construct()
    {
        $this->name = 'itwillcut';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'FL-Toussaint';
        $this->need_instance = 0;
        $this->bootstrap = true; // BO moderne

        parent::__construct();

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
     * - Crée la page CMS disclaimer
     */
    public function install()
    {
        $ok = parent::install()
            && $this->registerHook('displayFooter')              // Footer section
            && $this->registerHook('displayCheckoutSummaryTop')  // Au-dessus du récapitulatif
            && $this->registerHook('displayHeader')              // Chargement CSS/JS
            && Configuration::updateValue(self::CFG_ENABLED_FOOTER, 1)
            && Configuration::updateValue(self::CFG_ENABLED_CHECKOUT, 1)
            && Configuration::updateValue(self::CFG_AGE_LIMIT, 18)
            // Textes par défaut (EN base, avec placeholder {age_limit})
            && Configuration::updateValue(self::CFG_TEXT_FOOTER, 'Sale of knives to minors is prohibited. {age_limit}+ only.')
            && Configuration::updateValue(self::CFG_TEXT_CHECKOUT, 'Reminder: sale of knives to minors is prohibited ({age_limit}+).');

        // Crée la page CMS disclaimer multilingue
        if ($ok) {
            $cms = new CMS();
            $cms->id_cms_category = 1; // Catégorie racine
            $cms->active = 1;
            $cms->position = 0;
            $cms->link_rewrite = [];
            $cms->meta_title = [];
            $cms->meta_description = [];
            $cms->content = [];

            foreach (Language::getLanguages(false) as $lang) {
                $idLang = (int)$lang['id_lang'];
                $cms->link_rewrite[$idLang] = 'knife-disclaimer';
                $cms->meta_title[$idLang] = 'Knife Sale Disclaimer';
                $cms->meta_description[$idLang] = 'Legal disclaimer for knife sales.';

                // Contenu par défaut du CMS avec placeholders
                $defaultCmsContent = '<article id="disclaimer"><h1>Disclaimer – {company} (Sale of Handcrafted Knives)</h1><p>The <strong>sale of knives to minors (under {age_limit})</strong> is strictly prohibited by French law.</p></article>';

                // Remplacement simple des placeholders à l'installation
                $age = 18;
                $company = 'My Company';
                $cms->content[$idLang] = strtr($defaultCmsContent, [
                    '{age_limit}' => (string)$age,
                    '{company}' => $company,
                ]);
            }

            $ok = $ok && $cms->add();
            if ($ok && $cms->id) {
                Configuration::updateValue(self::CFG_CMS_ID, (int)$cms->id);
            }
        }

        return $ok;
    }

    /**
     * Désinstallation: supprime les clés de configuration et la page CMS.
     */
    public function uninstall()
    {
        $ok = parent::uninstall();

        // Supprime la page CMS créée par le module
        $idCms = (int)Configuration::get(self::CFG_CMS_ID);
        if ($idCms > 0) {
            $cms = new CMS($idCms);
            $ok = $ok && $cms->delete();
        }

        // Supprime toutes les clés de configuration
        $keys = [
            self::CFG_ENABLED_FOOTER,
            self::CFG_ENABLED_CHECKOUT,
            self::CFG_CMS_ID,
            self::CFG_TEXT_FOOTER,
            self::CFG_TEXT_CHECKOUT,
            self::CFG_AGE_LIMIT,
            self::CFG_ARIA_LABEL,
            self::CFG_ALT_LOGO,
            self::CFG_COMPANY,
            self::CFG_CMS_CONTENT
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

            // Multilingue : sauvegarde pour chaque langue
            foreach (Language::getLanguages(false) as $lang) {
                $idLang = (int)$lang['id_lang'];
                Configuration::updateValue(self::CFG_TEXT_FOOTER . '_' . $idLang, Tools::getValue(self::CFG_TEXT_FOOTER . '_' . $idLang));
                Configuration::updateValue(self::CFG_TEXT_CHECKOUT . '_' . $idLang, Tools::getValue(self::CFG_TEXT_CHECKOUT . '_' . $idLang));
                Configuration::updateValue(self::CFG_ARIA_LABEL . '_' . $idLang, Tools::getValue(self::CFG_ARIA_LABEL . '_' . $idLang));
                Configuration::updateValue(self::CFG_ALT_LOGO . '_' . $idLang, Tools::getValue(self::CFG_ALT_LOGO . '_' . $idLang));
                Configuration::updateValue(self::CFG_COMPANY . '_' . $idLang, Tools::getValue(self::CFG_COMPANY . '_' . $idLang));
                Configuration::updateValue(self::CFG_CMS_CONTENT . '_' . $idLang, Tools::getValue(self::CFG_CMS_CONTENT . '_' . $idLang));
            }

            $output .= $this->displayConfirmation($this->trans('Settings updated', [], 'Modules.Itwillcut.Admin'));
        }

        // Action: mise à jour du CMS disclaimer
        if (Tools::isSubmit('updateCmsDisclaimer')) {
            $idCms = (int)Configuration::get(self::CFG_CMS_ID);
            if ($idCms > 0) {
                $cms = new CMS($idCms);
                $age = (int)Configuration::get(self::CFG_AGE_LIMIT) ?: 18;
                
                foreach (Language::getLanguages(false) as $lang) {
                    $idLang = (int)$lang['id_lang'];
                    
                    // Récupère le contenu CMS pour cette langue
                    $cmsContent = Configuration::get(self::CFG_CMS_CONTENT . '_' . $idLang);
                    if (!$cmsContent) {
                        $cmsContent = Configuration::get(self::CFG_CMS_CONTENT);
                    }
                    // Si toujours pas de contenu, utilise la valeur par défaut
                    if (!$cmsContent) {
                        $cmsContent = $this->getDefaultCmsContent();
                    }
                    
                    // Récupère le nom de la company pour cette langue
                    $company = Configuration::get(self::CFG_COMPANY . '_' . $idLang);
                    if (!$company) {
                        $company = Configuration::get(self::CFG_COMPANY) ?: 'My Company';
                    }
                    
                    // Remplace les placeholders
                    $cms->content[$idLang] = strtr($cmsContent, [
                        '{age_limit}' => (string)$age,
                        '{company}' => $company,
                    ]);
                }
                
                if ($cms->update()) {
                    $output .= $this->displayConfirmation($this->trans('CMS disclaimer page updated successfully', [], 'Modules.Itwillcut.Admin'));
                } else {
                    $output .= $this->displayError($this->trans('Error updating CMS disclaimer page.', [], 'Modules.Itwillcut.Admin'));
                }
            } else {
                $output .= $this->displayError($this->trans('No CMS disclaimer page found.', [], 'Modules.Itwillcut.Admin'));
            }
        }

        // Build HelperForm
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = (int)Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int)Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->title = $this->displayName;
        $helper->show_toolbar = false;
        $helper->submit_action = 'submitItwillcut';

        // Configuration des langues pour HelperForm
        $languages = Language::getLanguages(false);
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');

        // Ajoute la clé is_default pour chaque langue
        foreach ($languages as &$lang) {
            $lang['is_default'] = ((int)$lang['id_lang'] === $defaultLang) ? 1 : 0;
        }
        unset($lang); // cleanup reference

        $helper->languages = $languages;

        // Onglet 1: Configuration des bannières
        $fields_form_banners = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Banner Settings', [], 'Modules.Itwillcut.Admin'),
                    'icon'  => 'icon-warning-sign',
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
                        'type'  => 'text',
                        'label' => $this->trans('Company name', [], 'Modules.Itwillcut.Admin'),
                        'name'  => self::CFG_COMPANY,
                        'lang'  => true,
                        'desc'  => $this->trans('Your company name. Used in all texts as {company}.', [], 'Modules.Itwillcut.Admin'),
                    ],
                    [
                        'type'  => 'textarea',
                        'label' => $this->trans('Footer text', [], 'Modules.Itwillcut.Admin'),
                        'name'  => self::CFG_TEXT_FOOTER,
                        'lang'  => true,
                        'rows'  => 2,
                        'desc'  => $this->trans('Placeholders: {age_limit}, {company}', [], 'Modules.Itwillcut.Admin'),
                    ],
                    [
                        'type'  => 'textarea',
                        'label' => $this->trans('Checkout text', [], 'Modules.Itwillcut.Admin'),
                        'name'  => self::CFG_TEXT_CHECKOUT,
                        'lang'  => true,
                        'rows'  => 2,
                        'desc'  => $this->trans('Placeholders: {age_limit}, {company}', [], 'Modules.Itwillcut.Admin'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->trans('Banner aria-label', [], 'Modules.Itwillcut.Admin'),
                        'name'  => self::CFG_ARIA_LABEL,
                        'lang'  => true,
                        'desc'  => $this->trans('Accessibility: aria-label for the banner region.', [], 'Modules.Itwillcut.Admin'),
                    ],
                    [
                        'type'  => 'text',
                        'label' => $this->trans('Logo alt text', [], 'Modules.Itwillcut.Admin'),
                        'name'  => self::CFG_ALT_LOGO,
                        'lang'  => true,
                        'desc'  => $this->trans('Accessibility: alt attribute for the logo image.', [], 'Modules.Itwillcut.Admin'),
                    ],
                ],
                'submit' => ['title' => $this->trans('Save', [], 'Modules.Itwillcut.Admin')],
                'tab' => 'banners',
            ],
        ];

        // Onglet 2: Configuration CMS
        $fields_form_cms = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('CMS Disclaimer Page', [], 'Modules.Itwillcut.Admin'),
                    'icon'  => 'icon-file-text',
                ],
                'input'  => [
                    [
                        'type' => 'select',
                        'label' => $this->trans('CMS page for "disclaimer" link', [], 'Modules.Itwillcut.Admin'),
                        'name'  => self::CFG_CMS_ID,
                        'options' => [
                            'query' => $this->getCmsPagesOptions(),
                            'id'    => 'id',
                            'name'  => 'name',
                        ],
                        'desc' => $this->trans('If empty, the notice is shown without link. You can create the page later.', [], 'Modules.Itwillcut.Admin'),
                    ],
                    [
                        'type'  => 'textarea',
                        'label' => $this->trans('Disclaimer CMS content', [], 'Modules.Itwillcut.Admin'),
                        'name'  => self::CFG_CMS_CONTENT,
                        'lang'  => true,
                        'rows'  => 8,
                        'desc'  => $this->trans('Content for the disclaimer CMS page. Placeholders: {age_limit}, {company}', [], 'Modules.Itwillcut.Admin'),
                    ],
                ],
                'submit' => ['title' => $this->trans('Save', [], 'Modules.Itwillcut.Admin')],
                'buttons' => [
                    [
                        'type' => 'submit',
                        'name' => 'updateCmsDisclaimer',
                        'title' => $this->trans('Update CMS disclaimer page', [], 'Modules.Itwillcut.Admin'),
                        'icon' => 'process-icon-refresh',
                        'class' => 'btn btn-warning pull-right',
                    ],
                ],
                'tab' => 'cms',
            ],
        ];

        // Prépare les valeurs multilingues pour chaque langue
        $fields_value = [
            self::CFG_ENABLED_FOOTER   => (int)Configuration::get(self::CFG_ENABLED_FOOTER),
            self::CFG_ENABLED_CHECKOUT => (int)Configuration::get(self::CFG_ENABLED_CHECKOUT),
            self::CFG_CMS_ID           => (int)Configuration::get(self::CFG_CMS_ID),
            self::CFG_AGE_LIMIT        => (int)Configuration::get(self::CFG_AGE_LIMIT) ?: 18,
        ];

        // Valeurs multilingues - utilise les anciennes valeurs comme fallback
        $defaultCompany = Configuration::get(self::CFG_COMPANY) ?: 'My Company';
        $defaultFooterText = Configuration::get(self::CFG_TEXT_FOOTER) ?: 'The sale of knives to persons under {age_limit} years of age is prohibited by {company}.';
        $defaultCheckoutText = Configuration::get(self::CFG_TEXT_CHECKOUT) ?: 'Warning: This order may contain knives. Sale to minors under {age_limit} is prohibited by {company}.';
        $defaultAriaLabel = Configuration::get(self::CFG_ARIA_LABEL) ?: 'Legal age warning';
        $defaultAltLogo = Configuration::get(self::CFG_ALT_LOGO) ?: 'Logo couteaux interdits aux mineurs';
        $defaultCmsContent = Configuration::get(self::CFG_CMS_CONTENT) ?: '<article id="disclaimer">
        <h1>Disclaimer – {company} (Sale of Handcrafted Knives)</h1>

        <h2>Legal Framework and Age Requirement</h2>
        <p>
            The <strong>sale of knives to minors (under 18)</strong> is strictly prohibited by French law.
            In France, most of our knives (hunting knives, folding knives, kitchen knives, etc.) are considered
            <em>armes blanches</em> and are generally classified as <strong>Category D</strong>. Their sale is permitted
            to <strong>adults only</strong>, and any transfer to a minor is strictly prohibited.
        </p>
        <p>
            Under Article <strong>R. 313-16-1 of the French Internal Security Code</strong> (in force since 6 September 2025
            pursuant to Decree No. 2025-894 of 5 September 2025 and the Ministerial Order of 5 September 2025),
            {company} displays and expressly reminds users of this prohibition on its website.
            While <strong>possession</strong> by adults is lawful, the <strong>carrying and transport</strong> of such items
            without a legitimate reason are prohibited and may require justification in case of inspection.
        </p>
        <p>
            By placing an order on this website, the buyer <strong>declares to be at least 18 years old</strong> and to have the legal capacity
            to purchase these products. The buyer also undertakes to use the knives purchased <strong>in strict compliance with the law</strong>
            applicable in France and, where relevant, in the country of delivery.
        </p>

        <h2>Lawful Use and Buyer’s Responsibility</h2>
        <p>
            It is the <strong>buyer’s responsibility</strong> to inform themselves, <strong>before ordering</strong>, about the laws and regulations
            in force regarding the purchase, possession, transport, and use of knives in their country of residence
            <strong>and</strong> of delivery. By confirming the order, the buyer expressly states that the purchase does not violate
            any applicable local, national, or international law. In case of doubt, the buyer agrees to consult the competent authorities
            or review the official texts prior to purchase.
        </p>
        <p>
            If, despite all precautions, the product ordered infringes a law of the country of delivery (for example due to import or possession restrictions),
            <strong>{company} shall not be held liable</strong>. Any legal or customs consequence arising from a purchase that is non-compliant with local laws
            (including seizure by the authorities) shall be borne exclusively by the buyer. <strong>No refund</strong> or compensation shall be owed by the Seller
            in the event of confiscation by a competent authority due to illegal import or use.
        </p>

        <h2>Limitation of the Seller’s Liability</h2>
        <p>
            {company}, its owner, and anyone involved in operating the website shall in no event be liable for the consequences of any use of the products sold
            that is <strong>non-compliant, improper, dangerous, or illegal</strong>. In particular, the Seller assumes <strong>no liability for any damage</strong>
            (direct or indirect) caused to the buyer or third parties arising from the purchase or use of a knife in a manner that is unintended, abusive, or unlawful.
            The buyer or end user bears <strong>all risks</strong> associated with the acquisition, use, transport, and/or import of knives purchased on this site.
        </p>
        <p>
            By ordering, the buyer acknowledges they will use the knives <strong>solely</strong> for their intended purposes (culinary use, regulated hunting,
            collecting, tableware, etc.) and <strong>never for a prohibited purpose</strong> (e.g., as a weapon against persons). Any misuse or dangerous use
            is at the <strong>buyer’s sole risk</strong>, without any liability on the part of {company}.
        </p>

        <h2>Statutory Guarantees and the Seller’s Obligations</h2>
        <p>
            The Seller remains bound by <strong>mandatory legal obligations</strong> towards the consumer, notably regarding <strong>product safety</strong>,
            the <strong>legal guarantee of conformity</strong> (French Consumer Code, Articles L.217-3 et seq.), and <strong>product liability for defective products</strong>
            (French Civil Code, Articles 1245 et seq.). Nothing in this disclaimer is intended or shall be construed to limit
            <strong>the consumer’s statutory rights</strong> or the Seller’s liability in the event of a breach of these public-order obligations.
            The Seller undertakes to provide the customer with all <strong>mandatory information</strong> relating to its products (essential characteristics,
            safety instructions, warranty conditions, etc.) in accordance with French consumer-protection rules.
        </p>

        <h2>Safety, Storage, and Abuse Prevention</h2>
        <p>
            To prevent accidents or malicious use, the buyer must ensure that knives are <strong>stored securely</strong>, out of reach of children
            or anyone lacking the necessary discernment. The Seller recommends keeping knives under lock and key or in secure packaging when not in use,
            and never granting minors or unauthorized persons access to these items.
        </p>
        <p>
            Our handcrafted knives are intended for <strong>legitimate and specific uses</strong> (culinary preparation, collection, professional use in compliance with the law, etc.).
            <strong>Any use outside this scope</strong>—in particular any <strong>dangerous, abusive, or illegal use</strong>—constitutes a misuse of the product and is strongly discouraged.
            The buyer is urged to strictly follow the safety instructions supplied with the products and to exercise responsibility in handling them.
        </p>

        <h2>Shipping and Customs</h2>
        <p>
            For <strong>public-safety</strong> reasons, the Seller reserves the right to refuse or cancel delivery to certain
            <strong>sensitive addresses</strong> (schools, nurseries, public buildings, or any address deemed inappropriate). In such a case,
            the buyer will be informed and refunded where applicable.
        </p>
        <p>
            For international sales, the buyer is solely responsible for complying with <strong>customs formalities</strong> and import laws
            in the destination country. Any <strong>customs duties and taxes</strong>, where applicable, are borne by the buyer and are typically
            collected by the authorities in the country of delivery upon entry of the parcel. In the event of a <strong>customs hold</strong> or seizure
            by local authorities, those authorities will contact the buyer directly with instructions. The Seller will make reasonable efforts to assist,
            <strong>without any obligation of result</strong> or legal commitment. The buyer is strongly advised to check any local restrictions on knife imports
            (blade length, mechanism type, etc.) prior to ordering to avoid delivery issues.
        </p>

        <h2>Further Information</h2>
        <p>
            For any questions regarding applicable law or the use of our products, {company}’s team is available to assist.
            To learn more about the current regulatory framework governing the sale, possession, and transport of knives,
            you can consult our <a href="/blog/knife-regulations-france" rel="noopener">dedicated blog article</a> (link on the website).
            We strive to keep our information up to date to ensure a <strong>safe, lawful</strong>, and transparent purchasing experience.
        </p>
    </article>';

        foreach ($languages as $lang) {
            $idLang = (int)$lang['id_lang'];
            $company = Configuration::get(self::CFG_COMPANY . '_' . $idLang);
            $footerText = Configuration::get(self::CFG_TEXT_FOOTER . '_' . $idLang);
            $checkoutText = Configuration::get(self::CFG_TEXT_CHECKOUT . '_' . $idLang);
            $ariaLabel = Configuration::get(self::CFG_ARIA_LABEL . '_' . $idLang);
            $altLogo = Configuration::get(self::CFG_ALT_LOGO . '_' . $idLang);
            $cmsContent = Configuration::get(self::CFG_CMS_CONTENT . '_' . $idLang);
            // Si pas de valeur spécifique à cette langue, utilise la valeur par défaut
            $fields_value[self::CFG_COMPANY][$idLang] = $company ?: $defaultCompany;
            $fields_value[self::CFG_TEXT_FOOTER][$idLang] = $footerText ?: $defaultFooterText;
            $fields_value[self::CFG_TEXT_CHECKOUT][$idLang] = $checkoutText ?: $defaultCheckoutText;
            $fields_value[self::CFG_ARIA_LABEL][$idLang] = $ariaLabel ?: $defaultAriaLabel;
            $fields_value[self::CFG_ALT_LOGO][$idLang] = $altLogo ?: $defaultAltLogo;
            $fields_value[self::CFG_CMS_CONTENT][$idLang] = $cmsContent ?: $defaultCmsContent;
        }
        $helper->fields_value = $fields_value;

        return $output . $helper->generateForm([$fields_form_banners, $fields_form_cms]);
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
        $idLang = (int)$this->context->language->id;
        $company = (string)Configuration::get(self::CFG_COMPANY . '_' . $idLang);
        if (!$company) {
            $company = (string)Configuration::get(self::CFG_COMPANY) ?: 'My Company';
        }
        return strtr($text, [
            '{age_limit}' => (string)$age,
            '{company}' => $company,
        ]);
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

        // Récupère le texte dans la langue courante
        $idLang = (int)$this->context->language->id;
        $text = $this->getTextByLanguage(self::CFG_TEXT_FOOTER, $idLang);
        $text = $this->renderText($text);

        $ariaLabel = $this->getTextByLanguage(self::CFG_ARIA_LABEL, $idLang);
        $altLogo = $this->getTextByLanguage(self::CFG_ALT_LOGO, $idLang);
        $this->context->smarty->assign([
            'itwillcut_text' => $text,
            'itwillcut_cms_url' => $this->getCmsLinkUrl(),
            'itwillcut_aria_label' => $ariaLabel,
            'itwillcut_alt_logo' => $altLogo,
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/front/footer_banner.tpl');
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

        // Récupère le texte dans la langue courante
        $idLang = (int)$this->context->language->id;
        $text = $this->getTextByLanguage(self::CFG_TEXT_CHECKOUT, $idLang);
        $text = $this->renderText($text);

        $ariaLabel = $this->getTextByLanguage(self::CFG_ARIA_LABEL, $idLang);
        $altLogo = $this->getTextByLanguage(self::CFG_ALT_LOGO, $idLang);
        $this->context->smarty->assign([
            'itwillcut_text' => $text,
            'itwillcut_cms_url' => $this->getCmsLinkUrl(),
            'itwillcut_aria_label' => $ariaLabel,
            'itwillcut_alt_logo' => $altLogo,
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/front/checkout_notice.tpl');
    }

    /**
     * Enregistre CSS/JS front (vanilla).
     * - On garde ça léger: une seule feuille de style et un JS “no-op” pour évolutivité.
     * @return void
     */
    public function hookDisplayHeader()
    {
        $this->context->controller->addJS('modules/' . $this->name . '/views/js/itwillcut.js');
        $this->context->controller->addCSS('modules/' . $this->name . '/views/css/itwillcut.css');
    }

    /**
     * Récupère le texte dans la langue spécifiée, avec fallback sur la langue par défaut.
     * @param string $configKey Clé de configuration (ex: self::CFG_TEXT_FOOTER)
     * @param int $idLang ID de la langue courante
     * @return string
     */
    private function getTextByLanguage(string $configKey, int $idLang): string
    {
        // Essaie d'abord la langue courante
        $text = (string)Configuration::get($configKey . '_' . $idLang);

        if (!empty($text)) {
            return $text;
        }

        // Fallback sur la langue par défaut
        $defaultLang = (int)Configuration::get('PS_LANG_DEFAULT');
        if ($idLang !== $defaultLang) {
            $text = (string)Configuration::get($configKey . '_' . $defaultLang);
            if (!empty($text)) {
                return $text;
            }
        }

        // Fallback sur l'ancienne valeur (pour la migration)
        $text = (string)Configuration::get($configKey);
        if (!empty($text)) {
            return $text;
        }

        // Fallback sur le texte par défaut selon le type
        if ($configKey === self::CFG_TEXT_FOOTER) {
            return 'The sale of knives to persons under {age_limit} years of age is prohibited.';
        } elseif ($configKey === self::CFG_TEXT_CHECKOUT) {
            return 'Warning: This order may contain knives. Sale to minors under {age_limit} is prohibited.';
        }

        return '';
    }

    /**
     * Retourne le contenu par défaut complet pour la page CMS disclaimer.
     * @return string
     */
    private function getDefaultCmsContent(): string
    {
        return '<article id="disclaimer">
    <h1>Disclaimer – {company} (Sale of Handcrafted Knives)</h1>

    <h2>Legal Framework and Age Requirement</h2>
    <p>
      The <strong>sale of knives to minors (under {age_limit})</strong> is strictly prohibited by French law.
      In France, most of our knives (hunting knives, folding knives, kitchen knives, etc.) are considered
      <em>armes blanches</em> and are generally classified as <strong>Category D</strong>. Their sale is permitted
      to <strong>adults only</strong>, and any transfer to a minor is strictly prohibited.
    </p>
    <p>
      Under Article <strong>R. 313-16-1 of the French Internal Security Code</strong> (in force since 6 September 2025
      pursuant to Decree No. 2025-894 of 5 September 2025 and the Ministerial Order of 5 September 2025),
      {company} displays and expressly reminds users of this prohibition on its website.
      While <strong>possession</strong> by adults is lawful, the <strong>carrying and transport</strong> of such items
      without a legitimate reason are prohibited and may require justification in case of inspection.
    </p>
    <p>
      By placing an order on this website, the buyer <strong>declares to be at least {age_limit} years old</strong> and to have the legal capacity
      to purchase these products. The buyer also undertakes to use the knives purchased <strong>in strict compliance with the law</strong>
      applicable in France and, where relevant, in the country of delivery.
    </p>

    <h2>Lawful Use and Buyer\'s Responsibility</h2>
    <p>
      It is the <strong>buyer\'s responsibility</strong> to inform themselves, <strong>before ordering</strong>, about the laws and regulations
      in force regarding the purchase, possession, transport, and use of knives in their country of residence
      <strong>and</strong> of delivery. By confirming the order, the buyer expressly states that the purchase does not violate
      any applicable local, national, or international law. In case of doubt, the buyer agrees to consult the competent authorities
      or review the official texts prior to purchase.
    </p>
    <p>
      If, despite all precautions, the product ordered infringes a law of the country of delivery (for example due to import or possession restrictions),
      <strong>{company} shall not be held liable</strong>. Any legal or customs consequence arising from a purchase that is non-compliant with local laws
      (including seizure by the authorities) shall be borne exclusively by the buyer. <strong>No refund</strong> or compensation shall be owed by the Seller
      in the event of confiscation by a competent authority due to illegal import or use.
    </p>

    <h2>Limitation of the Seller\'s Liability</h2>
    <p>
      {company}, its owner, and anyone involved in operating the website shall in no event be liable for the consequences of any use of the products sold
      that is <strong>non-compliant, improper, dangerous, or illegal</strong>. In particular, the Seller assumes <strong>no liability for any damage</strong>
      (direct or indirect) caused to the buyer or third parties arising from the purchase or use of a knife in a manner that is unintended, abusive, or unlawful.
      The buyer or end user bears <strong>all risks</strong> associated with the acquisition, use, transport, and/or import of knives purchased on this site.
    </p>
    <p>
      By ordering, the buyer acknowledges they will use the knives <strong>solely</strong> for their intended purposes (culinary use, regulated hunting,
      collecting, tableware, etc.) and <strong>never for a prohibited purpose</strong> (e.g., as a weapon against persons). Any misuse or dangerous use
      is at the <strong>buyer\'s sole risk</strong>, without any liability on the part of {company}.
    </p>

    <h2>Statutory Guarantees and the Seller\'s Obligations</h2>
    <p>
      The Seller remains bound by <strong>mandatory legal obligations</strong> towards the consumer, notably regarding <strong>product safety</strong>,
      the <strong>legal guarantee of conformity</strong> (French Consumer Code, Articles L.217-3 et seq.), and <strong>product liability for defective products</strong>
      (French Civil Code, Articles 1245 et seq.). Nothing in this disclaimer is intended or shall be construed to limit
      <strong>the consumer\'s statutory rights</strong> or the Seller\'s liability in the event of a breach of these public-order obligations.
      The Seller undertakes to provide the customer with all <strong>mandatory information</strong> relating to its products (essential characteristics,
      safety instructions, warranty conditions, etc.) in accordance with French consumer-protection rules.
    </p>

    <h2>Safety, Storage, and Abuse Prevention</h2>
    <p>
      To prevent accidents or malicious use, the buyer must ensure that knives are <strong>stored securely</strong>, out of reach of children
      or anyone lacking the necessary discernment. The Seller recommends keeping knives under lock and key or in secure packaging when not in use,
      and never granting minors or unauthorized persons access to these items.
    </p>
    <p>
      Our handcrafted knives are intended for <strong>legitimate and specific uses</strong> (culinary preparation, collection, professional use in compliance with the law, etc.).
      <strong>Any use outside this scope</strong>—in particular any <strong>dangerous, abusive, or illegal use</strong>—constitutes a misuse of the product and is strongly discouraged.
      The buyer is urged to strictly follow the safety instructions supplied with the products and to exercise responsibility in handling them.
    </p>

    <h2>Shipping and Customs</h2>
    <p>
      For <strong>public-safety</strong> reasons, the Seller reserves the right to refuse or cancel delivery to certain
      <strong>sensitive addresses</strong> (schools, nurseries, public buildings, or any address deemed inappropriate). In such a case,
      the buyer will be informed and refunded where applicable.
    </p>
    <p>
      For international sales, the buyer is solely responsible for complying with <strong>customs formalities</strong> and import laws
      in the destination country. Any <strong>customs duties and taxes</strong>, where applicable, are borne by the buyer and are typically
      collected by the authorities in the country of delivery upon entry of the parcel. In the event of a <strong>customs hold</strong> or seizure
      by local authorities, those authorities will contact the buyer directly with instructions. The Seller will make reasonable efforts to assist,
      <strong>without any obligation of result</strong> or legal commitment. The buyer is strongly advised to check any local restrictions on knife imports
      (blade length, mechanism type, etc.) prior to ordering to avoid delivery issues.
    </p>

    <h2>Further Information</h2>
    <p>
      For any questions regarding applicable law or the use of our products, {company}\'s team is available to assist.
      To learn more about the current regulatory framework governing the sale, possession, and transport of knives,
      you can consult our <a href="/blog/knife-regulations-france" rel="noopener">dedicated blog article</a> (link on the website).
      We strive to keep our information up to date to ensure a <strong>safe, lawful</strong>, and transparent purchasing experience.
    </p>
  </article>';
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
