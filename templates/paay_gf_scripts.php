<script type="text/javascript">
    window.addEventListener('load', function(){
        var settings = {
            orSpan: false
        };

        window.paay = new PAAY(
            '/?page=paay_gf_handler&paay-module=createTransactionGF',
            '/?page=paay_gf_handler&paay-module=cancelTransactionGF',
            '/?page=paay_gf_handler&paay-module=awaitingApprovalGF',
            '/?page=paay_gf_handler&paay-module=approveWithout3dsGF',
            settings
        );

        paay.loadButtons();
        paay.bindEvents();

        PAAY.$("body").on("click", ".paay-button", function(e, is_real) {
            if (true !== is_real) {
                e.preventDefault();
                var $paay_button = PAAY.$(this);
                var $form = $paay_button.parents("form");
                var data = $form.serializeArray();
                data.push({
                    name: "paay_button_clicked",
                    value: "1"
                });

                PAAY.$.ajax({
                    type: "POST",
                    url: $form.attr("action"),
                    data: PAAY.$.param(data),
                    success: function(data) {
                        paay.config.url.createTransaction += "&order_id=" + data.id;
                        $paay_button.trigger("click", [true]);
                    }
                });
            }
        });
    });
</script>