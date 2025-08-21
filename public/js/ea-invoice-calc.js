console.log('[calc] script chargé v6');

(function () {
  let isUpdatingTotals = false;

  function toCents(inputEl) {
    if (!inputEl) return 0;
    const raw = (inputEl.value || '').toString().replace(/\s/g, '').replace(',', '.');
    const n = parseFloat(raw);
    return Math.round((isNaN(n) ? 0 : n) * 100);
  }
  function toFloat(inputEl) {
    if (!inputEl) return 0;
    const raw = (inputEl.value || '').toString().replace(/\s/g, '').replace(',', '.');
    const n = parseFloat(raw);
    return isNaN(n) ? 0 : n;
  }
  function setMoney(inputEl, cents) {
    if (!inputEl) return;
    inputEl.value = (cents / 100).toString().replace('.', ',');
  }
  function getForm(el) {
    if (el && typeof el.closest === 'function') {
      const f = el.closest('.ea-new-form form, .ea-edit-form form, form');
      if (f) return f;
    }
    return document.querySelector('.ea-new-form form, .ea-edit-form form, form');
  }

  function linePrefixFromName(name) {
    return name.replace(/\[[^\]]+\]$/, '');
  }
  function fieldBySuffix(form, prefix, suffix) {
    const sel = `input[name="${prefix}${suffix}"], select[name="${prefix}${suffix}"], textarea[name="${prefix}${suffix}"]`;
    return form.querySelector(sel);
  }

  function recalc(form) {
    if (!form || isUpdatingTotals) return;
    isUpdatingTotals = true;

    const totalNetEl   = form.querySelector('input[name$="[totalNet]"]');
    const totalVatEl   = form.querySelector('input[name$="[totalVat]"]');
    const totalGrossEl = form.querySelector('input[name$="[totalGross]"]');

    let sumNet = 0, sumVat = 0;

    const qtyInputs = form.querySelectorAll('input[name$="[quantity]"]');
    qtyInputs.forEach(qtyEl => {
      const name = qtyEl.getAttribute('name') || '';
      const prefix = linePrefixFromName(name);

      const unitEl = fieldBySuffix(form, prefix, '[unitPriceCents]');
      const discEl = fieldBySuffix(form, prefix, '[discountCents]');
      const taxSel = fieldBySuffix(form, prefix, '[taxRate]');

      if (!unitEl && !discEl && !taxSel) return;

      const qty      = toFloat(qtyEl);
      const unit     = toCents(unitEl);
      const discount = toCents(discEl);
      const gross    = Math.round(qty * unit);
      const net      = Math.max(0, gross - discount);

      let rate = 0;
      if (taxSel && taxSel.selectedOptions && taxSel.selectedOptions[0]) {
        const p = parseFloat(taxSel.selectedOptions[0].dataset.percent || '0');
        rate = isNaN(p) ? 0 : p;
      }
      const vat = Math.round(net * rate);

      sumNet += net;
      sumVat += vat;
    });

    setMoney(totalNetEl,   sumNet);
    setMoney(totalVatEl,   sumVat);
    setMoney(totalGrossEl, sumNet + sumVat);

    isUpdatingTotals = false;
  }

  function shouldTriggerFor(target) {
    if (!(target instanceof Element)) return false;
    const name = target.getAttribute('name') || '';
    return (
      /\[quantity\]$/.test(name) ||
      /\[unitPriceCents\]$/.test(name) ||
      /\[discountCents\]$/.test(name) ||
      /\[taxRate\]$/.test(name)
    );
  }

  function onAnyChange(e) {
    const t = e.target;
    if (!shouldTriggerFor(t)) return;
    recalc(getForm(t));
  }

  document.addEventListener('input', onAnyChange);
  document.addEventListener('change', onAnyChange);
  document.addEventListener('ea.collection.item-added', function (e) {
    recalc(getForm(e.target || document.body));
  });
  window.addEventListener('load', function () {
    recalc(getForm(document.body));
  });
})();
