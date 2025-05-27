jQuery(document).ready(function () {
    document.getElementById("po_gen_price").addEventListener("input", function () {
        this.value = this.value.replace(/,/g, '.');
    });

    document.getElementById("po_gen_character_limit").addEventListener("input", function () {
        // Only allow numbers
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});
