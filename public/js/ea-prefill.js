console.log('[prefill] v9 (TomSelect-safe, per-line exact targeting)');

(function () {
  function setVal(el, val) {
    if (!el) return;
    el.value = val ?? '';
    el.dispatchEvent(new Event('input',  { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function centsToMajor(cents) {
    const n = parseInt(cents, 10);
    if (isNaN(n)) return '';
    return (n / 100).toString().replace('.', ','); // FR simple
  }

  // ex: "Invoice[lines][3][item]" -> "Invoice[lines][3]"
  function basePrefixFrom(selectName) {
    return (selectName || '').replace(/\[[^\]]+\]$/, '');
  }

  function byExactName(form, fullName) {
    return form.querySelector(
      `input[name="${fullName}"], select[name="${fullName}"], textarea[name="${fullName}"]`
    );
  }

  function readMeta(select) {
    // Preferred path: TomSelect instance keeps a stable options map
    const ts = select.tomselect || select.tomSelect;
    const value = select.value;

    if (ts && value && ts.options && ts.options[value]) {
      const opt = ts.options[value]; // populated from data-data / server JSON
      return {
        name:  opt.name  ?? opt.text ?? '',
        price: typeof opt.price === 'number' ? opt.price : parseInt(opt.price || '0', 10),
        tax:   opt.tax   ?? ''
      };
    }

    // Fallback: selected <option> data-*
    const optEl = select.selectedOptions && select.selectedOptions[0];
    return {
      name:  optEl?.dataset?.name  || '',
      price: parseInt(optEl?.dataset?.price || '0', 10),
      tax:   optEl?.dataset?.tax   || ''
    };
  }

  document.addEventListener('change', function (e) {
    // target select (works for native & TomSelect)
    const select = e.target.matches('select[data-controller="prefill"]')
      ? e.target
      : e.target.closest && e.target.closest('select[data-controller="prefill"]');

    if (!select || !select.value) return;

    // make sure we act only inside the current line
    const form = select.closest('form') || document;
    const base = basePrefixFrom(select.getAttribute('name') || '');
    if (!base) return;

    const nameDesignation = `${base}[designation]`;
    const nameQty         = `${base}[quantity]`;
    const nameUnit        = `${base}[unitPriceCents]`;
    const nameTax         = `${base}[taxRate]`;

    const designation = byExactName(form, nameDesignation);
    const qty         = byExactName(form, nameQty);
    const unit        = byExactName(form, nameUnit);
    const tax         = byExactName(form, nameTax);

    const meta = readMeta(select);

    // non-intrusive filling
    if (designation && !designation.value) setVal(designation, meta.name);
    if (qty && (!qty.value || qty.value === '0' || qty.value === '0.000')) setVal(qty, '1');
    if (unit && (!unit.value || parseFloat(unit.value.replace(',', '.')) === 0)) {
      setVal(unit, centsToMajor(meta.price || 0));
    }
    if (tax && meta.tax) setVal(tax, meta.tax);
  });

  // EA adds/removes lines; nothing to do here — the next 'change' will use updated names.
  document.addEventListener('ea.collection.item-added',  () => {});
  document.addEventListener('ea.collection.item-removed', () => {});
})();
