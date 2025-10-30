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
  --regular_price="$VARIANT1_PRICE" \
  --attributes='[{"id": '"$ATTRIBUTE_ID"', "option": "'"$VARIANT1_TERM_SLUG"'"}]' \
  --porcelain)

echo "Tworzę wariant: $VARIANT2_COLOR..."
VARIANT2_ID=$(wp wc product_variation create $PRODUCT_ID \
  --user="$WPUSER" \
  --regular_price="$VARIANT2_PRICE" \
  --attributes='[{"id": '"$ATTRIBUTE_ID"', "option": "'"$VARIANT2_TERM_SLUG"'"}]' \
  --porcelain)

echo "
✅ GOTOWE!
Produkt ID: $PRODUCT_ID
Warianty: $VARIANT1_ID ($VARIANT1_COLOR, $VARIANT1_PRICE)
          $VARIANT2_ID ($VARIANT2_COLOR, $VARIANT2_PRICE)
"
