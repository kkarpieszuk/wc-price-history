#!/usr/bin/env bash
set -euo pipefail

WPUSER="konrad"

ATTRIBUTE_NAME="Kolor"
ATTRIBUTE_SLUG="pa_kolor" # WooCommerce tak oznacza globalne atrybuty
PRODUCT_NAME="Produkt z wariantami"
PRODUCT_SLUG="produkt-z-wariantami"

VARIANT1_COLOR="czerwony"
VARIANT1_PRICE="15.00"

VARIANT2_COLOR="biały"
VARIANT2_PRICE="30.00"

echo "Sprawdzam atrybut globalny..."
# Sprawdź istnienie atrybutu po slugu lub nazwie i pobierz jego ID (puste jeśli brak)
EXISTING_ATTRIBUTE_ID=$(wp wc product_attribute list --user="$WPUSER" --format=json | jq -r '.[] | select((.slug=="kolor") or (.slug=="pa_kolor") or (.name=="Kolor")) | .id' | head -n1)

if [[ -z "$EXISTING_ATTRIBUTE_ID" ]]; then
  echo "Tworzę atrybut 'Kolor'..."
  wp wc product_attribute create \
    --user="$WPUSER" \
    --name="$ATTRIBUTE_NAME" \
    --slug="kolor" \
    --type="select"
else
  echo "Atrybut 'Kolor' już istnieje ✅"
fi

# Pobierz/ustal ID atrybutu 'kolor' do użycia w komendach term
ATTRIBUTE_ID=$(wp wc product_attribute list --user="$WPUSER" --format=json | jq -r '.[] | select((.slug=="kolor") or (.slug=="pa_kolor") or (.name=="Kolor")) | .id' | head -n1)

create_term_if_missing() {
  local term=$1
  if ! wp wc product_attribute_term list "$ATTRIBUTE_ID" --user="$WPUSER" --format=json | jq -e ".[] | select(.name == \"$term\")" > /dev/null; then
    echo "Tworzę termin: $term"
    wp wc product_attribute_term create "$ATTRIBUTE_ID" --user="$WPUSER" --name="$term"
  else
    echo "Termin '$term' już istnieje ✅"
  fi
}

create_term_if_missing "$VARIANT1_COLOR"
create_term_if_missing "$VARIANT2_COLOR"

# Pobierz ID utworzonych terminów (będą potrzebne przy przypięciu atrybutu i wariantach)
VARIANT1_TERM_ID=$(wp wc product_attribute_term list "$ATTRIBUTE_ID" --user="$WPUSER" --format=json | jq -r ".[] | select(.name == \"$VARIANT1_COLOR\") | .id" | head -n1)
VARIANT2_TERM_ID=$(wp wc product_attribute_term list "$ATTRIBUTE_ID" --user="$WPUSER" --format=json | jq -r ".[] | select(.name == \"$VARIANT2_COLOR\") | .id" | head -n1)
VARIANT1_TERM_SLUG=$(wp wc product_attribute_term list "$ATTRIBUTE_ID" --user="$WPUSER" --format=json | jq -r ".[] | select(.id == $VARIANT1_TERM_ID) | .slug" | head -n1)
VARIANT2_TERM_SLUG=$(wp wc product_attribute_term list "$ATTRIBUTE_ID" --user="$WPUSER" --format=json | jq -r ".[] | select(.id == $VARIANT2_TERM_ID) | .slug" | head -n1)

# Daty historyczne jak w history-test.sh
thirty_one_days_ago=$(date -d "31 days ago" +%s)
twenty_nine_days_ago=$(date -d "29 days ago" +%s)
yesterday_time=$(date -d "yesterday" +%s)
two_hours_ago_time=$(date -d "2 hours ago" +%s)

# Tablice dat i cen dla obu wariantów: indeks 0 = tworzenie, pozostałe = aktualizacje
timestamps=("$thirty_one_days_ago" "$twenty_nine_days_ago" "$yesterday_time" "$two_hours_ago_time")
variant1_prices=("15.00" "7.00" "8.00" "12.00")
variant2_prices=("30.00" "14.00" "16.00" "22.00")

sudo timedatectl set-ntp false

# Ustaw czas na pierwszą datę i utwórz produkt wraz z wariantami z cenami początkowymi
sudo date -s "@${timestamps[0]}"
date
# sleep 1

echo "Tworzę produkt variable..."
PRODUCT_ID=$(wp wc product create \
  --user="$WPUSER" \
  --name="$PRODUCT_NAME" \
  --slug="$PRODUCT_SLUG" \
  --type="variable" \
  --status="publish" \
  --regular_price="0" \
  --porcelain)

echo "✅ Produkt: $PRODUCT_ID"

echo "Przypisuję atrybut do produktu..."
wp wc product update $PRODUCT_ID \
  --user="$WPUSER" \
  --attributes='[
    {
      "id": '"$ATTRIBUTE_ID"',
      "name": "'"$ATTRIBUTE_NAME"'",
      "slug": "pa_kolor",
      "visible": true,
      "variation": true,
      "options": ["'"$VARIANT1_TERM_SLUG"'", "'"$VARIANT2_TERM_SLUG"'"]
    }
  ]' > /dev/null

echo "Tworzę wariant: $VARIANT1_COLOR..."
VARIANT1_ID=$(wp wc product_variation create $PRODUCT_ID \
  --user="$WPUSER" \
  --regular_price="${variant1_prices[0]}" \
  --attributes='[{"id": '"$ATTRIBUTE_ID"', "option": "'"$VARIANT1_TERM_SLUG"'"}]' \
  --porcelain)

echo "Tworzę wariant: $VARIANT2_COLOR..."
VARIANT2_ID=$(wp wc product_variation create $PRODUCT_ID \
  --user="$WPUSER" \
  --regular_price="${variant2_prices[0]}" \
  --attributes='[{"id": '"$ATTRIBUTE_ID"', "option": "'"$VARIANT2_TERM_SLUG"'"}]' \
  --porcelain)

# Aktualizacje cen wariantów w kolejnych datach
for i in "${!timestamps[@]}"; do
  if [[ "$i" -eq 0 ]]; then
    continue
  fi
  ts="${timestamps[$i]}"
  v1_price="${variant1_prices[$i]}"
  v2_price="${variant2_prices[$i]}"

  sudo date -s "@$ts"
  date
  # sleep 1

  wp wc product_variation update $PRODUCT_ID $VARIANT1_ID --user="$WPUSER" --regular_price="$v1_price"
  wp wc product_variation update $PRODUCT_ID $VARIANT2_ID --user="$WPUSER" --regular_price="$v2_price"
done

echo "
✅ GOTOWE!
Produkt ID: $PRODUCT_ID
Warianty: $VARIANT1_ID ($VARIANT1_COLOR, $VARIANT1_PRICE)
          $VARIANT2_ID ($VARIANT2_COLOR, $VARIANT2_PRICE)
"

# Przywróć czas
sudo timedatectl set-ntp true
date
