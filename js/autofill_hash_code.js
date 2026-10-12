// for new record
document.getElementById("table_number").addEventListener("input", function () {
    document.getElementById("hash_code").value = md5(this.value.trim());

    if(document.getElementById("table_number").value.trim() == '') {
        document.getElementById("hash_code").value = '';
    }
});

// for update records
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("input[name^='table_number-']").forEach(input => {
        input.addEventListener("input", function () {
            const id = this.name.split('-')[1];
            const hashInput = document.querySelector(`input[name='hash_code-${id}']`);

            hashInput.value = md5(this.value.trim());

            if(input.value.trim() == '') {
                hashInput.value = '';
            }
        });
    });
});