#!/usr/bin/env bash

set -e

# using `sudo date -s ...` set the date which was 31 days ago. Always relate to the current time when the script is run.

# get the current time in seconds since epoch.
current_time=$(date +%s)

# calculate the time 31 days ago in seconds since epoch.
thirty_one_days_ago=$(date -d "31 days ago" +%s)
twenty_nine_days_ago=$(date -d "29 days ago" +%s)
yesterday_time=$(date -d "yesterday" +%s)
two_hours_ago_time=$(date -d "2 hours ago" +%s)

sudo timedatectl set-ntp false

# set the date to 31 days ago.
sudo date -s "@$thirty_one_days_ago"

date

product_id=$(wp wc product create --name="prod 4" --type="simple" --regular_price="19.99" --user="konrad" --porcelain)

sudo date -s "@$twenty_nine_days_ago"
sleep 1
wp wc product update $product_id --regular_price="8.99" --user="konrad"

sudo date -s "@$yesterday_time"
sleep 1
wp wc product update $product_id --regular_price="9.99" --user="konrad"

sudo date -s "@$two_hours_ago_time"
sleep 1
wp wc product update $product_id --regular_price="12.99" --user="konrad"

# restore the time.
sudo timedatectl set-ntp true

date