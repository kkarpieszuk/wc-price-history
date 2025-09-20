jQuery(document).ready(function ($) {
    var $exportButton = $('#wc-price-history-export-product-with-price-history');
    $exportButton.on('click', function () {
        var productId = $(this).data('product-id');
        $.post(ajaxurl, {
            action: 'wc_price_history_export_product_with_price_history',
            security: wc_price_history_export.nonce,
            product_id: productId,
        }, function (response) {
            if (response.success) {
                var blob = new Blob([JSON.stringify(response.data)], { type: 'application/json' });
                var url = window.URL.createObjectURL(blob);
                var link = document.createElement('a');
                link.href = url;
                link.download = "".concat(response.data.product_name, ".json");
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }
            else {
                $exportButton.after("<p class=\"wc-price-history-export-error\">".concat(response.data.message, "</p>"));
            }
        });
    });
});
//# sourceMappingURL=export.js.map