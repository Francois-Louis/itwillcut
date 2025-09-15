{*
  Avertissement au checkout — hook displayCheckoutSummaryTop
*}
<section class="itwillcut itwillcut--checkout" role="region" aria-label="{l s='Legal age warning' d='Modules.Itwillcut.Shop'}">
  <div class="itwillcut__inner">
    <span class="itwillcut__icon" aria-hidden="true">18+</span>
    <p class="itwillcut__text">
      {l s=$itwillcut_text d='Modules.Itwillcut.Shop'}
      {if $itwillcut_cms_url}
        <a class="itwillcut__link" href="{$itwillcut_cms_url|escape:'html':'UTF-8'}">
          {l s='Read the disclaimer' d='Modules.Itwillcut.Shop'}
        </a>
      {/if}
    </p>
  </div>
</section>