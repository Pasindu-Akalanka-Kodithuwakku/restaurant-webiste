// view see more details
function showDetails(description, image) {
    document.getElementById('popupContent').innerText = description;
    document.getElementById('item_image').src = 'img/' + image;
    document.getElementById('popup').style.display = 'block';
}

// hide see more details
function hidePopup() {
    document.getElementById('popup').style.display = 'none';
}
