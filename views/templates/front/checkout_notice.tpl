{*
  Avertissement au checkout — hook displayCheckoutSummaryTop
*}
{strip}
  <section class="itwillcut itwillcut--checkout" role="region"
  aria-label="{$itwillcut_aria_label|escape:'html':'UTF-8'}">
    <div class="itwillcut__inner">
      <img class="itwillcut__icon" src="modules/itwillcut/views/img/logo-couteaux-interdits-mineurs.png" alt="{$itwillcut_alt_logo|escape:'html':'UTF-8'}" width="32" height="32" aria-hidden="true" />
      <p class="itwillcut__text">
        {$itwillcut_text nofilter}
        {if $itwillcut_cms_url}
          <a class="itwillcut__link" href="{$itwillcut_cms_url|escape:'html':'UTF-8'}">
            {l s='Read the disclaimer' d='Modules.Itwillcut.Shop'}
          </a>
        {/if}
      </p>
    </div>
  </section>
{/strip}