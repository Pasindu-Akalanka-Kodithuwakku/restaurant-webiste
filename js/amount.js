// The clicked button (➕)
function inc(event) {
    let plusButton = event.target;  // The clicked button (➕)
    let inputField = plusButton.previousElementSibling; // Get the input field before the button
    let row = plusButton.closest("tr"); // Get the table row
    let checkbox = row.querySelector(".priceCheckbox"); // Get the checkbox in the same row
    let priceCell = plusButton.parentElement.nextElementSibling; // Get the price cell

    if (inputField && checkbox.checked) { // Only increase if the checkbox is checked
        // Increase the input value & price
        if (inputField.value < 20) {
            let price = priceCell.textContent;
            let unitPrice = parseFloat(price.replace(/,/g, "")) / parseInt(inputField.value);

            // Increase value
            inputField.value = parseInt(inputField.value) + 1;

            // Increase price
            priceCell.textContent = (parseFloat(price.replace(/,/g, "")) + unitPrice).toFixed(2);

            // Update total
            let total = document.getElementById("total");
            total.textContent = (parseFloat(total.textContent.replace(/,/g, "")) + unitPrice).toFixed(2);
        }
    } else {
        inputField.value = 1;
        alert("Please select the item before change quantity :(");
    }
}

// The clicked button (➖)
function dec(event) {
    let minusButton = event.target;  // The clicked button (➖)
    let inputField = minusButton.nextElementSibling; // Get the input field after the button
    let row = minusButton.closest("tr"); // Get the table row
    let checkbox = row.querySelector(".priceCheckbox"); // Get the checkbox in the same row
    let priceCell = minusButton.parentElement.nextElementSibling; // Get the price cell

    if (inputField && checkbox.checked) { // Only decrease if the checkbox is checked
        // Decrease the input value & price
        if (inputField.value > 1) {
            let price = priceCell.textContent;
            let unitPrice = parseFloat(price.replace(/,/g, "")) / parseInt(inputField.value);

            // Decrease value
            inputField.value = parseInt(inputField.value) - 1;

            // Decrease price
            priceCell.textContent = (parseFloat(price.replace(/,/g, "")) - unitPrice).toFixed(2);

            // Update total
            let total = document.getElementById("total");
            total.textContent = (parseFloat(total.textContent.replace(/,/g, "")) - unitPrice).toFixed(2);
        }
    } else {
        inputField.value = 1;
        alert("Please select the item before change quantity :(");
    }
}

// Update total
function updateTotal(event) {
    let checkbox = event.target; // Get clicked checkbox
    let totalElement = document.getElementById("total");
    let currentTotal = parseFloat(totalElement.textContent.replace(/,/g, "")) || 0;
    let itemPrice = parseFloat(checkbox.value);

    if (checkbox.checked) {
        // If checked, add the price
        totalElement.textContent = (currentTotal + itemPrice).toFixed(2);
    } else {
        // If unchecked, subtract the price
        totalElement.textContent = (currentTotal - itemPrice).toFixed(2);
    }
}

// Attach event listener to all checkboxes
document.querySelectorAll(".priceCheckbox").forEach(checkbox => {
    checkbox.addEventListener("change", updateTotal);
});

// Checkbox handle
function handleCheckboxChange(event) {
    let checkbox = event.target; // Get the clicked
    let row = checkbox.closest("tr"); // Get the table row
    let inputField = row.querySelector("input[type='number']"); // Find the number input in the same row

    if (inputField && (!checkbox.checked) && inputField.value > 1) {
        let totalElement = document.getElementById("total");
        let currentTotal = parseFloat(totalElement.textContent.replace(/,/g, "")) || 0;
        let itemPrice = parseFloat(checkbox.value * (inputField.value - 1));

        totalElement.textContent = (currentTotal - itemPrice).toFixed(2);
        row.cells[5].textContent = parseFloat(checkbox.value).toFixed(2);
        inputField.value = 1;
    }
}
