#!/usr/bin/env bash

set -e

# using `sudo date -s ...` set historical dates and create/update a WooCommerce product

# Calculate timestamps relative to now
thirty_one_days_ago=$(date -d "31 days ago" +%s)
twenty_nine_days_ago=$(date -d "29 days ago" +%s)
yesterday_time=$(date -d "yesterday" +%s)
two_hours_ago_time=$(date -d "2 hours ago" +%s)

# Arrays: timestamps aligned with prices; index 0 is creation, the rest are updates
timestamps=("$thirty_one_days_ago" "$twenty_nine_days_ago" "$yesterday_time" "$two_hours_ago_time")
prices=("19.99" "8.99" "9.99" "12.99")

sudo timedatectl set-ntp false

product_id=""
for i in "${!timestamps[@]}"; do
  ts="${timestamps[$i]}"
  price="${prices[$i]}"

  sudo date -s "@$ts"
  date
  sleep 1

  if [[ "$i" -eq 0 ]]; then
    product_id=$(wp wc product create --name="prod 4" --type="simple" --regular_price="$price" --user="konrad" --porcelain)
  else
    wp wc product update "$product_id" --regular_price="$price" --user="konrad"
  fi
done

# restore the time.
sudo timedatectl set-ntp true

date