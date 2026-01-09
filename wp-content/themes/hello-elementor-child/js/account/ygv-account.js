(function () {
  // Require the container and the localized config
  const root = document.getElementById('ygv-account');
  if (!root || typeof YGV_ACCOUNT === 'undefined') return;

  // --- REST endpoints (provided via wp_localize_script) ---
  const stateUrl  = YGV_ACCOUNT.restRoot + '/user/state';
  const spendUrl  = YGV_ACCOUNT.restRoot + '/tokens/spend';
  const startUrl  = (id) => YGV_ACCOUNT.restRoot + '/quiz/' + id + '/start';
  const submitUrl = (id) => YGV_ACCOUNT.restRoot + '/quiz/' + id + '/submit';

  // --- Elements ---
  const $ = (id) => document.getElementById(id);
  const elTokens     = $('ygv-tokens');
  const elMax        = $('ygv-tokens-max');
  const elBar        = $('ygv-tokens-bar');
  const elNext       = $('ygv-next-in');
  const elOverallLvl = $('ygv-overall-level');
  const elOverallXP  = $('ygv-overall-xp');
  const elCats       = $('ygv-cats');

  const elSpendBtn = $('ygv-spend-8');
  const elSpendRes = $('ygv-spend-result');

  const elStartBtn = $('ygv-start');
  const elStartRes = $('ygv-start-result');
  const elQuizId   = $('ygv-quiz-id');

  const elSubmitBtn  = $('ygv-submit');
  const elSubmitRes  = $('ygv-submit-result');
  const elAttemptId  = $('ygv-attempt-id');
  const elCorrect    = $('ygv-correct');
  const elTotal      = $('ygv-total');

  // --- Helpers ---
  let nextRefillSeconds = 0;
  let tickTimer = null;

  function fmtTime(s) {
    s = Math.max(0, s | 0);
    const m = Math.floor(s / 60);
    const sec = s % 60;
    return String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
  }

  function startTick() {
    if (tickTimer) clearInterval(tickTimer);
    tickTimer = setInterval(() => {
      if (nextRefillSeconds > 0) {
        nextRefillSeconds--;
        if (elNext) elNext.textContent = fmtTime(nextRefillSeconds);
      }
    }, 1000);
  }

  async function api(url, opts = {}) {
    const res = await fetch(url, {
      method: opts.method || 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': YGV_ACCOUNT.nonce
      },
      body: opts.body ? JSON.stringify(opts.body) : undefined,
      credentials: 'same-origin'
    });
    let json = {};
    try { json = await res.json(); } catch (e) {}
    if (!res.ok) {
      const msg = (json && (json.message || json.code)) || 'Greška.';
      throw { status: res.status, message: msg, data: json };
    }
    return json;
  }

  function renderState(s) {
    if (elTokens) elTokens.textContent = s.tokens.current;
    if (elMax)    elMax.textContent    = s.tokens.max;

    const pct = s.tokens.max ? Math.min(100, Math.round(100 * s.tokens.current / s.tokens.max)) : 0;
    if (elBar) elBar.style.width = pct + '%';

    nextRefillSeconds = s.tokens.next_refill_in || 0;
    if (elNext) elNext.textContent = fmtTime(nextRefillSeconds);
    startTick();

    if (elOverallLvl) elOverallLvl.textContent = s.overall.overall_level;
    if (elOverallXP)  elOverallXP.textContent  = s.overall.overall_xp;

    if (elCats) {
      elCats.innerHTML = '';
      (s.categories || []).forEach(c => {
        const li = document.createElement('li');

        const left = document.createElement('div');
        left.textContent = c.name;

        const right = document.createElement('div');
        const toNext = (c.to_next === null) ? 'MAX' : (c.to_next + ' XP');
        right.innerHTML = `Lvl <strong>${c.level}</strong> · XP <strong>${c.xp}</strong> · do sledećeg: <strong>${toNext}</strong>`;

        li.append(left, right);
        elCats.appendChild(li);
      });
    }
  }

  async function load() {
    try {
      const s = await api(stateUrl);
      renderState(s);
    } catch (e) {
      console.error('Load state error:', e);
      if (elStartRes) elStartRes.textContent = 'Greška pri učitavanju stanja.';
    }
  }

  // --- Events ---
  if (elSpendBtn) {
    elSpendBtn.addEventListener('click', async () => {
      elSpendBtn.disabled = true;
      if (elSpendRes) elSpendRes.textContent = '...';
      try {
        await api(spendUrl, { method: 'POST', body: { cost: 8 } });
        if (elSpendRes) elSpendRes.textContent = 'Uspešno potrošeno.';
        await load();
      } catch (e) {
        if (elSpendRes) elSpendRes.textContent = e && e.message ? e.message : 'Greška.';
      } finally {
        elSpendBtn.disabled = false;
      }
    });
  }

  if (elStartBtn) {
    elStartBtn.addEventListener('click', async () => {
      const id = parseInt(elQuizId && elQuizId.value, 10);
      if (!id) {
        if (elStartRes) elStartRes.textContent = 'Unesite ID kviza.';
        return;
      }
      elStartBtn.disabled = true;
      if (elStartRes) elStartRes.textContent = '...';
      try {
        const r = await api(startUrl(id), { method: 'POST' });
        if (elStartRes) elStartRes.textContent = `Pokušaj #${r.attempt_id} je kreiran. Srećno!`;
        if (elAttemptId) elAttemptId.value = r.attempt_id;
        await load();
      } catch (e) {
        if (elStartRes) elStartRes.textContent = e && e.message ? e.message : 'Greška.';
      } finally {
        elStartBtn.disabled = false;
      }
    });
  }

  if (elSubmitBtn) {
    elSubmitBtn.addEventListener('click', async () => {
      const id = parseInt(elQuizId && elQuizId.value, 10);
      const a  = parseInt(elAttemptId && elAttemptId.value, 10);
      const c  = parseInt(elCorrect && elCorrect.value, 10);
      const t  = parseInt(elTotal && elTotal.value, 10);

      if (!id || !a || isNaN(c) || !t) {
        if (elSubmitRes) elSubmitRes.textContent = 'Popunite ID kviza, attempt, tačnih i ukupno.';
        return;
      }

      elSubmitBtn.disabled = true;
      if (elSubmitRes) elSubmitRes.textContent = '...';
      try {
        const r = await api(submitUrl(id), {
          method: 'POST',
          body: { attempt_id: a, correct: c, total: t }
        });
        if (elSubmitRes) elSubmitRes.textContent =
          (r.passed ? 'Položeno' : 'Nije položeno') + ` · ${r.score_percent}% · +${r.xp_awarded} XP`;
        await load();
      } catch (e) {
        if (elSubmitRes) elSubmitRes.textContent = e && e.message ? e.message : 'Greška.';
      } finally {
        elSubmitBtn.disabled = false;
      }
    });
  }

  // Initial state load
  load();
})();
