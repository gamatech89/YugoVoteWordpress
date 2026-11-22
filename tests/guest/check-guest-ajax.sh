#!/usr/bin/env bash
# Gost sa ZASTARELIM ključem (nonce) iz keširane stranice mora i dalje da može da koristi sajt.
# Simuliramo LiteSpeed keš stariji od 24h tako što šaljemo nevažeći nonce.
#
# Upotreba: bash tests/guest/check-guest-ajax.sh http://yugovote.local:8080
# Prolazi samo ako na kraju ispiše "SVE PASS".
# NAPOMENA: šalje samo zahteve koji čitaju (ne glasa), pa je bezbedno i protiv produkcije.

set -u
BASE="${1:?Upotreba: check-guest-ajax.sh BASE_URL}"
AJAX="$BASE/wp-admin/admin-ajax.php"
BAD="0000000000"
UA="Mozilla/5.0 (YugoVote guest test)"
fails=0

pass() { echo "PASS  $1"; }
fail() { echo "FAIL  $1"; echo "      $2"; fails=$((fails+1)); }

# expect_success NAME DATA... : POST na admin-ajax kao gost, očekuje {"success":true
expect_success() {
  local name="$1"; shift
  local out
  out=$(curl -s -A "$UA" -X POST "$AJAX" "$@")
  case "$out" in
    '{"success":true'*) pass "$name" ;;
    *) fail "$name" "odgovor: ${out:0:160}" ;;
  esac
}

# ID nekog dvoboja sa stranice arhive dvoboja
SD_PAGE=$(curl -s -A "$UA" "$BASE/showdown/")
SID=$(printf '%s' "$SD_PAGE" | grep -o 'data-showdown-id="[0-9]*"' | head -1 | grep -o '[0-9]*')
if [ -z "$SID" ]; then
  fail "pronađen ID dvoboja na /showdown/" "nema data-showdown-id"
else
  expect_success "dvoboj: učitavanje sa starim ključem"     -d "action=yuv_showdown_get_items&nonce=$BAD&showdown_id=$SID"
  expect_success "dvoboj: poredak sa starim ključem"        -d "action=yuv_showdown_get_leaderboard&nonce=$BAD&showdown_id=$SID"
fi

expect_success "ankete: učitaj još sa starim ključem"       -d "action=cs_load_more_polls&nonce=$BAD&page=2"
expect_success "kvizovi: filter arhive sa starim ključem"   -d "action=ygv_archive_filter&nonce=$BAD&cat=&diff=0&paged=1"
expect_success "liste: pretraga sa starim ključem"          -d "action=cs_search_voting_lists&nonce=$BAD&term=film"

# Prijava: sa starim ključem ne sme da padne na "Sigurnosna provera" (pogrešna lozinka je OK)
out=$(curl -s -A "$UA" -X POST "$AJAX" -d "action=yugo_ajax_login&nonce=$BAD&username=nepostojeci_korisnik_xyz&password=pogresna")
case "$out" in
  *Sigurnosna*) fail "prijava: stari ključ ne blokira prijavu" "odgovor: ${out:0:160}" ;;
  *) pass "prijava: stari ključ ne blokira prijavu" ;;
esac

# Kviz: gost ne sme dobiti REST nonce u keširanoj stranici (inače 403 "Cookie check failed" posle 24h)
QS=$(curl -s -A "$UA" "$BASE/kvizovi/" | grep -o 'var quizSettings = {[^}]*}')
case "$QS" in
  *'"nonce":""'*) pass "kviz: gost nema REST nonce u stranici" ;;
  '') fail "kviz: gost nema REST nonce u stranici" "quizSettings nije pronađen" ;;
  *) fail "kviz: gost nema REST nonce u stranici" "$(printf '%s' "$QS" | grep -o '"nonce":"[^"]*"')" ;;
esac

# Dvoboj: keširana stranica ne sme nositi tuđe rezultate
SD_ONE=$(printf '%s' "$SD_PAGE" | grep -o 'href="[^"]*/showdown/[a-z0-9-]*/"' | grep -v '/feed/' | head -1 | cut -d'"' -f2)
if [ -n "$SD_ONE" ]; then
  P=$(curl -s -A "$UA" "$SD_ONE")
  if printf '%s' "$P" | grep -q 'sd-results-screen--visible'; then
    fail "dvoboj: HTML bez rezultata (crta ih JS posle provere)" "$SD_ONE ima sd-results-screen--visible"
  else
    pass "dvoboj: HTML bez rezultata (crta ih JS posle provere)"
  fi
fi

if [ "$fails" -eq 0 ]; then echo "SVE PASS"; else echo "PALO: $fails"; exit 1; fi
